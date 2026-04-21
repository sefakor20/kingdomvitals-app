<x-mail::message>
# Subscription Cancelled

Dear {{ $tenant->name }},

Your Kingdom Vitals subscription has been cancelled. You will continue to have full access to all features until **{{ $endsAt->format('F j, Y') }}**.

@if($daysRemaining > 0)
You have **{{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}** remaining.
@endif

<x-mail::panel>
**What happens next:**
- Your data is preserved and safe
- You can reactivate your subscription at any time before {{ $endsAt->format('F j, Y') }}
- After that date, your account will be deactivated
</x-mail::panel>

If you change your mind, you can reactivate your subscription by visiting your subscription settings.

<x-mail::button :url="route('settings.subscription')">
Reactivate Subscription
</x-mail::button>

If you have any questions or feedback, we'd love to hear from you. Simply reply to this email.

Thank you for being a Kingdom Vitals customer.

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
