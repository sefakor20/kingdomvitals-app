<x-mail::message>
# Account Overdue Notice

Dear {{ $tenant->name }},

Your Kingdom Vitals account has an overdue balance that requires immediate attention.

**Account Summary:**
- **Total Overdue:** {{ $currency }} {{ number_format($overdueAmount, 2) }}
- **Number of Overdue Invoices:** {{ $invoiceCount }}

**Overdue Invoices:**

<x-mail::table>
| Invoice # | Due Date | Amount |
|:----------|:---------|-------:|
@foreach($invoices as $invoice)
| {{ $invoice->invoice_number }} | {{ $invoice->due_date->format('M d, Y') }} | {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }} |
@endforeach
</x-mail::table>

To avoid any disruption to your service, please arrange payment at your earliest convenience.

<x-mail::button :url="$billingUrl">
View All Invoices
</x-mail::button>

<x-mail::panel>
**Need Help?** If you're experiencing financial difficulties or have questions about your account, please contact us. We're here to help find a solution.
</x-mail::panel>

Best regards,<br>
{{ config('app.name') }} Team
</x-mail::message>
