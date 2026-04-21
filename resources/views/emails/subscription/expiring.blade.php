<x-mail::message>
# Your Access Expires in {{ $daysRemaining }} {{ Str::plural('Day', $daysRemaining) }}

Dear {{ $tenant->name }},

This is a reminder that your Kingdom Vitals access will expire on **{{ $endsAt->format('F j, Y') }}**.

After this date, you will no longer be able to access your account. Reactivate now to keep your data and avoid any disruption.

<x-mail::button :url="route('settings.subscription')">
Reactivate Subscription
</x-mail::button>

**Why reactivate?**
- All your data is preserved and ready to use
- Pick up exactly where you left off
- Reactivation takes less than a minute

If you have any questions about our plans or pricing, reply to this email — we're happy to help.

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
