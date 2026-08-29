<?php

use App\Modules\Transaction\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/transfer', [TransferController::class, 'create'])->name('transfer.create');
    Route::get('/send-money', [TransferController::class, 'sendMoneyPage'])->name('send.money');
    Route::get('/transfer/verify', [TransferController::class, 'verify'])->name('transfer.verify');
    Route::get('/transfer/recipient-preview', [TransferController::class, 'recipientPreview'])->name('transfer.recipient-preview');
    Route::post('/transfer/receiver-confirm/{token}', [TransferController::class, 'confirmReceiverTransfer'])->name('transfer.receiver-confirm');
    Route::post('/transfer', [TransferController::class, 'store'])->middleware('throttle:30,1')->name('transfer.store');
    Route::get('/transactions', [TransferController::class, 'history'])->name('transaction.history');
    Route::post('/transactions/clear', [TransferController::class, 'clearHistory'])->name('transaction.clear');
    Route::get('/transactions/{id}/receipt', [TransferController::class, 'receipt'])->name('transaction.receipt');
});
