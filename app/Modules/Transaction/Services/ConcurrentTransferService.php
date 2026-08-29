<?php

namespace App\Modules\Transaction\Services;

use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\BalanceHistory;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\TransactionAttempt;
use App\Notifications\WalletEventNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ConcurrentTransferService
{
    private $lockTTL = 10; // seconds
    private $maxRetries = 3;
    private $maxAttempts = 5;
    
    /**
     * ✅ ACID-compliant transfer with full concurrency protection
     * Handles: Race conditions, Deadlocks, Network failures, Double spending
     */
    public function transfer($fromUserId, $toUserId, $amount, $idempotencyKey, $metadata = [])
    {
        $idempotencyKey = $this->normalizeIdempotencyKey((string) $idempotencyKey);
        $startTime = microtime(true);
        $attempt = 0;
        
        while ($attempt < $this->maxAttempts) {
            $attempt++;
            
            try {
                // ✅ 1. Validate inputs
                $this->validateTransfer($fromUserId, $toUserId, $amount);
                
                // ✅ 2. Idempotency Check - Fast path
                if ($cached = $this->getIdempotentResult($idempotencyKey)) {
                    Log::info('Idempotent request', ['key' => $idempotencyKey]);
                    return $cached;
                }
                
                // ✅ 3. Distributed Locks - Prevent deadlocks
                $lockKeys = ["transfer:{$fromUserId}", "transfer:{$toUserId}"];
                sort($lockKeys); // Always acquire in same order
                $locks = $this->acquireLocks($lockKeys);
                
                try {
                    // ✅ 4. Database Transaction with ACID
                    $result = DB::transaction(function() use ($fromUserId, $toUserId, $amount, $idempotencyKey, $metadata, $attempt) {
                        
                        // ✅ 5. Pessimistic Row Locks
                        $fromAccount = Account::where('user_id', $fromUserId)
                            ->lockForUpdate()
                            ->first();
                            
                        $toAccount = Account::where('user_id', $toUserId)
                            ->lockForUpdate()
                            ->first();
                        
                        if (!$fromAccount || !$toAccount) {
                            throw new \Exception('Account not found', 404);
                        }
                        
                        // ✅ 6. Check Balance with Concurrency
                        $available = $fromAccount->balance - $fromAccount->reserved_balance;
                        if ($available < $amount) {
                            throw new \Exception('Insufficient balance', 422);
                        }
                        
                        // ✅ 7. Reserve Amount - Prevent double spending
                        $reservationId = $this->reservationId($idempotencyKey);
                        $fromAccount->reserveBalance($amount, $reservationId);
                        
                        // ✅ 8. Update with Optimistic Locking
                        $fromAccount->updateBalanceOptimistic($amount, 'debit');
                        $toAccount->updateBalanceOptimistic($amount, 'credit');
                        
                        // ✅ 9. Release Reservation
                        $fromAccount->releaseReservation($reservationId);
                        
                        // ✅ 10. Create Transaction
                        $transaction = Transaction::create([
                            'from_user_id' => $fromUserId,
                            'to_user_id' => $toUserId,
                            'amount' => $amount,
                            'status' => 'completed',
                            'idempotency_key' => $idempotencyKey,
                            'metadata' => json_encode(array_merge($metadata, [
                                'from_balance_before' => $fromAccount->getOriginal('balance'),
                                'to_balance_before' => $toAccount->getOriginal('balance'),
                                'from_version' => $fromAccount->version,
                                'to_version' => $toAccount->version,
                            ])),
                            'processed_at' => now(),
                            'attempts' => $attempt,
                        ]);
                        
                        return $transaction;
                        
                    }, $this->maxRetries); // ✅ Retry on deadlock
                    
                    // ✅ 11. Cache Idempotency
                    $this->cacheIdempotentResult($idempotencyKey, $result);
                    $this->forgetReadCaches($fromUserId, $toUserId);
                    
                    // ✅ 12. Release Locks
                    $this->releaseLocks($locks);
                    
                    // ✅ 13. Log Success
                    $duration = (microtime(true) - $startTime) * 1000;
                    Log::info('Transfer completed', [
                        'idempotency_key' => $idempotencyKey,
                        'from_user' => $fromUserId,
                        'to_user' => $toUserId,
                        'amount' => $amount,
                        'duration_ms' => $duration,
                        'attempts' => $attempt,
                    ]);

                    $result->load(['fromUser', 'toUser']);
                    $result->fromUser->notify(new WalletEventNotification(
                        'Money sent',
                        'Your transfer of ৳' . number_format((float) $result->amount, 2) . ' to ' . $result->toUser->name . ' was completed.',
                        'success'
                    ));
                    $result->toUser->notify(new WalletEventNotification(
                        'Money received',
                        'You received ৳' . number_format((float) $result->amount, 2) . ' from ' . $result->fromUser->name . '.',
                        'success'
                    ));
                    
                    return $result;
                    
                } catch (\Exception $e) {
                    // ✅ Release locks on error
                    $this->releaseLocks($locks);
                    throw $e;
                }
                
            } catch (\Exception $e) {
                // ✅ Log attempt failure
                Log::warning('Transfer attempt failed', [
                    'idempotency_key' => $idempotencyKey,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                
                // ✅ Record attempt for debugging
                TransactionAttempt::create([
                    'idempotency_key' => $idempotencyKey,
                    'from_user_id' => $fromUserId,
                    'to_user_id' => $toUserId,
                    'amount' => $amount,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'attempted_at' => now(),
                ]);
                
                // ✅ Don't retry on validation errors
                if (in_array($e->getCode(), [404, 422, 400])) {
                    throw $e;
                }
                
                // ✅ Exponential backoff before retry
                if ($attempt < $this->maxAttempts) {
                    $sleep = 100000 * pow(2, $attempt); // 200ms, 400ms, 800ms, 1600ms, 3200ms
                    usleep($sleep);
                }
            }
        }
        
        // ✅ All retries exhausted
        Log::error('All transfer attempts failed', [
            'idempotency_key' => $idempotencyKey,
            'from_user' => $fromUserId,
            'to_user' => $toUserId,
            'amount' => $amount,
            'attempts' => $attempt,
        ]);
        
        throw new \Exception('Transfer failed after ' . $this->maxAttempts . ' attempts');
    }
    
    /**
     * ✅ Validate transfer inputs
     */
    private function validateTransfer($fromUserId, $toUserId, $amount)
    {
        if (! is_numeric($amount)) {
            throw new \Exception('Transfer amount must be numeric.', 422);
        }

        if ($fromUserId === $toUserId) {
            throw new \Exception('Cannot transfer to yourself', 422);
        }
        
        if ((float) $amount <= 0) {
            throw new \Exception('Amount must be greater than zero', 422);
        }
        
        if ((float) $amount > 100000000) {
            throw new \Exception('Amount exceeds maximum limit', 422);
        }

        $fromAccount = Account::where('user_id', $fromUserId)->first();
        if (! $fromAccount) {
            throw new \Exception('Sender account not found', 404);
        }

        if ((float) $fromAccount->getAvailableBalance() < (float) $amount) {
            throw new \Exception('Insufficient available balance', 422);
        }
    }

    private function normalizeIdempotencyKey(string $idempotencyKey): string
    {
        return strlen($idempotencyKey) <= 64
            ? $idempotencyKey
            : hash('sha256', $idempotencyKey);
    }

    private function reservationId(string $idempotencyKey): string
    {
        return 'transfer_' . substr(hash('sha256', $idempotencyKey), 0, 55);
    }
    
    /**
     * ✅ Acquire locks in a simple, reliable local-safe way.
     * The database row lock is the real concurrency guard; Redis is optional.
     */
    private function acquireLocks(array $keys)
    {
        if (!extension_loaded('redis') || !class_exists('Redis')) {
            return [];
        }

        $acquired = [];
        $timeout = 5; // seconds

        foreach ($keys as $key) {
            $lockKey = "lock:{$key}";
            $lockValue = Str::random(32);
            $start = time();

            while (time() - $start < $timeout) {
                $client = new \Redis();
                $client->connect(env('REDIS_HOST', '127.0.0.1'), (int) env('REDIS_PORT', 6379));
                $result = $client->set($lockKey, $lockValue, ['nx', 'ex' => $this->lockTTL]);

                if ($result) {
                    $acquired[$key] = $lockValue;
                    break;
                }

                usleep(100000);
            }

            if (!isset($acquired[$key])) {
                $this->releaseLocks($acquired);
                throw new \Exception('Could not acquire lock, please retry', 409);
            }
        }

        return $acquired;
    }

    /**
     * ✅ Release locks atomically if Redis is available.
     */
    private function releaseLocks($locks)
    {
        if (empty($locks) || !extension_loaded('redis') || !class_exists('Redis')) {
            return;
        }

        $client = new \Redis();
        $client->connect(env('REDIS_HOST', '127.0.0.1'), (int) env('REDIS_PORT', 6379));

        foreach ($locks as $key => $value) {
            $lockKey = "lock:{$key}";
            if ($client->get($lockKey) === $value) {
                $client->del($lockKey);
            }
        }
    }

    /**
     * ✅ Idempotency - Prevent duplicates using cache or DB as fallback.
     */
    private function getIdempotentResult($key)
    {
        $cached = Cache::get("idempotent:{$key}");
        if ($cached) {
            $payload = is_array($cached) ? $cached : json_decode($cached, true);

            if (is_array($payload) && isset($payload['id'])) {
                return Transaction::find($payload['id']);
            }
        }

        $transaction = Transaction::where('idempotency_key', $key)->first();
        if ($transaction) {
            Cache::put("idempotent:{$key}", json_encode($transaction->toArray()), now()->addDay());
            return $transaction;
        }

        return null;
    }

    private function cacheIdempotentResult($key, $result)
    {
        Cache::put("idempotent:{$key}", json_encode($result->toArray()), now()->addDay());
    }

    private function forgetReadCaches(int $fromUserId, int $toUserId): void
    {
        foreach ([$fromUserId, $toUserId] as $userId) {
            Cache::forget("dashboard:{$userId}:transactions");
            Cache::forget("dashboard:{$userId}:pending_requests");
            Cache::forget("dashboard:{$userId}:completed_transfers");
            Cache::forget("account_balance:{$userId}");
            Cache::forget("account_available:{$userId}");
        }
    }
}