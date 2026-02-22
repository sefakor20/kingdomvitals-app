<x-mail::message>
# {{ __('Registration Confirmed!') }}

{{ __('Dear :name,', ['name' => $attendeeName]) }}

{{ __('Your registration for **:event** has been confirmed. We look forward to seeing you!', ['event' => $event->name]) }}

<x-mail::panel>
**{{ __('Event Details') }}**

- **{{ __('Event') }}:** {{ $event->name }}
- **{{ __('Date') }}:** {{ $event->starts_at->format('l, F j, Y') }}
- **{{ __('Time') }}:** {{ $event->starts_at->format('g:i A') }}@if($event->ends_at) - {{ $event->ends_at->format('g:i A') }}@endif

- **{{ __('Location') }}:** {{ $event->location }}
@if($event->address || $event->city)
- **{{ __('Address') }}:** {{ collect([$event->address, $event->city])->filter()->join(', ') }}
@endif
</x-mail::panel>

@if($registration->ticket_number)
<x-mail::panel>
**{{ __('Your Ticket') }}**

<div style="text-align: center; margin: 16px 0;">
{!! app(\App\Services\QrCodeService::class)->generateEventTicketQrCode($registration, 150) !!}
</div>

**{{ $registration->ticket_number }}**

{{ __('Show this QR code at check-in.') }}
</x-mail::panel>
@endif

@if($registration->is_paid && $registration->price_paid)
<x-mail::panel>
**{{ __('Payment Details') }}**

- **{{ __('Amount Paid') }}:** {{ app(\App\Services\CurrencyFormatter::class)->formatWithCode($registration->price_paid, $event->currency) }}
- **{{ __('Status') }}:** {{ __('Paid') }}
</x-mail::panel>
@endif

@if($registration->ticket_number)
<x-mail::button :url="URL::signedRoute('events.public.ticket.download', [$branch, $event, $registration])">
{{ __('Download Ticket PDF') }}
</x-mail::button>
@endif

<x-mail::button :url="route('events.public.details', [$branch, $event])" color="success">
{{ __('View Event Details') }}
</x-mail::button>

{{ __('If you have any questions, please contact us.') }}

{{ __('We look forward to seeing you!') }}

{{ __('Best regards,') }}<br>
{{ $branch->name }}
</x-mail::message>
