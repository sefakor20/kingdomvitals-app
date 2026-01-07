<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Payments') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('View all platform payments') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export') }}
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search payments...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="status" class="w-40">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach($this->statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="method" class="w-40">
            <option value="">{{ __('All Methods') }}</option>
            @foreach($this->methodOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
        @if($search || $status || $method)
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th wire:click="sortBy('payment_reference')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Reference') }}
                            @if($sortBy === 'payment_reference')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Tenant') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Invoice') }}</th>
                        <th wire:click="sortBy('amount')" class="cursor-pointer px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Amount') }}
                            @if($sortBy === 'amount')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Method') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Status') }}</th>
                        <th wire:click="sortBy('paid_at')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Date') }}
                            @if($sortBy === 'paid_at')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->payments as $payment)
                        <tr wire:key="payment-{{ $payment->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="font-mono text-sm font-medium">
                                    {{ $payment->payment_reference }}
                                </span>
                                @if($payment->paystack_reference)
                                    <br><span class="font-mono text-xs text-zinc-400">{{ $payment->paystack_reference }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $payment->tenant?->name ?? 'Unknown' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($payment->invoice)
                                    <a href="{{ route('superadmin.billing.invoices.show', $payment->invoice) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-zinc-400">{{ __('N/A') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">
                                {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <flux:badge color="zinc" size="sm">
                                    {{ $payment->payment_method->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <flux:badge :color="$payment->status->color()" size="sm">
                                    {{ $payment->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $payment->paid_at?->format('M d, Y H:i') ?? $payment->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        @if($payment->invoice)
                                            <flux:menu.item href="{{ route('superadmin.billing.invoices.show', $payment->invoice) }}" wire:navigate icon="document-text">
                                                {{ __('View Invoice') }}
                                            </flux:menu.item>
                                        @endif
                                        @if($payment->notes)
                                            <flux:menu.item icon="chat-bubble-left">
                                                {{ __('Notes:') }} {{ Str::limit($payment->notes, 30) }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">
                                {{ __('No payments found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->payments->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->payments->links() }}
            </div>
        @endif
    </div>
</div>
