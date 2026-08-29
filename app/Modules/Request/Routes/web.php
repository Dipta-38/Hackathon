<?php

use App\Modules\Request\Controllers\MoneyRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/money-request', [MoneyRequestController::class, 'create'])->name('money-request.create');
    Route::get('/request-money', [MoneyRequestController::class, 'requestMoneyPage'])->name('request.money');
    Route::post('/money-request', [MoneyRequestController::class, 'store'])->middleware('throttle:30,1')->name('money-request.store');
    Route::post('/money-request/{id}/accept', [MoneyRequestController::class, 'accept'])->name('money-request.accept');
    Route::post('/money-request/{id}/reject', [MoneyRequestController::class, 'reject'])->name('money-request.reject');
});
