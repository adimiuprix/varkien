<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\PustakaController;

Route::apiResource('pustaka', PustakaController::class);
Route::post('buang', [PustakaController::class, 'buangData']);
Route::post('buang', [PustakaController::class, 'buangData']);

Route::prefix('trade')->group(function () {
    
    // Endpoint utama untuk menerima signal
    Route::post('/signal', [TradeController::class, 'handleSignal'])
        ->middleware('throttle:60,1'); // Rate limit: 60 request per menit

    // Endpoint untuk cek status bot
    Route::get('/status', [TradeController::class, 'status']);

    // Endpoint untuk cek balance
    Route::get('/balance', [TradeController::class, 'balance']);
});