<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\AuthController;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::post('/order', [LandingController::class, 'store']);
Route::get('/bank-info', [LandingController::class, 'bankInfo']);
Route::get('/my-orders', [LandingController::class, 'myOrders'])->name('my.orders')->middleware('auth');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
