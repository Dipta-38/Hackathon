<?php

namespace App\Modules\Request\Services;

use App\Modules\Request\Models\MoneyRequest;
use App\Modules\Transaction\Services\ConcurrentTransferService;
use App\Notifications\WalletEventNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MoneyRequestService
{
    private $transferService;
    private $maxPendingRequests = 50;
    private $expiryHours = 24;
    
    public function __construct(ConcurrentTransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    
    /**
     * ✅ Request money with concurrency protection
     */
    public function request($fromUserId, $toUserId, $amount, $message = null)
    {
        // ✅ Validate
        if ($fromUserId === $toUserId) {
            throw new \Exception('Cannot request money from yourself', 422);
        }
        
        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than zero', 422);
        }
        
        // ✅ Check pending request limit
        $pendingCount = MoneyRequest::where('from_user_id', $fromUserId)
            ->where('status', MoneyRequest::STATUS_PENDING)
            ->count();
            
        if ($pendingCount >= $this->maxPendingRequests) {
            throw new \Exception('Too many pending requests (max ' . $this->maxPendingRequests . ')', 429);
        }
        
        // ✅ Create request with idempotency
        $idempotencyKey = Str::uuid()->toString();
        
        return DB::transaction(function() use ($fromUserId, $toUserId, $amount, $message, $idempotencyKey) {
            
            $request = MoneyRequest::create([
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'message' => $message,
                'status' => MoneyRequest::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addHours($this->expiryHours),
                'version' => 0,
            ]);
            
            $request->load(['fromUser', 'toUser']);
            $request->toUser->notify(new WalletEventNotification(
                'Money request received',
                $request->fromUser->name . ' requested ৳' . number_format((float) $request->amount, 2) . ($message ? ': ' . $message : '.'),
                'info',
                [
                    'action_type' => 'money_request',
                    'action_url' => route('money-request.accept', $request->id),
                    'sender_name' => $request->fromUser->name,
                    'sender_photo_url' => $request->fromUser->profilePhotoUrl(),
                    'sender_account_number' => $request->fromUser->account
                        ? sprintf('ACC-%06d', $request->fromUser->account->id)
                        : null,
                    'receiver_otp_enabled' => (bool) $request->toUser->otp_receiver_confirmation,
                    'amount' => (string) $request->amount,
                    'request_id' => $request->id,
                ]
            ));
            Cache::forget("dashboard:{$toUserId}:pending_requests");

            Log::info('Money request created', [
                'request_id' => $request->id,
                'from_user' => $fromUserId,
                'to_user' => $toUserId,
                'amount' => $amount,
            ]);
            
            return $request;
            
        }, 3);
    }
    
    /**
     * ✅ Accept request - Converts to transfer with concurrency
     */
    public function accept($requestId, $userId)
    {
        // ✅ Get request with lock
        $request = MoneyRequest::where('id', $requestId)
            ->where('to_user_id', $userId)
            ->lockForUpdate()
            ->first();
            
        if (!$request) {
            throw new \Exception('Request not found', 404);
        }
        
        // ✅ Validate request status
        if ($request->status !== MoneyRequest::STATUS_PENDING) {
            throw new \Exception('Request already processed', 422);
        }
        
        // ✅ Check expiry
        if ($request->expires_at < now()) {
            $request->status = MoneyRequest::STATUS_EXPIRED;
            $request->save();
            throw new \Exception('Request has expired', 422);
        }
        
        // ✅ Update request with optimistic locking
        $version = $request->version;
        $updated = MoneyRequest::where('id', $requestId)
            ->where('version', $version)
            ->update([
                'status' => MoneyRequest::STATUS_PROCESSING,
                'version' => $version + 1,
            ]);
            
        if (!$updated) {
            throw new \Exception('Request version mismatch - retry', 409);
        }
        
        try {
            // ✅ Process transfer (concurrent)
            $idempotencyKey = "accept_request_{$requestId}_" . Str::uuid()->toString();
            
            $transaction = $this->transferService->transfer(
                $userId, // Payer (request receiver)
                $request->from_user_id, // Receiver (request sender)
                $request->amount,
                $idempotencyKey,
                ['request_id' => $requestId]
            );
            
            // ✅ Update request with result
            MoneyRequest::where('id', $requestId)
                ->update([
                    'status' => MoneyRequest::STATUS_ACCEPTED,
                    'transaction_id' => $transaction->id,
                    'accepted_at' => now(),
                    'version' => $version + 2,
                ]);
            
            Log::info('Money request accepted', [
                'request_id' => $requestId,
                'user' => $userId,
                'transaction_id' => $transaction->id,
            ]);
            
            return $transaction;
            
        } catch (\Exception $e) {
            // ✅ Rollback request status on failure
            MoneyRequest::where('id', $requestId)
                ->update([
                    'status' => MoneyRequest::STATUS_PENDING,
                    'version' => DB::raw('version - 1'),
                ]);
                
            throw $e;
        }
    }
    
    /**
     * ✅ Reject request
     */
    public function reject($requestId, $userId)
    {
        $request = MoneyRequest::where('id', $requestId)
            ->where('to_user_id', $userId)
            ->lockForUpdate()
            ->first();
            
        if (!$request) {
            throw new \Exception('Request not found', 404);
        }
        
        if ($request->status !== MoneyRequest::STATUS_PENDING) {
            throw new \Exception('Request already processed', 422);
        }
        
        $request->status = MoneyRequest::STATUS_REJECTED;
        $request->rejected_at = now();
        $request->save();
        
        Log::info('Money request rejected', [
            'request_id' => $requestId,
            'user' => $userId,
        ]);
        
        return $request;
    }
}