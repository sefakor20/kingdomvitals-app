<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Giving Statement') }} - {{ $year }}</title>
    <style>
        @page { margin: 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 8px;
        }
        .church-name {
            font-size: 18px;
            font-weight: bold;
            color: {{ $branch->color_primary ?? '#3B82F6' }};
            margin-bottom: 5px;
        }
        .church-contact {
            font-size: 10px;
            color: #666;
        }
        .statement-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 5px;
            text-transform: uppercase;
        }
        .statement-period {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .member-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .member-info h4 {
            margin: 0 0 8px;
            color: {{ $branch->color_primary ?? '#3B82F6' }};
            font-size: 12px;
        }
        .member-info p {
            margin: 2px 0;
            font-size: 11px;
        }
        .donations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .donations-table th {
            background: {{ $branch->color_primary ?? '#3B82F6' }};
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        .donations-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .donations-table tr:nth-child(even) {
            background: #fafafa;
        }
        .donations-table .amount {
            text-align: right;
            font-family: monospace;
        }
        .total-row {
            background: #f3f4f6 !important;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            border-bottom: 2px solid {{ $branch->color_primary ?? '#3B82F6' }};
            padding: 12px 10px;
            font-size: 12px;
        }
        .total-amount {
            color: {{ $branch->color_primary ?? '#3B82F6' }};
            font-size: 14px;
        }
        .no-donations {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .disclaimer {
            margin-top: 30px;
            padding: 15px;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            font-size: 10px;
            color: #92400e;
        }
        .footer {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 9px;
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

    <div class="statement-title">{{ __('Giving Statement') }}</div>
    <div class="statement-period">{{ __('January 1 - December 31, :year', ['year' => $year]) }}</div>

    <div class="member-info">
        <h4>{{ __('Donor Information') }}</h4>
        <p><strong>{{ $member->fullName() }}</strong></p>
        @if($member->membership_number)
            <p>{{ __('Member #:') }} {{ $member->membership_number }}</p>
        @endif
        @if($member->address)
            <p>{{ $member->address }}</p>
        @endif
        @if($member->city || $member->state || $member->zip)
            <p>
                @if($member->city){{ $member->city }}@endif
                @if($member->city && $member->state), @endif
                @if($member->state){{ $member->state }}@endif
                @if($member->zip) {{ $member->zip }}@endif
            </p>
        @endif
    </div>

    @if($donations->isEmpty())
        <div class="no-donations">
            {{ __('No giving records found for :year.', ['year' => $year]) }}
        </div>
    @else
        <table class="donations-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Payment Method') }}</th>
                    <th class="amount">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donations as $donation)
                    <tr>
                        <td>{{ $donation->donation_date->format('M j, Y') }}</td>
                        <td>{{ $donation->donation_type ? __(str()->headline($donation->donation_type->value)) : '-' }}</td>
                        <td>{{ $donation->payment_method ? __(str()->headline($donation->payment_method->value)) : '-' }}</td>
                        <td class="amount">{{ $currency->symbol() }}{{ number_format($donation->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>{{ __('Total Giving for :year', ['year' => $year]) }}</strong></td>
                    <td class="amount total-amount">{{ $currency->symbol() }}{{ number_format($total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="disclaimer">
        <strong>{{ __('Tax Information:') }}</strong>
        {{ __('This statement is provided for your records. Please consult with a tax professional regarding the tax deductibility of your contributions. No goods or services were provided in exchange for these contributions unless otherwise noted.') }}
    </div>

    <div class="footer">
        {{ __('Statement generated on :date', ['date' => now()->format('F j, Y')]) }} |
        {{ $branch->name }}
    </div>
</body>
</html>
