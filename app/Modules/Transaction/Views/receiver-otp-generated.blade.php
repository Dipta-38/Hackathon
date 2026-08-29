@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5 text-center">
                <h2 class="fw-bold mb-3">Receiver confirmation required</h2>
                <img src="{{ $receiver->profilePhotoUrl() }}" alt="{{ $receiver->name }}" class="rounded-circle border mb-3" width="72" height="72">
                <p class="mb-1">You are sending <strong>৳{{ number_format((float) $confirmation->amount, 2) }}</strong> to <strong>{{ $receiver->name }}</strong>.</p>
                <p class="text-muted">Tell the receiver this OTP. They must enter it from their notification before the money is transferred.</p>
                <div class="display-4 fw-bold text-primary my-4" aria-label="Receiver confirmation OTP">{{ $otp }}</div>
                <p class="small text-muted">This OTP expires in 10 minutes and can be used once.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-primary w-100">Return to dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection
