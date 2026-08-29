<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; line-height: 1.6; }
        .box { border: 1px solid #d1d5db; padding: 20px; border-radius: 12px; }
        h1 { margin: 0 0 10px; }
        .meta { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .amount { font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Wallet Transfer Receipt</h1>
        <div class="meta">
            <strong>Receipt ID:</strong> {{ $receiptId }}<br>
            <strong>Date:</strong> {{ $date }}
        </div>

        <table>
            <tr>
                <th>From</th>
                <td>{{ $transaction->fromUser?->name ?? 'User #' . $transaction->from_user_id }}</td>
            </tr>
            <tr>
                <th>To</th>
                <td>{{ $transaction->toUser?->name ?? 'User #' . $transaction->to_user_id }}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td class="amount">BDT {{ number_format((float) $transaction->amount, 2) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($transaction->status) }}</td>
            </tr>
            <tr>
                <th>Memo</th>
                <td>{{ $memo }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
