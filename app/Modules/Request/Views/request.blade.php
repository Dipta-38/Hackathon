@extends('auth::layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4">Request payment</h2>

                <form method="POST" action="{{ route('money-request.store') }}" class="recipient-preview-form">
                    @csrf

                    <div class="mb-3">
                        <label for="to_user_id" class="form-label">Sender account number or user ID</label>
                        <input type="text" name="to_user_id" id="to_user_id" class="form-control form-control-lg" placeholder="ACC-000001 or 12" required>
                        <div class="recipient-preview alert alert-light border mt-2 d-none"></div>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" class="form-control form-control-lg" step="0.01" min="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <input type="text" name="message" id="message" class="form-control" maxlength="255" placeholder="Optional request message">
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100">Create request</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
