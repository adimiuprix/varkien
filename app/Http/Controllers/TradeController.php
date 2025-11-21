<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TradeController extends Controller
{
    private string $apiKey;
    private string $apiSecret;
    private string $passphrase;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey     = env('BITGET_API_KEY');
        $this->apiSecret  = env('BITGET_API_SECRET');
        $this->passphrase = env('BITGET_PASSPHRASE');
        $this->baseUrl    = rtrim(env('BITGET_BASE_URL', 'https://api.bitget.com'), '/');
    }

    public function handleSignal(Request $request)
    {
        $signal = $request->input('body');

        if (!is_array($signal) || empty($signal['pair']) || empty($signal['recomendation'])) {
            return response()->json(['error' => 'Invalid signal body'], 400);
        }

        $pair = strtoupper($signal['pair']);
        $normalized = $this->normalizeRecommendation($signal['recomendation']);

        // neutral → tidak ada aksi
        if ($normalized === 'neutral') {
            return response()->json(['message' => 'Signal neutral, no action'], 200);
        }

        try {
            $coin = strtoupper(str_replace('USDT', '', $pair));
            $available = $this->getAvailableBalance($coin); // float

            // Jika ada posisi BUY (available > 0) dan signal = sell => close all then open sell (note: spot can't short)
            if ($available > 0 && $normalized === 'sell') {
                // close: sell all available
                $closeBody = [
                    'symbol'    => $pair,
                    'side'      => 'sell',
                    'orderType' => 'market',
                    'force'     => 'gtc',
                    'size'      => (string) $available,
                    'clientOid' => uniqid('close_'),
                ];

                $closeResp = $this->executeOrder($closeBody);

                if (! $this->isSuccess($closeResp)) {
                    Log::warning('Failed to close position', ['pair' => $pair, 'resp' => $closeResp->body()]);
                    return response()->json(['error' => 'Failed to close existing position', 'detail' => $closeResp->json()], 500);
                }

                // After closing, on SPOT typically you won't enter a sell (short) — skip opening sell unless you want to trade futures.
                return response()->json([
                    'message' => 'Position closed due to sell signal (spot). No short opened on spot.',
                    'close' => $closeResp->json()
                ], 200);
            }

            // If available == 0 and signal = sell -> nothing to close / skip
            if ($available == 0 && $normalized === 'sell') {
                return response()->json(['message' => 'No asset to sell on spot. Skipping.'], 200);
            }

            // If available > 0 and signal = buy -> already holding, skip buy
            if ($available > 0 && $normalized === 'buy') {
                return response()->json(['message' => 'Already holding asset. Skipping buy.'], 200);
            }

            // No position and signal = buy -> open buy
            if ($available == 0 && $normalized === 'buy') {
                $entrySize = $this->determineSize($pair, $normalized, $available);

                if ($entrySize <= 0) {
                    return response()->json(['error' => 'Entry size calculated zero'], 400);
                }

                $entryBody = [
                    'symbol'    => $pair,
                    'side'      => 'buy',
                    'orderType' => 'market',
                    'force'     => 'gtc',
                    'size'      => (string) $entrySize,
                    'clientOid' => uniqid('entry_'),
                ];

                $entryResp = $this->executeOrder($entryBody);

                if (! $this->isSuccess($entryResp)) {
                    Log::warning('Failed to place entry order', ['pair' => $pair, 'resp' => $entryResp->body()]);
                    return response()->json(['error' => 'Failed to place entry order', 'detail' => $entryResp->json()], 500);
                }

                return response()->json([
                    'message' => 'Buy order placed',
                    'entry' => $entryResp->json()
                ], 200);
            }

            // Fallback safety
            return response()->json(['message' => 'No action taken'], 200);

        } catch (\Throwable $e) {
            Log::error('handleSignal error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal error', 'detail' => $e->getMessage()], 500);
        }
    }

    private function normalizeRecommendation(string $rec): string
    {
        $r = strtoupper(trim($rec));
        return match ($r) {
            'BUY', 'STRONG BUY' => 'buy',
            'SELL', 'STRONG SELL' => 'sell',
            default => 'neutral',
        };
    }

    /**
     * Ambil available balance coin di Spot
     * Mengembalikan float jumlah available coin (0.0 jika gagal)
     */
    private function getAvailableBalance(string $coin): float
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $requestPath = '/api/v2/spot/account/assets';
        $method = 'GET';
        $query = http_build_query(['coin' => $coin]);

        $preSign = $timestamp . strtoupper($method) . $requestPath . '?' . $query;
        $sign = base64_encode(hash_hmac('sha256', $preSign, $this->apiSecret, true));

        $resp = Http::withHeaders([
            'ACCESS-KEY'        => $this->apiKey,
            'ACCESS-SIGN'       => $sign,
            'ACCESS-TIMESTAMP'  => $timestamp,
            'ACCESS-PASSPHRASE' => $this->passphrase,
            'Content-Type'      => 'application/json',
        ])->get($this->baseUrl . $requestPath . '?' . $query);

        if (! $resp->successful()) {
            Log::warning('Balance check failed', ['coin' => $coin, 'resp' => $resp->body()]);
            return 0.0;
        }

        // Sesuaikan struktur jika API berbeda. Asumsi: data.available
        return (float) ($resp->json('data.available') ?? 0.0);
    }

    /**
     * Execute order ke Bitget (POST)
     * Mengembalikan Illuminate\Http\Client\Response
     */
    private function executeOrder(array $body): Response
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $requestPath = '/api/v2/spot/trade/place-order';
        $method = 'POST';
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);

        $preSign = $timestamp . strtoupper($method) . $requestPath . $bodyJson;
        $sign = base64_encode(hash_hmac('sha256', $preSign, $this->apiSecret, true));

        return Http::withHeaders([
            'ACCESS-KEY'        => $this->apiKey,
            'ACCESS-SIGN'       => $sign,
            'ACCESS-TIMESTAMP'  => $timestamp,
            'ACCESS-PASSPHRASE' => $this->passphrase,
            'Content-Type'      => 'application/json',
        ])->withBody($bodyJson, 'application/json')->post($this->baseUrl . $requestPath);
    }

    private function isSuccess(Response $resp): bool
    {
        if (! $resp->successful()) return false;
        $code = $resp->json('code');
        return ($code !== null && (string)$code === '00000');
    }

    /**
     * Tentukan ukuran order. Saat ini fixed size; ubah sesuai strategi.
     * Kamu bisa kembangkan: based on USDT balance, percent, or dynamic calc.
     */
    private function determineSize(string $pair, string $action, float $available): float
    {
        // contoh sederhana: fixed size 1.0 (ubah sesuai kebutuhan)
        return (float) env('TRADE_FIXED_SIZE', 1.0);
    }
}
