<x-mail::message>
# Subscription Reactivated

Dear {{ $tenant->name }},

Great news — your Kingdom Vitals subscription has been successfully reactivated! You now have full access to all features.

<x-mail::panel>
**Your subscription is active again.** All your data and settings are exactly as you left them.
</x-mail::panel>

<x-mail::button :url="route('dashboard')">
Go to Dashboard
</x-mail::button>

Thank you for continuing with Kingdom Vitals. If you have any questions, reply to this email and we'll be happy to help.

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
