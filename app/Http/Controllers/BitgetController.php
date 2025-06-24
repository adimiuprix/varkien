<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitgetController extends Controller
{
    public function serverTime()
    {
        try {
            $response = Http::timeout(5)->get('https://api.bitget.com/api/v2/public/time');

            if ($response->successful() && data_get($response, 'code') === '00000') {
                return response()->json([
                    'server_time' => (int) data_get($response, 'data.serverTime')
                ]);
            }

            Log::warning('Gagal ambil server time dari Bitget', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json(['error' => 'Gagal mengambil waktu server'], 500);
        } catch (\Throwable $e) {
            Log::error('Exception saat ambil server time Bitget', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function tradeRate(Request $request)
    {
        $apiKey      = 'bg_fd76a0a9024313374466a07763bdbd14';
        $apiSecret   = '1c6bde77c85ce625433ece5bee29fb0bc2c15f2087f54750abed9944a61b8784';
        $passphrase  = 'VeRTErveldSeNTiLsECR';
        $baseUrl     = 'https://api.bitget.com';
        $timestamp   = (string) round(microtime(true) * 1000);

        $params = [
            'symbol'       => $request->get('symbol', 'BTCUSDT'),
            'businessType' => $request->get('businessType', 'mix'),
        ];

        $queryString = http_build_query($params);
        $requestPath = "/api/v2/common/trade-rate?$queryString";
        $method      = 'GET';
        $body        = '';

        $preSign = $timestamp . strtoupper($method) . "/api/v2/common/trade-rate" . '?' . $queryString . $body;
        $sign    = base64_encode(hash_hmac('sha256', $preSign, $apiSecret, true));

        try {
            $response = Http::withHeaders([
                'ACCESS-KEY'       => $apiKey,
                'ACCESS-SIGN'      => $sign,
                'ACCESS-TIMESTAMP' => $timestamp,
                'ACCESS-PASSPHRASE'=> $passphrase,
                'Content-Type'     => 'application/json',
                'locale'           => 'en-US',
            ])->get($baseUrl . $requestPath);

            if ($response->successful() && data_get($response, 'code') === '00000') {
                return response()->json([
                    'trade_rate' => data_get($response, 'data.tradeRate', 0),
                    'symbol'     => $params['symbol'],
                    'businessType' => $params['businessType'],
                ]);
            }

            Log::warning('Gagal ambil trade rate', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal ambil trade rate'], 500);
        } catch (\Throwable $e) {
            Log::error('Error ambil trade rate', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }
}
