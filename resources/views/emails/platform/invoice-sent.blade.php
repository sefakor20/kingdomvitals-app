<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Dear {{ $tenant->name }},

A new invoice has been generated for your Kingdom Vitals subscription.

**Invoice Details:**
- **Invoice Number:** {{ $invoice->invoice_number }}
- **Billing Period:** {{ $invoice->billing_period }}
- **Issue Date:** {{ $invoice->issue_date->format('F j, Y') }}
- **Due Date:** {{ $invoice->due_date->format('F j, Y') }}
- **Amount Due:** {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}

<x-mail::table>
| Description | Qty | Total |
|:------------|:---:|------:|
@foreach($invoice->items as $item)
| {{ $item->description }} | {{ $item->quantity }} | {{ $invoice->currency }} {{ number_format((float) $item->total, 2) }} |
@endforeach
</x-mail::table>

@if($invoice->discount_amount > 0)
**Discount Applied:** -{{ $invoice->currency }} {{ number_format((float) $invoice->discount_amount, 2) }}
@endif

**Total Amount Due:** {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}

<x-mail::button :url="$invoiceUrl">
View Invoice
</x-mail::button>

Please ensure payment is made by the due date to avoid any service interruptions.

If you have any questions about this invoice, please don't hesitate to contact us.

Thank you for choosing Kingdom Vitals!

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
