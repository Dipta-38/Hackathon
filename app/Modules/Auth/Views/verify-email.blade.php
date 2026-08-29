@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4 text-center">Verify Email</h2>
                <form method="POST" action="{{ route('verify.email.submit') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
                    <div class="mb-4">
                        <label for="otp" class="form-label">Verification OTP</label>
                        <input type="text" name="otp" id="otp" class="form-control" inputmode="numeric" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Verify account</button>
                </form>
                <form method="POST" action="{{ route('verify.email.resend') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
                    <button type="submit" class="btn btn-outline-secondary w-100">Resend OTP</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
