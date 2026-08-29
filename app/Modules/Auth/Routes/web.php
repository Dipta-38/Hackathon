<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->middleware('throttle:10,1')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/verify-email', [AuthController::class, 'showEmailVerification'])->name('verify.email');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email.submit');
    Route::post('/verify-email/resend', [AuthController::class, 'resendEmailOtp'])->name('verify.email.resend');
    Route::get('/login', [AuthController::class, 'showLogin'])->middleware('throttle:10,1')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/login/otp', [AuthController::class, 'showLoginOtp'])->name('login.otp');
    Route::post('/login/otp', [AuthController::class, 'verifyLoginOtp'])->name('login.otp.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->middleware('throttle:20,1')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:20,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/notifications/{id}/read', [AuthController::class, 'markNotificationRead'])->middleware('auth')->name('notifications.read');
    Route::get('/settings', [AuthController::class, 'showSettings'])->middleware(['auth', 'throttle:30,1'])->name('settings');
    Route::post('/settings', [AuthController::class, 'updateSettings'])->middleware(['auth', 'throttle:30,1'])->name('settings.update');
        Route::get('/profile', [AuthController::class, 'showProfile'])->middleware(['auth', 'throttle:30,1'])->name('profile');
        Route::post('/profile', [AuthController::class, 'updateProfile'])->middleware(['auth', 'throttle:30,1'])->name('profile.update');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->middleware(['auth', 'throttle:30,1'])->name('dashboard');
});