<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
	Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
	Route::post('/register', [AuthController::class, 'register']);
	Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
	Route::post('/login', [AuthController::class, 'login']);
	Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
	Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
	Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
	Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
	Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
	Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware('auth')->name('dashboard');
});
