<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;

Route::get('/', [LandingController::class, 'index']);
Route::post('/order', [LandingController::class, 'store']);
Route::get('/qris/{amount}', [LandingController::class, 'qris']);
Route::get('/bank-info', [LandingController::class, 'bankInfo']);
