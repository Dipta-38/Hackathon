@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4 text-center">Login Verification</h2>
                <p class="text-muted text-center">Login OTP is disabled for this app. Please use your email and password to sign in.</p>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="otp" class="form-label">OTP</label>
                        <input type="text" name="otp" id="otp" class="form-control form-control-lg" inputmode="numeric" maxlength="6" placeholder="Enter OTP if needed" value="{{ old('otp') }}">
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg w-100">Back to login</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
