<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 32px 36px; }
        .muted { color: #64748b; }
        .top { display: table; width: 100%; }
        .top > div { display: table-cell; vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #0f172a; }
        .tagline { font-size: 10px; color: #64748b; margin-top: 2px; }
        .doc-title { text-align: right; font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #0f766e; }
        .doc-meta { text-align: right; font-size: 10px; color: #475569; margin-top: 4px; }
        .parties { display: table; width: 100%; margin-top: 28px; }
        .parties > div { display: table-cell; width: 50%; vertical-align: top; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 5px; }
        .name { font-weight: bold; font-size: 12px; color: #0f172a; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 26px; }
        table.items th { background: #f1f5f9; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: #475569; padding: 7px 8px; border-bottom: 1px solid #cbd5e1; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .totals { width: 100%; margin-top: 14px; }
        .totals td { padding: 4px 8px; }
        .totals .amount { text-align: right; width: 30%; }
        .totals tr.total td { border-top: 2px solid #0f172a; font-weight: bold; font-size: 12px; padding-top: 8px; }
        .footer { margin-top: 36px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 9px; color: #64748b; }
        .footer strong { color: #334155; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; background: #ccfbf1; color: #0f766e; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <div class="brand">{{ $company['name'] }}</div>
            <div class="tagline">{{ $company['tagline'] }}</div>
            <div class="muted" style="margin-top:10px;">
                {{ $company['address_line_1'] }}<br>
                {{ $company['address_line_2'] }}<br>
                {{ $company['city'] }} {{ $company['postcode'] }}<br>
                {{ $company['phone'] }} · {{ $company['email'] }}
            </div>
        </div>
        <div>
            <div class="doc-title">INVOICE</div>
            <div class="doc-meta">
                {{ $invoice->number }}<br>
                Invoiced {{ $invoice->invoice_date?->format('j F Y') }}<br>
                Due {{ $invoice->due_date?->format('j F Y') }}<br>
                <span class="badge">{{ str_replace('_', ' ', $invoice->status) }}</span>
            </div>
        </div>
    </div>

    <div class="parties">
        <div>
            <div class="label">Invoice to</div>
            <div class="name">{{ $invoice->customer?->name }}</div>
            <div class="muted">
                {{ $invoice->customer?->address }}<br>
                {{ $invoice->customer?->city }} {{ $invoice->customer?->postcode }}<br>
                {{ $invoice->customer?->email }}
            </div>
        </div>
        <div>
            <div class="label">Reference</div>
            <div>{{ $invoice->order ? 'Sales order '.$invoice->order->number : 'Trade sale' }}</div>
            <div class="label" style="margin-top:10px;">VAT number</div>
            <div>{{ $company['vat_number'] }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:52%;">Description</th>
                <th class="right" style="width:8%;">Qty</th>
                <th class="right" style="width:18%;">Unit price</th>
                <th class="right" style="width:22%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">£{{ number_format($line->unit_price_pence / 100, 2) }}</td>
                    <td class="right">£{{ number_format($line->line_total_pence / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal (excl. VAT)</td>
            <td class="amount">£{{ number_format($invoice->subtotal_pence / 100, 2) }}</td>
        </tr>
        <tr>
            <td class="muted">VAT @ {{ rtrim(rtrim(number_format(\App\Modules\Core\Support\Vat::rate() * 100, 2), '0'), '.') }}%</td>
            <td class="amount">£{{ number_format($invoice->vat_pence / 100, 2) }}</td>
        </tr>
        <tr class="total">
            <td>Total due</td>
            <td class="amount">£{{ number_format($invoice->total_pence / 100, 2) }}</td>
        </tr>
        @if ($invoice->amount_paid_pence > 0)
            <tr>
                <td class="muted">Paid to date</td>
                <td class="amount">£{{ number_format($invoice->amount_paid_pence / 100, 2) }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Outstanding</td>
                <td class="amount" style="font-weight:bold;">£{{ number_format($invoice->outstandingPence() / 100, 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="footer">
        <strong>Payment details</strong> {{ $company['bank_name'] }} · sort code {{ $company['bank_sort_code'] }} · account {{ $company['bank_account'] }}<br>
        Please quote {{ $invoice->number }} with your payment. Goods remain the property of {{ $company['name'] }} until paid in full.<br>
        {{ $company['name'] }} · Registered in England &amp; Wales no. {{ $company['company_number'] }} · VAT {{ $company['vat_number'] }}
    </div>
</body>
</html>
