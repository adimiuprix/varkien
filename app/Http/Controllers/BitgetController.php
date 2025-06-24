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
}
