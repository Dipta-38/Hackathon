@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4">Send Money</h2>
                <form method="POST" action="{{ route('transfer.store') }}" class="recipient-preview-form">
                    @csrf
                    <div class="mb-3">
                        <label for="to_user_id" class="form-label">Recipient account number or user ID</label>
                        <input type="text" name="to_user_id" id="to_user_id" class="form-control form-control-lg" placeholder="ACC-000001 or 12" required>
                        <div class="recipient-preview alert alert-light border mt-2 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" class="form-control form-control-lg amount-input" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="memo" class="form-label">Memo</label>
                        <textarea name="memo" id="memo" class="form-control" rows="3" maxlength="255" placeholder="Optional transfer description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">{{ auth()->user()->otp_receiver_confirmation ? 'Generate receiver OTP' : 'Confirm transfer' }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
