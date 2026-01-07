<x-mail::message>
# Payment Reminder

Dear {{ $tenant->name }},

@if($reminderType === 'upcoming')
This is a friendly reminder that invoice {{ $invoice->invoice_number }} is due in 3 days.
@elseif($reminderType === 'overdue_7')
Your invoice {{ $invoice->invoice_number }} is now 7 days overdue. Please arrange payment as soon as possible.
@elseif($reminderType === 'overdue_14')
Your invoice {{ $invoice->invoice_number }} is now 14 days overdue. Please arrange immediate payment to avoid service interruption.
@elseif($reminderType === 'overdue_30')
**URGENT:** Your invoice {{ $invoice->invoice_number }} is now 30 days overdue. Your account may be suspended if payment is not received soon.
@elseif($reminderType === 'final_notice')
**FINAL NOTICE:** This is our final reminder for invoice {{ $invoice->invoice_number }}. Your service will be suspended if payment is not received within 7 days.
@endif

**Invoice Details:**
- **Invoice Number:** {{ $invoice->invoice_number }}
- **Billing Period:** {{ $invoice->billing_period }}
- **Issue Date:** {{ $invoice->issue_date->format('F j, Y') }}
- **Due Date:** {{ $invoice->due_date->format('F j, Y') }}
- **Amount Due:** {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}

@if($invoice->daysOverdue() > 0)
**Days Overdue:** {{ $invoice->daysOverdue() }} days
@endif

<x-mail::button :url="$invoiceUrl">
Pay Now
</x-mail::button>

If you have already made this payment, please disregard this notice. If you are experiencing any difficulties or have questions about your account, please contact us immediately.

@if($reminderType === 'final_notice')
<x-mail::panel>
**Important:** Service suspension means your team will lose access to Kingdom Vitals, including member records, attendance tracking, and all other features. Please take action to avoid disruption.
</x-mail::panel>
@endif

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
