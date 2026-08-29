@extends('auth::layouts.app')

@section('content')
<div class="dashboard-page">
<div class="dashboard-hero row align-items-center mb-4">
    <div class="col-md-8">
        <div class="eyebrow">Personal wallet</div>
        <h1 class="fw-bold mb-1">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="text-muted mb-0">Your money, clearly in view.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="Profile" class="rounded-circle border-2 border-primary" width="56" height="56">
    </div>
</div>

<div class="dashboard-stats row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Account Number</div>
                <h4 class="mt-2 fw-bold">{{ $account_number ?? 'N/A' }}</h4>
                <small class="text-muted">Wallet ID for transfers</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Available balance</div>
                <h3 class="mt-2 fw-bold">৳{{ number_format((float) $available, 2) }}</h3>
                <small class="text-muted">Total wallet: ৳{{ number_format((float) $balance, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Pending requests</div>
                <h3 class="mt-2 fw-bold">{{ $pendingRequests }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Completed transfers</div>
                <h3 class="mt-2 fw-bold">{{ $completedTransfers }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-actions row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="mb-0 fw-bold">Send money</h4>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST" action="{{ route('transfer.store') }}" class="recipient-preview-form">
                    @csrf
                    <div class="mb-3">
                        <label for="to_user_id" class="form-label">Recipient account number or user ID</label>
                        <input type="text" name="to_user_id" id="to_user_id" class="form-control" placeholder="ACC-000001 or 12" required>
                        <div class="recipient-preview alert alert-light border mt-2 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="amount" class="form-control amount-input" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="memo" class="form-label">Memo</label>
                        <input type="text" name="memo" id="memo" class="form-control" maxlength="255" placeholder="Optional note">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ auth()->user()->otp_receiver_confirmation ? 'Generate receiver OTP' : 'Transfer funds' }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="mb-0 fw-bold">Request money</h4>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST" action="{{ route('money-request.store') }}" class="recipient-preview-form">
                    @csrf
                    <div class="mb-3">
                        <label for="request_to_user_id" class="form-label">Requester account number or user ID</label>
                        <input type="text" name="to_user_id" id="request_to_user_id" class="form-control" placeholder="ACC-000001 or 12" required>
                        <div class="recipient-preview alert alert-light border mt-2 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label for="request_amount" class="form-label">Amount</label>
                        <input type="number" name="amount" id="request_amount" class="form-control amount-input" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <input type="text" name="message" id="message" class="form-control" maxlength="255" placeholder="Optional request note">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Request payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 mt-4">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold">Recent transactions</h4>
        <a href="{{ route('transaction.history') }}" class="btn btn-sm btn-outline-primary">View all</a>
    </div>
    <div class="card-body px-4 pb-4">
        @if($transactions->isEmpty())
            <p class="text-muted mb-0">No transactions yet.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Counterparty</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                $isOutgoing = $transaction->from_user_id === auth()->id();
                                $counterparty = $isOutgoing ? $transaction->toUser->name ?? 'User #' . $transaction->to_user_id : $transaction->fromUser->name ?? 'User #' . $transaction->from_user_id;
                                $amount = $isOutgoing ? -$transaction->amount : $transaction->amount;
                            @endphp
                            <tr>
                                <td>{{ $isOutgoing ? 'Sent' : 'Received' }}</td>
                                <td>{{ $counterparty }}</td>
                                <td class="fw-semibold {{ $isOutgoing ? 'text-danger' : 'text-success' }}">{{ $isOutgoing ? '-' : '+' }}৳{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ ucfirst($transaction->status) }}</span></td>
                                <td><a href="{{ route('transaction.receipt', $transaction->id) }}" class="btn btn-sm btn-outline-secondary">PDF</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</div>
@endsection
