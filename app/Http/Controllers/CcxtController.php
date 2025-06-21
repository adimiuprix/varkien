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
        dd($now->toDateTimeString());
        return response()->json([
            'exchanges' => Exchange::$exchanges,
        ]);
    }
}
