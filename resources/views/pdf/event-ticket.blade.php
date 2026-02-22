<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Event Ticket') }} - {{ $registration->ticket_number }}</title>
    <style>
        @page { margin: 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .ticket-container {
            max-width: 400px;
            margin: 0 auto;
            border: 2px solid {{ $branch->color_primary ?? '#4F46E5' }};
            border-radius: 12px;
            overflow: hidden;
        }
        .ticket-header {
            background: {{ $branch->color_primary ?? '#4F46E5' }};
            color: white;
            padding: 20px;
            text-align: center;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }
        .branch-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .event-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .ticket-body {
            padding: 25px;
            background: white;
        }
        .event-name {
            font-size: 22px;
            font-weight: bold;
            color: {{ $branch->color_primary ?? '#4F46E5' }};
            text-align: center;
            margin-bottom: 20px;
        }
        .event-details {
            margin-bottom: 25px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-icon {
            width: 24px;
            color: {{ $branch->color_primary ?? '#4F46E5' }};
            font-weight: bold;
        }
        .detail-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-weight: bold;
            color: #333;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border-top: 2px dashed #ddd;
            border-bottom: 2px dashed #ddd;
        }
        .qr-code {
            margin: 0 auto 15px;
        }
        .qr-code svg {
            width: 180px;
            height: 180px;
        }
        .ticket-number {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: {{ $branch->color_primary ?? '#4F46E5' }};
            letter-spacing: 2px;
        }
        .scan-instruction {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }
        .attendee-section {
            padding: 20px;
            text-align: center;
        }
        .attendee-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .attendee-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .ticket-footer {
            background: #f3f4f6;
            padding: 15px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .paid-badge {
            display: inline-block;
            background: #D1FAE5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .price-paid {
            font-size: 14px;
            font-weight: bold;
            color: #059669;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        {{-- Header --}}
        <div class="ticket-header">
            @if($logoUrl = \App\Services\LogoService::getLogoUrl($branch, 'medium'))
                <img src="{{ $logoUrl }}" class="logo" alt="{{ $branch->name }}">
            @endif
            <div class="branch-name">{{ $branch->name }}</div>
            <div class="event-badge">{{ __('Event Ticket') }}</div>
        </div>

        {{-- Body --}}
        <div class="ticket-body">
            <div class="event-name">{{ $event->name }}</div>

            <div class="event-details">
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px dashed #eee;">
                            <div class="detail-label">{{ __('Date') }}</div>
                            <div class="detail-value">{{ $event->starts_at->format('l, F j, Y') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px dashed #eee;">
                            <div class="detail-label">{{ __('Time') }}</div>
                            <div class="detail-value">
                                {{ $event->starts_at->format('g:i A') }}@if($event->ends_at) - {{ $event->ends_at->format('g:i A') }}@endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;">
                            <div class="detail-label">{{ __('Location') }}</div>
                            <div class="detail-value">{{ $event->location }}</div>
                            @if($event->address || $event->city)
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                    {{ collect([$event->address, $event->city])->filter()->join(', ') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- QR Code Section --}}
        <div class="qr-section">
            <div class="qr-code">
                <img src="{{ $qrCode }}" alt="QR Code" style="width: 180px; height: 180px;">
            </div>
            <div class="ticket-number">{{ $registration->ticket_number }}</div>
            <div class="scan-instruction">{{ __('Present this QR code at check-in') }}</div>
        </div>

        {{-- Attendee Section --}}
        <div class="attendee-section">
            <div class="attendee-label">{{ __('Attendee') }}</div>
            <div class="attendee-name">{{ $registration->attendee_name }}</div>

            @if($registration->is_paid && $registration->price_paid)
                <div class="paid-badge">{{ __('Paid') }}</div>
                <div class="price-paid">
                    {{ app(\App\Services\CurrencyFormatter::class)->formatWithCode($registration->price_paid, $event->currency) }}
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            {{ __('Generated on :date', ['date' => now()->format('F j, Y')]) }}
        </div>
    </div>
</body>
</html>
