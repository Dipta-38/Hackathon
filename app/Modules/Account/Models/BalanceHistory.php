<?php

namespace App\Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    protected $table = 'balance_history';

    protected $fillable = [
        'account_id', 'amount', 'type', 
        'balance_before', 'balance_after', 'version'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];
}