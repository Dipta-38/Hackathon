<?php

namespace App\Modules\Transaction\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\ReceiverTransferConfirmation;
use App\Modules\Transaction\Services\ConcurrentTransferService;
use App\Modules\Auth\Models\User;
use App\Notifications\WalletEventNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class TransferController extends Controller
{
    public function __construct(protected ConcurrentTransferService $transferService)
    {
    }

    public function create()
    {
        return view('transaction::transfer');
    }

    public function sendMoneyPage()
    {
        return view('transaction::send-money');
    }

    public function verify(Request $request)
    {
        $pendingTransfer = $request->session()->get('pending_transfer');

        if (! $pendingTransfer) {
            return redirect()->route('send.money')->with('error', 'There is no pending transfer to verify.');
        }

        return view('transaction::verify-transfer', compact('pendingTransfer'));
    }

    public function recipientPreview(Request $request)
    {
        $userId = $this->resolveTargetUserId($request->input('to_user_id'));
        $user = $userId ? Cache::remember("recipient_preview:{$userId}", 60, function () use ($userId) {
            return User::with('account')->find($userId);
        }) : null;

        if (! $user || $user->id === Auth::id()) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'name' => $user->name,
            'photo_url' => $user->profilePhotoUrl(),
            'account_number' => $user->account ? sprintf('ACC-%06d', $user->account->id) : null,
        ]);
    }

    public function history()
    {
        $user = Auth::user();

        $transactions = Transaction::with(['fromUser', 'toUser'])
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->latest()
            ->paginate(20);

        return view('transaction::history', compact('transactions'));
    }

    public function clearHistory()
    {
        $userId = Auth::id();

        Transaction::where(function ($query) use ($userId) {
            $query->where('from_user_id', $userId)
                ->orWhere('to_user_id', $userId);
        })->delete();
        Cache::forget("dashboard:{$userId}:transactions");

        return redirect()->route('transaction.history')->with('success', 'Your transaction history has been cleared.');
    }

    public function receipt($id)
    {
        $transaction = Transaction::with(['fromUser', 'toUser'])->findOrFail($id);

        if ($transaction->from_user_id !== Auth::id() && $transaction->to_user_id !== Auth::id()) {
            abort(403);
        }

        $receiptId = 'TXN-' . str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
        $date = $transaction->processed_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A');
        $metadata = $transaction->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }
        $memo = is_array($metadata) && filled($metadata['memo'] ?? null)
            ? $metadata['memo']
            : 'No memo provided';

        $pdf = Pdf::loadView('transaction::receipt', [
            'transaction' => $transaction,
            'receiptId' => $receiptId,
            'date' => $date,
            'memo' => $memo,
        ]);

        return $pdf->download("receipt-{$receiptId}.pdf");
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
            'memo' => ['nullable', 'string', 'max:255'],
            'otp' => ['nullable', 'digits:6'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $validated['to_user_id'] = $resolvedReceiverId;

        $fromUserId = Auth::id();

        if ((int) $validated['to_user_id'] === $fromUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send money to yourself.',
            ], 422);
        }

        $account = \App\Modules\Account\Models\Account::where('user_id', $fromUserId)->first();
        if (! $account || (float) $account->getAvailableBalance() < (float) $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient available balance for this transfer.',
            ], 422);
        }

        $sender = Auth::user();
        $pendingTransfer = $request->session()->get('pending_transfer');
        if ($sender->otp_receiver_confirmation && (! $pendingTransfer || ($pendingTransfer['mode'] ?? null) !== 'receiver_confirmation')) {
            $otp = (string) random_int(100000, 999999);
            $confirmation = ReceiverTransferConfirmation::create([
                'token' => bin2hex(random_bytes(32)),
                'from_user_id' => $fromUserId,
                'to_user_id' => (int) $validated['to_user_id'],
                'amount' => $validated['amount'],
                'memo' => $validated['memo'] ?? null,
                'otp_hash' => hash('sha256', $otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            $receiver = User::findOrFail($validated['to_user_id']);
            $emailOtp = (string) random_int(100000, 999999);
            $pendingTransfer = [
                'mode' => 'receiver_confirmation',
                'to_user_id' => (int) $validated['to_user_id'],
                'amount' => (float) $validated['amount'],
                'memo' => $validated['memo'] ?? null,
                'otp' => $emailOtp,
                'receiver_otp' => $otp,
                'confirmation_id' => $confirmation->id,
                'idempotency_key' => $validated['idempotency_key'] ?? 'transfer_' . uniqid(),
            ];
            $request->session()->put('pending_transfer', $pendingTransfer);

            Mail::raw("Your transfer email verification OTP is: {$emailOtp}. It expires in 5 minutes.", function ($message) use ($sender) {
                $message->to($sender->email)->subject('Transfer email verification - MyMoney');
            });

            if (! $request->expectsJson()) {
                return redirect()->route('transfer.verify');
            }

            return response()->json([
                'success' => false,
                'requires_otp' => true,
                'requires_receiver_otp' => true,
                'message' => 'Receiver OTP generated and copied on the confirmation page. Verify your email before sharing it with the receiver.',
            ], 428);
        }

        if (! isset($validated['otp']) || empty($validated['otp'])) {
            $otp = (string) random_int(100000, 999999);
            $request->session()->put('pending_transfer', [
                'to_user_id' => (int) $validated['to_user_id'],
                'amount' => (float) $validated['amount'],
                'memo' => $validated['memo'] ?? null,
                'otp' => $otp,
                'idempotency_key' => $validated['idempotency_key'] ?? 'transfer_' . uniqid(),
            ]);

            Mail::raw("Your transfer confirmation OTP is: {$otp}. It expires in 5 minutes.", function ($message) {
                $message->to(Auth::user()->email)->subject('Transfer confirmation - MyMoney');
            });

            if (! $request->expectsJson()) {
                return redirect()->route('transfer.verify');
            }

            return response()->json([
                'success' => false,
                'requires_otp' => true,
                'message' => 'Confirmation OTP sent to your email. Please submit the code to complete the transfer.',
            ], 428);
        }

        if (! $pendingTransfer || $pendingTransfer['otp'] !== $validated['otp']) {
            if (! $request->expectsJson()) {
                return redirect()->route('transfer.verify')->with('error', 'Invalid or expired transfer confirmation OTP.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired transfer confirmation OTP.',
            ], 422);
        }

        if (($pendingTransfer['mode'] ?? null) === 'receiver_confirmation') {
            $confirmation = ReceiverTransferConfirmation::find($pendingTransfer['confirmation_id']);
            if (! $confirmation || $confirmation->status !== 'pending' || $confirmation->expires_at->isPast()) {
                $request->session()->forget('pending_transfer');

                return redirect()->route('send.money')->with('error', 'The receiver confirmation has expired. Please start again.');
            }

            $receiver = User::findOrFail($confirmation->to_user_id);
            $receiver->notify(new WalletEventNotification(
                'Transfer awaiting your confirmation',
                $sender->name . ' wants to send you ৳' . number_format((float) $confirmation->amount, 2) . '. Ask the sender for the OTP to receive it.',
                'warning',
                [
                    'action_type' => 'receiver_transfer',
                    'action_url' => route('transfer.receiver-confirm', $confirmation->token),
                    'sender_name' => $sender->name,
                    'sender_photo_url' => $sender->profilePhotoUrl(),
                    'amount' => (string) $confirmation->amount,
                    'transfer_token' => $confirmation->token,
                ]
            ));
            $request->session()->forget('pending_transfer');

            if (! $request->expectsJson()) {
                return redirect()->route('dashboard')->with('success', 'Email verified. Tell the receiver the OTP shown on the confirmation page.');
            }

            return response()->json([
                'success' => true,
                'requires_receiver_otp' => true,
                'message' => 'Email verified. Tell the receiver the generated OTP to release the transfer.',
            ]);
        }

        $transaction = $this->transferService->transfer(
            $fromUserId,
            (int) $validated['to_user_id'],
            (float) $validated['amount'],
            $pendingTransfer['idempotency_key'],
            ['memo' => $pendingTransfer['memo'] ?? null]
        );

        $request->session()->forget('pending_transfer');

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('success', 'Transfer completed successfully.');
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction->load(['fromUser', 'toUser']),
        ]);
    }

    public function confirmReceiverTransfer(Request $request, string $token)
    {
        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $confirmation = ReceiverTransferConfirmation::where('token', $token)->first();

        if (! $confirmation || $confirmation->to_user_id !== Auth::id()) {
            return back()->with('error', 'This transfer confirmation is not available for your account.');
        }

        if ($confirmation->status !== 'pending' || $confirmation->expires_at->isPast()) {
            return back()->with('error', 'This transfer confirmation has expired or was already used.');
        }

        if (! hash_equals($confirmation->otp_hash, hash('sha256', $validated['otp']))) {
            return back()->with('error', 'The receiver OTP is invalid.');
        }

        $transaction = $this->transferService->transfer(
            $confirmation->from_user_id,
            $confirmation->to_user_id,
            (float) $confirmation->amount,
            'receiver_confirmation_' . $confirmation->token,
            ['memo' => $confirmation->memo, 'receiver_confirmation' => true]
        );

        $confirmation->update(['status' => 'accepted', 'transaction_id' => $transaction->id]);
        if (! empty($confirmation->request_id)) {
            \App\Modules\Request\Models\MoneyRequest::whereKey($confirmation->request_id)->update([
                'status' => \App\Modules\Request\Models\MoneyRequest::STATUS_ACCEPTED,
                'transaction_id' => $transaction->id,
                'accepted_at' => now(),
            ]);
        }
        return redirect()->route('dashboard')->with('success', 'Transfer received successfully.');
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
}
