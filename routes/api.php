<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\PustakaController;

Route::apiResource('pustaka', PustakaController::class);
Route::post('buang', [PustakaController::class, 'buangData']);
Route::post('buang', [PustakaController::class, 'buangData']);

// Public endpoints (tanpa middleware)
Route::prefix('trade')->group(function () {
    
    // Status check
    Route::get('/status', [TradeController::class, 'status'])
        ->name('trade.status');
    
    // Balance check
    Route::get('/balance', [TradeController::class, 'balance'])
        ->name('trade.balance');
    
    // Test signal (untuk development/testing)
    // IMPORTANT: Disable di production!
    Route::post('/test-signal', [TradeController::class, 'testSignal'])
        ->name('trade.test')
        ->middleware('throttle:10,1');
});