<?php

namespace App\Modules\Request\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Request\Services\MoneyRequestService;
use App\Modules\Request\Models\MoneyRequest;
use App\Modules\Transaction\Models\ReceiverTransferConfirmation;
use App\Notifications\WalletEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class MoneyRequestController extends Controller
{
    public function __construct(protected MoneyRequestService $moneyRequestService)
    {
    }

    public function create()
    {
        return view('request::request');
    }

    public function requestMoneyPage()
    {
        return view('request::request-money');
    }

    public function store(Request $request)
    {
        $recipientInput = $request->input('to_user_id');
        $resolvedReceiverId = $this->resolveTargetUserId($recipientInput);

        if (! $resolvedReceiverId) {
            return response()->json([
                'success' => false,
                'message' => 'The selected account number is invalid.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['to_user_id'] = $resolvedReceiverId;

        if ((int) $validated['to_user_id'] === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot request money from yourself.',
            ], 422);
        }

        $requestModel = $this->moneyRequestService->request(
            Auth::id(),
            (int) $validated['to_user_id'],
            (float) $validated['amount'],
            $validated['message'] ?? null
        );

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('success', 'Money request sent successfully.');
        }

        return response()->json([
            'success' => true,
            'request' => $requestModel,
        ]);
    }

    private function resolveTargetUserId($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $user = \App\Modules\Auth\Models\User::find((int) $value);
            return $user ? $user->id : null;
        }

        if (is_string($value) && preg_match('/^ACC[- ]?(\d+)$/i', trim($value), $matches)) {
            $accountId = (int) $matches[1];
            $account = \App\Modules\Account\Models\Account::find($accountId);
            return $account ? $account->user_id : null;
        }

        return null;
    }

    public function accept(Request $request, $id)
    {
        $requestModel = MoneyRequest::where('id', $id)
            ->where('to_user_id', Auth::id())
            ->where('status', MoneyRequest::STATUS_PENDING)
            ->firstOrFail();

        if (Auth::user()->otp_receiver_confirmation) {
            $receiverOtp = (string) random_int(100000, 999999);
            $emailOtp = (string) random_int(100000, 999999);
            $confirmation = ReceiverTransferConfirmation::create([
                'token' => bin2hex(random_bytes(32)),
                'from_user_id' => Auth::id(),
                'to_user_id' => $requestModel->from_user_id,
                'amount' => $requestModel->amount,
                'memo' => $requestModel->message,
                'otp_hash' => hash('sha256', $receiverOtp),
                'status' => 'pending',
                'expires_at' => now()->addMinutes(10),
                'request_id' => $requestModel->id,
            ]);

            $request->session()->put('pending_transfer', [
                'mode' => 'receiver_confirmation',
                'to_user_id' => $requestModel->from_user_id,
                'amount' => (float) $requestModel->amount,
                'memo' => $requestModel->message,
                'otp' => $emailOtp,
                'receiver_otp' => $receiverOtp,
                'confirmation_id' => $confirmation->id,
                'request_id' => $requestModel->id,
                'idempotency_key' => 'request_' . $requestModel->id . '_' . bin2hex(random_bytes(16)),
            ]);

            Mail::raw("Your money request payment email verification OTP is: {$emailOtp}. It expires in 5 minutes.", function ($message) {
                $message->to(Auth::user()->email)->subject('Money request payment verification - MyMoney');
            });

            if (! $request->expectsJson()) {
                return redirect()->route('transfer.verify');
            }

            return response()->json([
                'success' => false,
                'requires_otp' => true,
                'requires_receiver_otp' => true,
                'message' => 'Receiver OTP generated. Verify your email before sharing it with the requester.',
            ], 428);
        }

        $transaction = $this->moneyRequestService->accept((int) $id, Auth::id());
        if (! request()->expectsJson()) {
            return redirect()->route('dashboard')->with('success', 'Money request accepted and payment sent.');
        }
        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    public function reject($id)
    {
        $requestModel = $this->moneyRequestService->reject((int) $id, Auth::id());
        Cache::forget('dashboard:' . Auth::id() . ':pending_requests');
        $requestModel->fromUser->notify(new WalletEventNotification(
            'Money request declined',
            Auth::user()->name . ' declined your money request for ৳' . number_format((float) $requestModel->amount, 2) . '.',
            'info'
        ));

        if (! request()->expectsJson()) {
            return redirect()->route('dashboard')->with('success', 'Money request declined.');
        }
        return response()->json([
            'success' => true,
            'request' => $requestModel,
        ]);
    }
}
