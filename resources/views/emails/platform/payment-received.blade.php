<x-mail::message>
# Payment Received

Dear {{ $tenant->name }},

Thank you! We have received your payment for invoice {{ $invoice->invoice_number }}.

**Payment Details:**
- **Payment Reference:** {{ $payment->payment_reference }}
- **Amount Paid:** {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
- **Payment Method:** {{ $payment->payment_method->label() }}
- **Date:** {{ $payment->paid_at->format('F j, Y g:i A') }}

**Invoice Status:**
- **Invoice Number:** {{ $invoice->invoice_number }}
- **Total Amount:** {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}
- **Amount Paid:** {{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}
@if($invoice->balance_due > 0)
- **Remaining Balance:** {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}
@else
- **Status:** Paid in Full
@endif

<x-mail::button :url="$invoiceUrl">
View Invoice
</x-mail::button>

Thank you for your continued trust in Kingdom Vitals!

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
