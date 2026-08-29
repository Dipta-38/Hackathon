<?php

namespace App\Modules\Transaction\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'amount',
        'status',
        'idempotency_key',
        'metadata',
        'processed_at',
        'version',
        'attempts'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'version' => 'integer',
        'attempts' => 'integer',
    ];
    
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
    
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
    
    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}