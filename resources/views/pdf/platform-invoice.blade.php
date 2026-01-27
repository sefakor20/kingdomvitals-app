<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
        }
        .header-table {
            width: 100%;
        }
        .company-info {
            font-size: 10px;
            color: #666;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 5px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 28px;
            color: #4F46E5;
            margin: 0;
            text-transform: uppercase;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-draft { background: #F3F4F6; color: #6B7280; }
        .status-sent { background: #DBEAFE; color: #1D4ED8; }
        .status-paid { background: #D1FAE5; color: #059669; }
        .status-partial { background: #FEF3C7; color: #D97706; }
        .status-overdue { background: #FEE2E2; color: #DC2626; }
        .status-cancelled { background: #F3F4F6; color: #6B7280; }
        .status-refunded { background: #EDE9FE; color: #7C3AED; }

        .addresses {
            margin: 30px 0;
        }
        .addresses-table {
            width: 100%;
        }
        .address-block {
            vertical-align: top;
            width: 48%;
        }
        .address-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .address-name {
            font-weight: bold;
            font-size: 14px;
        }

        .invoice-details {
            margin: 30px 0;
            background: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
        }
        .invoice-details-table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .invoice-details .label {
            color: #666;
            width: 50%;
        }
        .invoice-details .value {
            text-align: right;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #4F46E5;
            color: white;
            text-align: left;
            padding: 10px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals {
            margin-top: 30px;
        }
        .totals-table {
            width: 300px;
            float: right;
        }
        .totals-table td {
            padding: 8px 0;
        }
        .totals-table .label {
            color: #666;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            font-size: 16px;
            border-top: 2px solid #4F46E5;
            padding-top: 10px;
        }
        .grand-total .label {
            color: #333;
            font-weight: bold;
        }
        .grand-total .value {
            color: #4F46E5;
        }
        .balance-due {
            background: #FEE2E2;
            padding: 8px;
            border-radius: 4px;
        }
        .balance-due .label,
        .balance-due .value {
            color: #DC2626;
        }

        .payments {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .payments-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4F46E5;
        }
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .payments-table th {
            background: #F3F4F6;
            padding: 8px;
            text-align: left;
        }
        .payments-table th:last-child,
        .payments-table td:last-child {
            text-align: right;
        }
        .payments-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .notes {
            margin-top: 30px;
            padding: 15px;
            background: #F9FAFB;
            border-radius: 8px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notes-content {
            color: #666;
            font-size: 11px;
        }

        .payment-instructions {
            margin-top: 30px;
            padding: 20px;
            background: #EEF2FF;
            border-radius: 8px;
        }
        .payment-instructions-title {
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        .payment-instructions-content {
            font-size: 11px;
            color: #666;
        }

        .footer {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    @php
        $platformLogoUrl = null;
        $platformName = config('app.name');

        // Get platform logo from SystemSetting
        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
        if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['medium'])) {
            $path = $platformLogoPaths['medium'];
            $fullPath = base_path('storage/app/public/'.$path);
            if (file_exists($fullPath)) {
                $platformLogoUrl = url('storage/'.$path);
            }
        }
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    @if($platformLogoUrl)
                        <img src="{{ $platformLogoUrl }}" style="max-height: 50px; margin-bottom: 10px;" alt="{{ $platformName }}">
                    @endif
                    <div class="company-name">{{ $platformName }}</div>
                    <div class="company-info">
                        Church Management Platform<br>
                        admin@kingdomvitals.com
                    </div>
                </td>
                <td class="invoice-title">
                    <h1>Invoice</h1>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                    <div class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->label() }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="addresses">
        <table class="addresses-table">
            <tr>
                <td class="address-block">
                    <div class="address-label">Bill To</div>
                    <div class="address-name">{{ $tenant->name }}</div>
                    <div class="company-info">
                        @if($tenant->contact_email){{ $tenant->contact_email }}<br>@endif
                        @if($tenant->contact_phone){{ $tenant->contact_phone }}<br>@endif
                        @if($tenant->address){{ $tenant->address }}@endif
                    </div>
                </td>
                <td class="address-block" style="text-align: right;">
                    <div class="invoice-details">
                        <table class="invoice-details-table">
                            <tr>
                                <td class="label">Issue Date</td>
                                <td class="value">{{ $invoice->issue_date->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Due Date</td>
                                <td class="value">{{ $invoice->due_date->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Billing Period</td>
                                <td class="value">{{ $invoice->billing_period }}</td>
                            </tr>
                            @if($plan)
                            <tr>
                                <td class="label">Subscription Plan</td>
                                <td class="value">{{ $plan->name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $invoice->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                <td>{{ $invoice->currency }} {{ number_format((float) $item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="value">-{{ $invoice->currency }} {{ number_format((float) $invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr>
                <td class="label">Tax</td>
                <td class="value">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Total</td>
                <td class="value">{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
            @if($invoice->amount_paid > 0)
            <tr>
                <td class="label">Paid</td>
                <td class="value">-{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</td>
            </tr>
            @endif
            @if($invoice->balance_due > 0)
            <tr class="balance-due">
                <td class="label">Balance Due</td>
                <td class="value">{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($payments->isNotEmpty())
    <div class="payments">
        <div class="payments-title">Payment History</div>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at?->format('M d, Y') ?? '-' }}</td>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>{{ $payment->payment_method->label() }}</td>
                    <td>{{ $payment->status->label() }}</td>
                    <td>{{ $invoice->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($invoice->notes)
    <div class="notes">
        <div class="notes-title">Notes</div>
        <div class="notes-content">{!! nl2br(e($invoice->notes)) !!}</div>
    </div>
    @endif

    @if($invoice->balance_due > 0)
    <div class="payment-instructions">
        <div class="payment-instructions-title">Payment Instructions</div>
        <div class="payment-instructions-content">
            Please make payment by the due date to avoid service interruption.<br>
            For questions about this invoice, please contact admin@kingdomvitals.com
        </div>
    </div>
    @endif

    <div class="footer">
        Invoice {{ $invoice->invoice_number }} | Generated on {{ now()->format('F j, Y') }}
    </div>
</body>
</html>
