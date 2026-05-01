@php
    use App\Enums\InvoiceStatus;
    use App\Models\PlatformInvoice;

    $tenant = tenant();
    $skipRoutes = ['payment.required', 'upgrade.required', 'plans.index', 'plans.checkout', 'subscription.show', 'payments.history'];

    $invoices = collect();

    if ($tenant && auth()->check() && ! request()->routeIs(...$skipRoutes)) {
        $invoices = PlatformInvoice::forTenant($tenant->id)
            ->where('status', InvoiceStatus::Sent)
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->get();
    }
@endphp

@if($invoices->isNotEmpty())
    <div class="px-6 pt-4">
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>
                {{ trans_choice('You have :count invoice due soon|You have :count invoices due soon', $invoices->count(), ['count' => $invoices->count()]) }}
            </flux:callout.heading>
            <flux:callout.text>
                @foreach($invoices as $invoice)
                    {{ $invoice->invoice_number }} ({{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}) {{ __('due') }} {{ $invoice->due_date->format('M j, Y') }}@if(! $loop->last), @endif
                @endforeach
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button :href="route('payments.history')" variant="primary" size="sm" wire:navigate>
                    {{ __('Pay Now') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    </div>
@endif
