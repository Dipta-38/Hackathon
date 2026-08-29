@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-3 text-center">Confirm transfer</h2>
                <p class="text-muted text-center mb-4">Enter the 6-digit OTP sent to your email.</p>
                @if(!empty($pendingTransfer['receiver_otp']))
                    <div class="alert alert-warning text-center">
                        <div class="fw-semibold">Receiver OTP</div>
                        <div id="receiverOtp" class="display-6 fw-bold my-2">{{ $pendingTransfer['receiver_otp'] }}</div>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="copyReceiverOtp">Copy receiver OTP</button>
                        <div class="small mt-2">Verify your email first, then tell this OTP to the receiver.</div>
                    </div>
                @endif
                <form method="POST" action="{{ route('transfer.store') }}">
                    @csrf
                    <input type="hidden" name="to_user_id" value="{{ $pendingTransfer['to_user_id'] }}">
                    <input type="hidden" name="amount" value="{{ $pendingTransfer['amount'] }}">
                    <input type="hidden" name="memo" value="{{ $pendingTransfer['memo'] ?? '' }}">
                    <div class="mb-4">
                        <label for="otp" class="form-label">Transfer OTP</label>
                        <input type="text" name="otp" id="otp" class="form-control form-control-lg text-center" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Complete transfer</button>
                </form>
                <a href="{{ route('send.money') }}" class="btn btn-link w-100 mt-2">Cancel transfer</a>
            </div>
        </div>
    </div>
</div>
@if(!empty($pendingTransfer['receiver_otp']))
<script>
    const copyReceiverOtp = async () => {
        try {
            await navigator.clipboard.writeText(document.getElementById('receiverOtp').textContent.trim());
            document.getElementById('copyReceiverOtp').textContent = 'Copied';
        } catch (error) {
            document.getElementById('copyReceiverOtp').textContent = 'Copy OTP';
        }
    };
    document.getElementById('copyReceiverOtp').addEventListener('click', copyReceiverOtp);
    copyReceiverOtp();
</script>
@endif
@endsection