<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Akrobat;
use App\Http\Controllers\BitgetController;
use App\Http\Controllers\FormController;

Route::get('/', [HomeController::class, 'index'])->name('homepage');
Route::get('akrobat', [Akrobat::class, 'binance']);
Route::get('stime', [BitgetController::class, 'serverTime']);
Route::get('trade-rate', [BitgetController::class, 'tradeRate']);
Route::get('spot-record', [BitgetController::class, 'spotRecord']);
Route::get('future-record', [BitgetController::class, 'futureRecord']);

Route::get('future-record', [BitgetController::class, 'futureRecord']);

Route::get('form', [FormController::class, 'index']);
Route::post('spot-order', [BitgetController::class, 'placeSpotOrder'])->name('spot.order');
