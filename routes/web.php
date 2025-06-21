<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CcxtController;

Route::get('/', [HomeController::class, 'index'])->name('homepage');
Route::get('exchanges', [CcxtController::class, 'listExchanges'])->name('homepage');
