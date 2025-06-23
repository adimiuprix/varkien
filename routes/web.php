<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CcxtController;
use App\Http\Controllers\Akrobat;

Route::get('/', [HomeController::class, 'index'])->name('homepage');
Route::get('exchanges', [CcxtController::class, 'listExchanges'])->name('homepage');
Route::get('akrobat', [Akrobat::class, 'binance']);
