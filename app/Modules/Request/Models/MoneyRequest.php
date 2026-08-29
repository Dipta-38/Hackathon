<?php

namespace App\Modules\Request\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class MoneyRequest extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'amount',
        'message',
        'status',
        'idempotency_key',
        'transaction_id',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'version'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'version' => 'integer',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PROCESSING = 'processing';
    
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