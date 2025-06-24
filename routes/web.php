<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CcxtController;
use App\Http\Controllers\Akrobat;
use App\Http\Controllers\BitgetController;

Route::get('/', [HomeController::class, 'index'])->name('homepage');
Route::get('exchanges', [CcxtController::class, 'listExchanges'])->name('homepage');
Route::get('akrobat', [Akrobat::class, 'binance']);
Route::get('stime', [BitgetController::class, 'serverTime']);
Route::get('trade-rate', [BitgetController::class, 'tradeRate']);
Route::get('spot-record', [BitgetController::class, 'spotRecord']);
