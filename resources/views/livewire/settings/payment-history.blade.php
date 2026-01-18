<div class="w-full">
    <div class="mb-8">
        <flux:heading size="xl" level="1">{{ __('Payment History') }}</flux:heading>
        <flux:subheading>
            {{ __('View your invoices and payment records') }}
        </flux:subheading>
    </div>

    <div class="space-y-8">
        @if($this->hasBillingHistory)
            {{-- Invoices Section --}}
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Invoices') }}</flux:heading>

                @if($this->invoices->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Invoice #') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Date') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Plan') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Amount') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                    @foreach($this->invoices as $invoice)
                                        <tr wire:key="invoice-{{ $invoice->id }}">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $invoice->invoice_number }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $invoice->issue_date->format('M d, Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $invoice->subscriptionPlan?->name ?? '-' }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ Number::currency($invoice->total_amount, in: $invoice->currency ?? 'GHS') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                <flux:badge color="{{ $invoice->status->color() }}" size="sm">
                                                    {{ $invoice->status->label() }}
                                                </flux:badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('No invoices found.') }}
                        </flux:text>
                    </div>
                @endif
            </div>

            {{-- Payments Section --}}
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Payments') }}</flux:heading>

                @if($this->payments->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Reference') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Date') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Method') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Amount') }}
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                    @foreach($this->payments as $payment)
                                        <tr wire:key="payment-{{ $payment->id }}">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $payment->payment_reference }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $payment->paid_at?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                <div class="flex items-center gap-2">
                                                    @if($payment->payment_method)
                                                        <flux:icon name="{{ $payment->payment_method->icon() }}" class="size-4 text-zinc-400" />
                                                        {{ $payment->payment_method->label() }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ Number::currency($payment->amount, in: $payment->currency ?? 'GHS') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                <flux:badge color="{{ $payment->status->color() }}" size="sm">
                                                    {{ $payment->status->label() }}
                                                </flux:badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                            {{ __('No payments found.') }}
                        </flux:text>
                    </div>
                @endif
            </div>
        @else
            {{-- Empty State --}}
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon name="document-text" class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No billing history') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Your invoices and payment records will appear here once you make your first subscription payment.') }}
                </flux:text>
            </div>
        @endif
    </div>
</div>
