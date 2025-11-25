<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TradeController extends Controller
{
    private string $apiKey;
    private string $apiSecret;
    private string $passphrase;
    private string $baseUrl;
    private int $maxRetries = 3;

    public function __construct()
    {
        $this->apiKey     = config('bitget.api_key');
        $this->apiSecret  = config('bitget.api_secret');
        $this->passphrase = config('bitget.passphrase');
        $this->baseUrl    = rtrim(config('bitget.base_url', 'https://api.bitget.com'), '/');
    }

    /**
     * Endpoint untuk menerima signal dari server API
     * POST /api/trade/signal
     */
    public function handleSignal(Request $request)
    {
        // Support format lama dari Python (dengan wrapper "body")
        $data = $request->input('body') ?? $request->all();
        
        // Normalisasi key "recomendation" (typo) ke "recommendation"
        if (isset($data['recomendation']) && !isset($data['recommendation'])) {
            $data['recommendation'] = $data['recomendation'];
        }

        // Validasi request
        $validator = validator($data, [
            'pair' => 'required|string',
            'recommendation' => 'required|string',
            'price' => 'nullable|numeric',
            'timestamp' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', [
                'errors' => $validator->errors(),
                'data' => $data
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        $pair = strtoupper($validated['pair']);

        // HANYA IZINKAN PAIR YANG DIKONFIGURASI
        $allowedPair = config('bitget.allowed_pair', 'ENAUSDT');
        
        if ($pair !== $allowedPair) {
            Log::warning('Pair not allowed', [
                'received_pair' => $pair,
                'allowed_pair' => $allowedPair
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Pair not allowed',
                'message' => "Bot hanya trading {$allowedPair}. Pair yang dikirim: {$pair}"
            ], 403);
        }

        $recommendation = $this->normalizeRecommendation($validated['recommendation']);

        Log::info('Signal received', [
            'pair' => $pair,
            'recommendation' => $recommendation,
            'raw' => $validated['recommendation'],
            'price' => $validated['price'] ?? null,
        ]);

        // Skip jika neutral/hold
        if (in_array($recommendation, ['neutral', 'hold'])) {
            return response()->json([
                'success' => true,
                'message' => 'Signal neutral/hold, no action taken',
                'pair' => $pair
            ], 200);
        }

        try {
            // Ekstrak coin dari pair (misal: BTCUSDT -> BTC)
            $coin = $this->extractCoin($pair);
            
            // Cek balance saat ini
            $balance = $this->getAvailableBalance($coin);
            $usdtBalance = $this->getAvailableBalance('USDT');

            Log::info('Current balance', [
                'coin' => $coin,
                'balance' => $balance,
                'usdt' => $usdtBalance
            ]);

            // Logic trading
            return $this->executeTradingLogic($pair, $coin, $recommendation, $balance, $usdtBalance);

        } catch (\Throwable $e) {
            Log::error('handleSignal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'pair' => $pair
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute trading logic berdasarkan signal dan balance
     */
    private function executeTradingLogic(string $pair, string $coin, string $recommendation, float $balance, float $usdtBalance)
    {
        $actions = [];

        // SELL SIGNAL
        if ($recommendation === 'sell') {
            if ($balance > 0) {
                // Ada posisi, close semua
                $closeResult = $this->closePosition($pair, $coin, $balance);
                $actions[] = $closeResult;

                if (!$closeResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to close position',
                        'actions' => $actions
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Position closed successfully',
                    'actions' => $actions
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No position to close',
                    'pair' => $pair
                ], 200);
            }
        }

        // BUY SIGNAL
        if ($recommendation === 'buy') {
            if ($balance > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Already holding position, skipping buy',
                    'current_balance' => $balance
                ], 200);
            }

            // Tidak ada posisi, buka posisi baru
            $minUsdtRequired = config('bitget.min_usdt_balance', 10);
            
            if ($usdtBalance < $minUsdtRequired) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient USDT balance',
                    'usdt_balance' => $usdtBalance,
                    'required' => $minUsdtRequired
                ], 400);
            }

            $buyResult = $this->openPosition($pair, $usdtBalance);
            $actions[] = $buyResult;

            if (!$buyResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to open position',
                    'actions' => $actions
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Position opened successfully',
                'actions' => $actions
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'No action taken'
        ], 200);
    }

    /**
     * Close posisi yang ada (SELL)
     */
    private function closePosition(string $pair, string $coin, float $balance): array
    {
        $symbolInfo = $this->getSymbolInfo($pair);
        $minSize = $symbolInfo['minSize'] ?? $this->getMinOrderSize($pair);
        
        if ($balance < $minSize) {
            Log::warning('Balance too low to close', [
                'pair' => $pair,
                'balance' => $balance,
                'min_size' => $minSize
            ]);
            
            return [
                'success' => false,
                'action' => 'close',
                'message' => "Balance too low. Min: {$minSize}, Current: {$balance}"
            ];
        }

        $precision = $symbolInfo['quantityPrecision'] ?? 6;
        $size = $this->formatSize($balance, $precision);

        $orderBody = [
            'symbol'    => $pair,
            'side'      => 'sell',
            'orderType' => 'market',
            'force'     => 'gtc',
            'size'      => $size,
            'clientOid' => $this->generateClientOid('close'),
        ];

        $response = $this->executeOrderWithRetry($orderBody);

        return [
            'success' => $this->isSuccess($response),
            'action' => 'close',
            'pair' => $pair,
            'size' => $size,
            'response' => $response->json(),
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Open posisi baru (BUY)
     */
    private function openPosition(string $pair, float $usdtBalance): array
    {
        $fall = Log::info($usdtBalance);
        dd($fall);
        // Mode trading: 'percentage' atau 'fixed'
        $tradeMode = config('bitget.trade_mode', 'fixed');
        
        if ($tradeMode === 'fixed') {
            // Mode FIXED: Gunakan jumlah USDT tetap
            $usdtToUse = config('bitget.trade_fixed_usdt', 2);

            // Validasi: pastikan balance cukup
            if ($usdtBalance < $usdtToUse) {
                return [
                    'success' => false,
                    'action' => 'open',
                    'message' => "Insufficient USDT balance. Required: {$usdtToUse} USDT, Available: {$usdtBalance} USDT"
                ];
            }
        } else {
            // Mode PERCENTAGE: Gunakan persentase dari balance
            $tradePercent = config('bitget.trade_percent', 95);
            $usdtToUse = ($usdtBalance * $tradePercent) / 100;
        }

        // Dapatkan harga terkini untuk estimasi
        $currentPrice = $this->getCurrentPrice($pair);
        
        if (!$currentPrice) {
            return [
                'success' => false,
                'action' => 'open',
                'message' => 'Failed to get current price'
            ];
        }

        // Hitung size coin yang akan dibeli
        $size = $usdtToUse / $currentPrice;
        
        // Get symbol info untuk validasi
        $symbolInfo = $this->getSymbolInfo($pair);
        $minSize = $symbolInfo['minSize'] ?? $this->getMinOrderSize($pair);
        $precision = $symbolInfo['quantityPrecision'] ?? 6;

        if ($size < $minSize) {
            return [
                'success' => false,
                'action' => 'open',
                'message' => "Calculated size too low. Min: {$minSize}, Calculated: {$size}. Try increasing trade_fixed_usdt or use higher balance."
            ];
        }

        $formattedSize = $this->formatSize($size, $precision);

        $orderBody = [
            'symbol'    => $pair,
            'side'      => 'buy',
            'orderType' => 'market',
            'force'     => 'gtc',
            'size'      => $formattedSize,
            'clientOid' => $this->generateClientOid('open'),
        ];

        $response = $this->executeOrderWithRetry($orderBody);

        return [
            'success' => $this->isSuccess($response),
            'action' => 'open',
            'pair' => $pair,
            'size' => $formattedSize,
            'usdt_used' => $usdtToUse,
            'price' => $currentPrice,
            'response' => $response->json(),
            'timestamp' => now()->toIso8601String()
        ];
    }

    /**
     * Get symbol info dari Bitget API
     */
    private function getSymbolInfo(string $pair): array
    {
        $cacheKey = "symbol_info_{$pair}";
        
        return Cache::remember($cacheKey, 3600, function () use ($pair) {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/api/v2/spot/public/symbols", [
                    'symbol' => $pair
                ]);

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (is_array($data) && count($data) > 0) {
                        $symbol = $data[0];
                        return [
                            'minSize' => (float) ($symbol['minTradeAmount'] ?? 0),
                            'quantityPrecision' => (int) ($symbol['quantityScale'] ?? 6),
                            'pricePrecision' => (int) ($symbol['priceScale'] ?? 6),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to get symbol info', [
                    'pair' => $pair,
                    'error' => $e->getMessage()
                ]);
            }

            return [];
        });
    }

    /**
     * Dapatkan harga terkini
     */
    private function getCurrentPrice(string $pair): ?float
    {
        try {
            $cacheKey = "price_{$pair}";
            
            return Cache::remember($cacheKey, 5, function () use ($pair) {
                $response = Http::timeout(10)->get("{$this->baseUrl}/api/v2/spot/market/tickers", [
                    'symbol' => $pair
                ]);

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (is_array($data) && count($data) > 0) {
                        return (float) ($data[0]['lastPr'] ?? 0);
                    }
                }

                return null;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to get current price', [
                'pair' => $pair,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Dapatkan available balance dengan retry
     */
    private function getAvailableBalance(string $coin): float
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                $timestamp = (string) round(microtime(true) * 1000);
                $requestPath = '/api/v2/spot/account/assets';
                $method = 'GET';
                $query = http_build_query(['coin' => $coin]);

                $preSign = $timestamp . strtoupper($method) . $requestPath . '?' . $query;
                $sign = base64_encode(hash_hmac('sha256', $preSign, $this->apiSecret, true));

                $response = Http::timeout(15)->withHeaders([
                    'ACCESS-KEY'        => $this->apiKey,
                    'ACCESS-SIGN'       => $sign,
                    'ACCESS-TIMESTAMP'  => $timestamp,
                    'ACCESS-PASSPHRASE' => $this->passphrase,
                    'Content-Type'      => 'application/json',
                ])->get($this->baseUrl . $requestPath . '?' . $query);

                if ($response->successful()) {
                    $data = $response->json('data');
                    
                    if (is_array($data) && count($data) > 0) {
                        return (float) ($data[0]['available'] ?? 0.0);
                    }
                    
                    return 0.0;
                }

                $lastError = "HTTP {$response->status()}: {$response->body()}";
                
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                usleep(500000); // 500ms delay
            }
        }

        Log::warning('Failed to get balance after retries', [
            'coin' => $coin,
            'attempts' => $this->maxRetries,
            'last_error' => $lastError
        ]);

        return 0.0;
    }

    /**
     * Execute order dengan retry mechanism
     */
    private function executeOrderWithRetry(array $body): Response
    {
        $attempt = 0;
        $lastResponse = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->executeOrder($body);
                
                if ($this->isSuccess($response)) {
                    return $response;
                }

                // Jika error bukan network issue, jangan retry
                $errorCode = $response->json('code');
                if (in_array($errorCode, ['40015', '40016', '40017'])) {
                    // Balance/size errors - jangan retry
                    return $response;
                }

                $lastResponse = $response;
                
            } catch (\Throwable $e) {
                Log::warning('Order execution failed, retrying...', [
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage()
                ]);
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                sleep(1); // 1 second delay
            }
        }

        return $lastResponse ?? Http::response(['error' => 'Max retries reached'], 500);
    }

    /**
     * Execute order
     */
    private function executeOrder(array $body): Response
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $requestPath = '/api/v2/spot/trade/place-order';
        $method = 'POST';
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);

        $preSign = $timestamp . strtoupper($method) . $requestPath . $bodyJson;
        $sign = base64_encode(hash_hmac('sha256', $preSign, $this->apiSecret, true));

        Log::info('Executing order', ['body' => $body]);

        $response = Http::timeout(30)->withHeaders([
            'ACCESS-KEY'        => $this->apiKey,
            'ACCESS-SIGN'       => $sign,
            'ACCESS-TIMESTAMP'  => $timestamp,
            'ACCESS-PASSPHRASE' => $this->passphrase,
            'Content-Type'      => 'application/json',
        ])->withBody($bodyJson, 'application/json')
          ->post($this->baseUrl . $requestPath);

        Log::info('Order response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return $response;
    }

    /**
     * Cek apakah response sukses
     */
    private function isSuccess(Response $response): bool
    {
        if (!$response->successful()) {
            return false;
        }

        $code = $response->json('code');
        return ($code !== null && (string)$code === '00000');
    }

    /**
     * Normalize recommendation
     */
    private function normalizeRecommendation(string $rec): string
    {
        $normalized = strtoupper(trim($rec));
        
        return match ($normalized) {
            'BUY', 'STRONG BUY', 'STRONG_BUY' => 'buy',
            'SELL', 'STRONG SELL', 'STRONG_SELL' => 'sell',
            'NEUTRAL', 'HOLD' => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * Extract coin dari pair
     */
    private function extractCoin(string $pair): string
    {
        return strtoupper(str_replace('USDT', '', $pair));
    }

    /**
     * Generate unique client order ID
     */
    private function generateClientOid(string $prefix): string
    {
        return $prefix . '_' . time() . '_' . substr(uniqid(), -6);
    }

    /**
     * Format size sesuai dengan presisi
     */
    private function formatSize(float $size, int $precision): string
    {
        return rtrim(rtrim(number_format($size, $precision, '.', ''), '0'), '.');
    }

    /**
     * Dapatkan minimum order size dari config
     */
    private function getMinOrderSize(string $pair): float
    {
        return config("bitget.pairs.{$pair}.min_size", 0.00001);
    }

    /**
     * Endpoint untuk cek status bot
     */
    public function status()
    {
        $allowedPair = config('bitget.allowed_pair');
        $tradeMode = config('bitget.trade_mode');
        
        $config = [
            'base_url' => $this->baseUrl,
            'api_key_configured' => !empty($this->apiKey),
            'allowed_pair' => $allowedPair,
            'trade_mode' => $tradeMode,
        ];

        if ($tradeMode === 'fixed') {
            $config['trade_fixed_usdt'] = config('bitget.trade_fixed_usdt');
        } else {
            $config['trade_percent'] = config('bitget.trade_percent');
        }

        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toIso8601String(),
            'config' => $config
        ]);
    }

    /**
     * Endpoint untuk cek balance
     */
    public function balance(Request $request)
    {
        $coin = strtoupper($request->input('coin', 'USDT'));
        $balance = $this->getAvailableBalance($coin);

        return response()->json([
            'coin' => $coin,
            'available' => $balance,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Endpoint untuk testing signal
     */
    public function testSignal(Request $request)
    {
        $pair = $request->input('pair', config('bitget.allowed_pair'));
        $recommendation = $request->input('recommendation', 'buy');

        return $this->handleSignal($request->merge([
            'pair' => $pair,
            'recommendation' => $recommendation,
            'timestamp' => time()
        ]));
    }
}