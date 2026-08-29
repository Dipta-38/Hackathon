<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Models\User;
use App\Modules\Account\Models\Account;
use App\Modules\Request\Models\MoneyRequest;
use App\Modules\Transaction\Models\Transaction;
use App\Notifications\WalletEventNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view("auth::register");
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'dob' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'address' => 'required|string|max:500',
            'nid_no' => 'required|string|max:30|unique:users,nid_no',
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'dob' => $validated['dob'],
            'address' => $validated['address'],
            'nid_no' => $validated['nid_no'],
            'profile_photo_path' => $photoPath,
            'email_verification_otp' => null,
            'email_verification_expires_at' => now(),
            'login_otp' => null,
            'login_otp_expires_at' => null,
            'email_verified_at' => null,
        ]);

        $otp = $user->generateEmailVerificationOtp();
        $this->sendOtpEmail($user, $otp, 'Email verification');
        $request->session()->put('pending_verification_email', $user->email);

        return redirect()->route('verify.email')->with('success', 'Verification OTP has been sent to your email. Please verify before activating your wallet.');
    }

    public function showEmailVerification()
    {
        return view('auth::verify-email', [
            'email' => session('pending_verification_email'),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $email = $request->input('email') ?? session('pending_verification_email');

        $request->validate([
            'email' => ['nullable', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user || $user->email_verification_otp !== $request->otp || $user->email_verification_expires_at < now()) {
            return back()->withErrors(['otp' => 'The OTP is invalid or expired.']);
        }

        $user->email_verified_at = now();
        $user->email_verification_otp = null;
        $user->email_verification_expires_at = null;
        $user->save();
        $request->session()->forget('pending_verification_email');

        Account::create([
            'user_id' => $user->id,
            'balance' => 100000.00,
            'reserved_balance' => 0.00,
            'version' => 0,
        ]);

        $user->notify(new \App\Notifications\WalletEventNotification(
            'Wallet activated',
            'Your email has been verified. Your starter wallet balance of ৳100,000.00 is ready.',
            'success'
        ));

        return redirect()->route('login')->with('success', 'Email verified successfully. You can now log in.');
    }

    public function resendEmailOtp(Request $request)
    {
        $email = $request->input('email') ?? session('pending_verification_email');

        $request->validate([
            'email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $email)->first();
        $otp = $user->generateEmailVerificationOtp();
        $this->sendOtpEmail($user, $otp, 'Email verification');

        return back()->with('success', 'A new verification OTP has been sent.');
    }

    public function showLogin()
    {
        return view("auth::login");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (! $user->email_verified_at) {
            return redirect()->route('verify.email')->with('error', 'Please verify your email address before logging in.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/dashboard')->with('success', 'Login successful.');
    }

    public function showLoginOtp()
    {
        return view('auth::verify-login-otp');
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        return back()->withErrors(['otp' => 'Login OTP is currently disabled. Please log in with your email and password.']);
    }

    public function showSettings()
    {
        return view('auth::settings', [
            'user' => Auth::user(),
        ]);
    }

    public function showProfile()
    {
        return view('auth::profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'dob' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'address' => ['required', 'string', 'max:500'],
            'nid_no' => ['required', 'string', 'max:30', Rule::unique('users', 'nid_no')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['profile_photo_path'] = $request->file('photo')->store('profile-photos', 'public');
        }
        unset($validated['photo']);

        $user->update($validated);
        Cache::forget("recipient_preview:{$user->id}");

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'smart_contact_name_check' => ['nullable', 'boolean'],
            'otp_receiver_confirmation' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $user->update([
            'smart_contact_name_check' => (bool) ($validated['smart_contact_name_check'] ?? false),
            'otp_receiver_confirmation' => (bool) ($validated['otp_receiver_confirmation'] ?? false),
        ]);

        return redirect()->route('dashboard')->with('success', 'Transfer safety settings updated.');
    }

    public function showForgot()
    {
        return view('auth::forgot-password');
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(string $token)
    {
        return view('auth::reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function markNotificationRead(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $account = $user?->account()->first();

        $transactions = Cache::remember("dashboard:{$user->id}:transactions", 15, function () use ($user) {
            return Transaction::with(['fromUser', 'toUser'])
                ->where(function ($query) use ($user) {
                    $query->where('from_user_id', $user->id)
                        ->orWhere('to_user_id', $user->id);
                })->latest()->take(10)->get();
        });

        $pendingRequests = Cache::remember("dashboard:{$user->id}:pending_requests", 15, function () use ($user) {
            return MoneyRequest::where('to_user_id', $user->id)
                ->where('status', MoneyRequest::STATUS_PENDING)
                ->count();
        });

        $completedTransfers = Cache::remember("dashboard:{$user->id}:completed_transfers", 15, function () use ($user) {
            return Transaction::where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })->where('status', 'completed')->count();
        });

        return view('auth::dashboard', [
            'user' => $user,
            'account' => $account,
            'account_number' => $account ? sprintf('ACC-%06d', $account->id) : 'N/A',
            'balance' => $account ? $account->balance : 0,
            'available' => $account ? $account->getAvailableBalance() : 0,
            'pendingRequests' => $pendingRequests,
            'completedTransfers' => $completedTransfers,
            'transactions' => $transactions,
        ]);
    }

    protected function sendOtpEmail(User $user, string $otp, string $purpose): void
    {
        Mail::raw("Your {$purpose} OTP is: {$otp}. It expires in 10 minutes.", function ($message) use ($user, $purpose) {
            $message->to($user->email)
                ->subject("{$purpose} - MyMoney");
        });
    }
}