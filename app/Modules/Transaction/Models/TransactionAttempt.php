<?php

namespace App\Modules\Transaction\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionAttempt extends Model
{
    protected $fillable = [
        'idempotency_key',
        'from_user_id',
        'to_user_id',
        'amount',
        'status',
        'error_message',
        'attempted_at'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'attempted_at' => 'datetime',
    ];
}