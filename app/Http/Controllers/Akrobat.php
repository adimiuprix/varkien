<?php

namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use ccxt\Exchange;

class Akrobat extends Controller
{
    public function binance(): JsonResponse
    {

        return response()->json(['message' => 'Binance endpoint reached']);
    }
}
