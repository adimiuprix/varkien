<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use ccxt\Exchange;

class CcxtController extends Controller
{
    public function listExchanges(): JsonResponse
    {
        $binance = new \ccxt\binance([
            'enableRateLimit' => true
        ]);
        dd($binance->fetchOHLCV('BTC/USDT', '1m', null, 10));
        return response()->json([
            'exchanges' => Exchange::$camelcase_methods,
        ]);
    }
}
