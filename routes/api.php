<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeController;

// Public endpoints (tanpa middleware)
Route::prefix('trade')->group(function () {

    // Handle Signal
    Route::get('/signal', [TradeController::class, 'handleSignal'])
        ->name('trade.signal');
    
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