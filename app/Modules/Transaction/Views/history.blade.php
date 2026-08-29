@extends('auth::layouts.app')

@section('content')
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center gap-3">
        <h3 class="mb-0 fw-bold">Transaction history</h3>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('transaction.clear') }}" onsubmit="return confirm('Clear all of your transaction history? This cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">Clear all</button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
    </div>
    <div class="card-body px-4 pb-4">
        @if($transactions->isEmpty())
            <p class="text-muted mb-0">No transaction history found.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                $isOutgoing = $transaction->from_user_id === auth()->id();
                            @endphp
                            <tr>
                                <td><span class="fw-semibold">{{ $isOutgoing ? 'Sent' : 'Received' }}</span><br><small class="text-muted">BDT {{ number_format((float) $transaction->amount, 2) }}</small></td>
                                <td>{{ $transaction->fromUser->name ?? 'User #' . $transaction->from_user_id }}</td>
                                <td>{{ $transaction->toUser->name ?? 'User #' . $transaction->to_user_id }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ ucfirst($transaction->status) }}</span></td>
                                <td><a href="{{ route('transaction.receipt', $transaction->id) }}" class="btn btn-sm btn-outline-primary">PDF</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
