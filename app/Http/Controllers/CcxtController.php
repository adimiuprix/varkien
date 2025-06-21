<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use ccxt\Exchange;

class CcxtController extends Controller
{
    public function listExchanges(): JsonResponse
    {
        return response()->json([
            'exchanges' => Exchange::$exchanges,
        ]);
    }
}
