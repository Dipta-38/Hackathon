<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReceiverTransferConfirmation extends Model
{
    protected $fillable = [
        'token',
        'from_user_id',
        'to_user_id',
        'amount',
        'memo',
        'otp_hash',
        'status',
        'expires_at',
        'transaction_id',
        'request_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
