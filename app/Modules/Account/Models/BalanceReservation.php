<?php

namespace App\Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceReservation extends Model
{
    protected $fillable = ['account_id', 'reservation_id', 'amount', 'expires_at'];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];
}