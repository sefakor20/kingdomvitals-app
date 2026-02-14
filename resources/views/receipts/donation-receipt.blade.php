<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Donation Receipt') }} - {{ $donation->getReceiptNumber() }}</title>
    <style>
        @page { margin: 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .church-name {
            font-size: 20px;
            font-weight: bold;
            color: {{ $branch->color_primary ?? '#3B82F6' }};
            margin-bottom: 5px;
        }
        .church-contact {
            font-size: 10px;
            color: #666;
        }
        .receipt-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 10px;
            text-transform: uppercase;
        }
        .receipt-number {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .details-table td.label {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .amount-row td {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            border-top: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            padding: 15px 0;
        }
        .amount-value {
            color: {{ $branch->color_primary ?? '#3B82F6' }};
        }
        .thank-you {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .thank-you h3 {
            color: {{ $branch->color_primary ?? '#3B82F6' }};
            margin-bottom: 10px;
        }
        .scripture {
            font-style: italic;
            color: #666;
            margin-top: 15px;
            font-size: 11px;
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
    <div class="header">
        @if($logoUrl = \App\Services\LogoService::getLogoUrl($branch, 'medium'))
            <img src="{{ $logoUrl }}" class="logo" alt="{{ $branch->name }}">
        @endif
        <div class="church-name">{{ $branch->name }}</div>
        <div class="church-contact">
            @if($branch->address){{ $branch->address }}@endif
            @if($branch->city), {{ $branch->city }}@endif
            @if($branch->state), {{ $branch->state }}@endif
            @if($branch->zip) {{ $branch->zip }}@endif
            <br>
            @if($branch->phone){{ $branch->phone }}@endif
            @if($branch->phone && $branch->email) | @endif
            @if($branch->email){{ $branch->email }}@endif
        </div>
    </div>

    <div class="receipt-title">{{ __('Donation Receipt') }}</div>
    <div class="receipt-number">{{ $donation->getReceiptNumber() }}</div>

    <table class="details-table">
        <tr>
            <td class="label">{{ __('Date') }}</td>
            <td>{{ $donation->donation_date->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Received From') }}</td>
            <td>{{ $donation->getDonorDisplayName() }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Donation Type') }}</td>
            <td>{{ str_replace('_', ' ', ucfirst($donation->donation_type->value)) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Payment Method') }}</td>
            <td>{{ str_replace('_', ' ', ucfirst($donation->payment_method->value)) }}</td>
        </tr>
        @if($donation->reference_number)
        <tr>
            <td class="label">{{ __('Reference') }}</td>
            <td>{{ $donation->reference_number }}</td>
        </tr>
        @endif
        @if($donation->service)
        <tr>
            <td class="label">{{ __('Service') }}</td>
            <td>{{ $donation->service->name }}</td>
        </tr>
        @endif
        <tr class="amount-row">
            <td class="label">{{ __('Amount') }}</td>
            <td class="amount-value">{{ \App\Services\CurrencyFormatter::formatWithCode($donation->amount, $donation->currency) }}</td>
        </tr>
    </table>

    <div class="thank-you">
        <h3>{{ __('Thank You for Your Generosity!') }}</h3>
        <p>{{ __('Your contribution supports our mission and ministry. We are grateful for your partnership in this work.') }}</p>
        <div class="scripture">
            "Each of you should give what you have decided in your heart to give,<br>
            not reluctantly or under compulsion, for God loves a cheerful giver."<br>
            — 2 Corinthians 9:7
        </div>
    </div>

    <div class="footer">
        {{ __('This receipt is for your records.') }} |
        {{ __('Generated on :date', ['date' => now()->format('F j, Y')]) }}
    </div>
</body>
</html>
