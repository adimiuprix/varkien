<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use ccxt\Exchange;
use Carbon\Carbon;

class CcxtController extends Controller
{
    public function listExchanges(): JsonResponse
    {
        $now = Carbon::now('Asia/Jakarta');

        $exchange_id = 'binance';
        $exchange_class = "\\ccxt\\{$exchange_id}";
        if (!class_exists($exchange_class)) {
            return response()->json(['error' => 'Exchange not found'], 404);
        }

        $exchange = new $exchange_class([
            'enableRateLimit' => true,
            'options' => [
                'adjustForTimeDifference' => true,
            ],
        ]);
        $exchange->load_markets();
        $exchange->fetch_time();
        $exchange->set_sandbox_mode(true);
        $exchange->set_time_difference($now->timestamp * 1000 - $exchange->fetch_time());
        $exchange->set_sandbox_mode(false);


        return response()->json([
            'exchanges' => Exchange::$exchanges,
        ]);
    }
}
