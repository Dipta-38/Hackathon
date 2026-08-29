<?php
// app/Modules/Account/Routes/web.php

use App\Modules\Account\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/account/balance', [AccountController::class, 'balance'])->name('account.balance');
});