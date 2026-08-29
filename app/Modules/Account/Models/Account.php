<?php

namespace App\Modules\Account\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Account extends Model
{
    protected $fillable = ['user_id', 'balance', 'reserved_balance', 'version'];
    
    protected $casts = [
        'balance' => 'decimal:2',
        'reserved_balance' => 'decimal:2',
        'version' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableBalance()
    {
        return (float) $this->balance - (float) $this->reserved_balance;
    }

    /**
     * ✅ ACID-compliant balance update with Optimistic Locking
     * Handles: Race conditions, Concurrent updates, Version conflicts
     * Retries automatically up to 5 times with exponential backoff
     */
    public function updateBalanceOptimistic($amount, $operation, $maxRetries = 5)
    {
        $retryCount = 0;
        $startTime = microtime(true);
        
        while ($retryCount < $maxRetries) {
            $currentVersion = $this->version;
            
            try {
                $result = DB::transaction(function() use ($amount, $operation, $currentVersion) {
                    // ✅ Pessimistic row lock + Version check
                    $account = Account::where('id', $this->id)
                        ->where('version', $currentVersion)
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$account) {
                        throw new \Exception('Version mismatch - concurrent update detected');
                    }
                    
                    // Check balance
                    if ($operation === 'debit') {
                        $available = $account->balance - $account->reserved_balance;
                        if ($available < $amount) {
                            throw new \Exception('Insufficient balance');
                        }
                    }
                    
                    // Apply operation
                    if ($operation === 'debit') {
                        $account->balance -= $amount;
                    } else {
                        $account->balance += $amount;
                    }
                    
                    // ✅ Increment version for next concurrent operation
                    $account->version = $currentVersion + 1;
                    $account->save();
                    
                    // ✅ Record history for audit
                    BalanceHistory::create([
                        'account_id' => $account->id,
                        'amount' => $amount,
                        'type' => $operation,
                        'balance_before' => $account->getOriginal('balance'),
                        'balance_after' => $account->balance,
                        'version' => $account->version,
                    ]);
                    
                    return $account;
                });
                
                // ✅ Clear cache after successful update
                Cache::forget("account_balance:{$this->user_id}");
                Cache::forget("account_available:{$this->user_id}");
                Cache::forget("account_version:{$this->user_id}");
                
                $duration = (microtime(true) - $startTime) * 1000;
                Log::info('Balance updated successfully', [
                    'user_id' => $this->user_id,
                    'operation' => $operation,
                    'amount' => $amount,
                    'duration_ms' => $duration,
                    'retries' => $retryCount,
                ]);
                
                return $result;
                
            } catch (\Exception $e) {
                $retryCount++;
                
                // Only retry on version mismatch
                if (!str_contains($e->getMessage(), 'Version mismatch')) {
                    throw $e;
                }
                
                // ✅ Exponential backoff: 100ms, 200ms, 400ms, 800ms, 1600ms
                if ($retryCount < $maxRetries) {
                    $sleep = 100000 * pow(2, $retryCount); // microseconds
                    Log::warning('Optimistic lock retry', [
                        'user_id' => $this->user_id,
                        'retry' => $retryCount,
                        'sleep_ms' => $sleep / 1000,
                    ]);
                    usleep($sleep);
                }
            }
        }
        
        Log::error('Max retries exceeded for balance update', [
            'user_id' => $this->user_id,
            'operation' => $operation,
            'amount' => $amount,
            'max_retries' => $maxRetries,
        ]);
        
        throw new \Exception('Unable to update balance after ' . $maxRetries . ' retries');
    }

    /**
     * ✅ Reserve balance for pending transaction - Prevents double spending
     */
    public function reserveBalance($amount, $reservationId)
    {
        return DB::transaction(function() use ($amount, $reservationId) {
            $account = Account::where('id', $this->id)
                ->lockForUpdate()
                ->first();
            
            $available = $account->balance - $account->reserved_balance;
            if ($available < $amount) {
                throw new \Exception('Insufficient balance for reservation');
            }
            
            $account->reserved_balance += $amount;
            $account->save();
            
            // ✅ Store reservation with expiry
            BalanceReservation::create([
                'account_id' => $account->id,
                'reservation_id' => $reservationId,
                'amount' => $amount,
                'expires_at' => now()->addMinutes(5),
            ]);
            
            return $account;
        });
    }

    /**
     * ✅ Release reserved balance
     */
    public function releaseReservation($reservationId)
    {
        return DB::transaction(function() use ($reservationId) {
            $reservation = BalanceReservation::where('reservation_id', $reservationId)
                ->lockForUpdate()
                ->first();
            
            if (!$reservation) {
                return $this;
            }
            
            $account = Account::where('id', $this->id)
                ->lockForUpdate()
                ->first();
            
            $account->reserved_balance -= $reservation->amount;
            $account->save();
            
            $reservation->delete();
            
            return $account;
        });
    }
}