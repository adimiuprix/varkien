<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PustakaController;

Route::get('halo', function () {
    return response()->json([
        'message' => 'Halo dari API Laravel! ✨',
        'status' => 'success'
    ], 200, [], JSON_UNESCAPED_UNICODE);
});

Route::apiResource('pustaka', PustakaController::class);
Route::post('buang', [PustakaController::class, 'buangData']);
