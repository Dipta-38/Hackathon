@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-3">Transfer safety settings</h2>
                <p class="text-muted mb-4">Enable receiver OTP confirmation when the receiver must approve each transfer.</p>

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf

                    <div class="border rounded-4 p-3 mb-4">
                        <div class="form-check form-switch d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-check-label fw-semibold" for="otp_receiver_confirmation">Receiver OTP Confirmation</label>
                                <div class="text-muted small">Generate an OTP before sending. The sender verifies by email, then the receiver enters the OTP before funds move.</div>
                            </div>
                            <input class="form-check-input ms-3" type="checkbox" role="switch" id="otp_receiver_confirmation" name="otp_receiver_confirmation" value="1" {{ old('otp_receiver_confirmation', auth()->user()->otp_receiver_confirmation) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="alert alert-light border">
                        <strong>Direct send mode:</strong> when receiver OTP confirmation is off, transfers proceed after sender email verification.
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">Save settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
