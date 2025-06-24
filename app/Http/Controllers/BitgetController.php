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
            'businessType' => $request->get('businessType', 'spot'),
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
                    'response' => $response,
                    'status' => $response->status(),
                    'data' => data_get($response, 'data'),
                ]);
            }

            Log::warning('Gagal ambil trade rate', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal ambil trade rate'], 500);
        } catch (\Throwable $e) {
            Log::error('Error ambil trade rate', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function spotRecord(Request $request)
    {
        $apiKey     = 'bg_fd76a0a9024313374466a07763bdbd14';
        $apiSecret  = '1c6bde77c85ce625433ece5bee29fb0bc2c15f2087f54750abed9944a61b8784';
        $passphrase = 'VeRTErveldSeNTiLsECR';
        $baseUrl    = 'https://api.bitget.com';
        $timestamp  = (string) round(microtime(true) * 1000);

        $params = [
            'startTime' => $request->get('startTime', 1750542133000),
            'endTime'   => $request->get('endTime', 1750740133000),
            'limit'     => $request->get('limit', 100),
        ];

        $queryString = http_build_query($params);
        $requestPath = "/api/v2/tax/spot-record?$queryString";
        $method      = 'GET';
        $body        = '';

        $preSign = $timestamp . strtoupper($method) . "/api/v2/tax/spot-record" . '?' . $queryString . $body;
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
                    'response' => $response,
                    'status'   => $response->status(),
                    'data'     => data_get($response, 'data'),
                ]);
            }

            Log::warning('Gagal ambil spot record', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal ambil spot record'], 500);
        } catch (\Throwable $e) {
            Log::error('Error ambil spot record', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function futureRecord(Request $request)
    {
        $apiKey     = 'bg_fd76a0a9024313374466a07763bdbd14';
        $apiSecret  = '1c6bde77c85ce625433ece5bee29fb0bc2c15f2087f54750abed9944a61b8784';
        $passphrase = 'VeRTErveldSeNTiLsECR';
        $baseUrl    = 'https://api.bitget.com';
        $timestamp  = (string) round(microtime(true) * 1000);

        $params = [
            'startTime'   => $request->get('startTime', 1748031522),
            'endTime'     => $request->get('endTime', 1750735122),
            'limit'       => $request->get('limit', 100),
            'productType' => $request->get('productType', 'USDT-FUTURES'),
            'marginCoin'  => $request->get('marginCoin'),
            'idLessThan'  => $request->get('idLessThan'),
        ];

        // Hilangkan null parameter dari query
        $params = array_filter($params, fn($value) => !is_null($value));

        $queryString = http_build_query($params);
        $requestPath = "/api/v2/tax/future-record?$queryString";
        $method      = 'GET';
        $body        = '';

        $preSign = $timestamp . strtoupper($method) . "/api/v2/tax/future-record" . '?' . $queryString . $body;
        $sign    = base64_encode(hash_hmac('sha256', $preSign, $apiSecret, true));

        try {
            $response = Http::withHeaders([
                'ACCESS-KEY'        => $apiKey,
                'ACCESS-SIGN'       => $sign,
                'ACCESS-TIMESTAMP'  => $timestamp,
                'ACCESS-PASSPHRASE' => $passphrase,
                'Content-Type'      => 'application/json',
                'locale'            => 'en-US',
            ])->get($baseUrl . $requestPath);

            if ($response->successful() && data_get($response, 'code') === '00000') {
                return response()->json([
                    'response' => $response,
                    'status'   => $response->status(),
                    'data'     => data_get($response, 'data'),
                ]);
            }

            Log::warning('Gagal ambil future record', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal ambil future record'], 500);
        } catch (\Throwable $e) {
            Log::error('Error ambil future record', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function placeSpotOrder(Request $request)
    {
        $apiKey     = 'bg_fd76a0a9024313374466a07763bdbd14';
        $apiSecret  = '1c6bde77c85ce625433ece5bee29fb0bc2c15f2087f54750abed9944a61b8784';
        $passphrase = 'VeRTErveldSeNTiLsECR';
        $baseUrl    = 'https://api.bitget.com';
        $timestamp  = (string) round(microtime(true) * 1000);

        $body = [
            'symbol'                    => $request->input('symbol', 'OASUSDT'),
            'side'                      => $request->input('side', 'buy'),
            'orderType'                 => $request->input('orderType', 'market'),
            'force'                     => $request->input('force', 'gtc'),
            'size'                      => $request->input('size', '3.50'),
            'clientOid'                 => $request->input('clientOid', uniqid()),
        ];

        $bodyJson   = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestPath = '/api/v2/spot/trade/place-order';
        $method      = 'POST';

        $preSign = $timestamp . strtoupper($method) . $requestPath . $bodyJson;
        $sign    = base64_encode(hash_hmac('sha256', $preSign, $apiSecret, true));

        try {
            $response = Http::withHeaders([
                'ACCESS-KEY'        => $apiKey,
                'ACCESS-SIGN'       => $sign,
                'ACCESS-TIMESTAMP'  => $timestamp,
                'ACCESS-PASSPHRASE' => $passphrase,
                'locale'            => 'en-US',
                'Content-Type'      => 'application/json',
            ])->post($baseUrl . $requestPath, $body);

            if ($response->successful() && data_get($response, 'code') === '00000') {
                return response()->json([
                    'response' => $response,
                    'status'   => $response->status(),
                    'data'     => data_get($response, 'data'),
                ]);
            }

            Log::warning('Gagal place order', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => $response->body()], 500);
        } catch (\Throwable $e) {
            Log::error('Error place order', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
