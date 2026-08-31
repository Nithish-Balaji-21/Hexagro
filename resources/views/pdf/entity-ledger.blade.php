<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger Book — {{ $entity->short_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: 700; }
        .num { text-align: right; }
        .totals { margin-top: 12px; }
        .totals td { border: none; padding: 4px 8px; }
    </style>
</head>
<body>
    <h1>Ledger Book — {{ $entity->short_name }}</h1>
    <div class="meta">
        {{ $range->displayLabel() }} · {{ $scopeLabel }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Unit</th>
                <th>Particulars</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if ((float) $openingBalance !== 0.0)
                <tr>
                    <td>—</td>
                    <td>—</td>
                    <td><strong>Opening balance</strong></td>
                    <td class="num">—</td>
                    <td class="num">—</td>
                    <td class="num">{{ \App\Support\Inr::format(abs((float) $openingBalance)) }} {{ (float) $openingBalance >= 0 ? 'Cr' : 'Dr' }}</td>
                </tr>
            @endif
            @foreach ($rows as $row)
                <tr>
                    <td>{{ \App\Support\Inr::formatDate($row->txnDate) }}</td>
                    <td>{{ $row->costCenter->name }}</td>
                    <td>{{ $row->particulars }}</td>
                    <td class="num">{{ (float) $row->debit > 0 ? \App\Support\Inr::format($row->debit) : '—' }}</td>
                    <td class="num">{{ (float) $row->credit > 0 ? \App\Support\Inr::format($row->credit) : '—' }}</td>
                    <td class="num">{{ \App\Support\Inr::format(abs((float) $row->runningBalance)) }} {{ (float) $row->runningBalance >= 0 ? 'Cr' : 'Dr' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td><strong>Total Debit:</strong> {{ \App\Support\Inr::format($totalDebit) }}</td>
            <td><strong>Total Credit:</strong> {{ \App\Support\Inr::format($totalCredit) }}</td>
            <td><strong>Closing Balance:</strong> {{ \App\Support\Inr::format(abs((float) $closing)) }} {{ (float) $closing >= 0 ? 'Cr' : 'Dr' }}</td>
        </tr>
    </table>
</body>
</html>
