<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Invoices') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('Manage platform invoices') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('superadmin.billing.invoices.create') }}" icon="plus" wire:navigate>
                {{ __('Create Invoice') }}
            </flux:button>
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export') }}
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search invoices...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="status" class="w-40">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach($this->statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
        @if($search || $status)
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
                        <th wire:click="sortBy('invoice_number')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Invoice #') }}
                            @if($sortBy === 'invoice_number')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Tenant') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Period') }}</th>
                        <th wire:click="sortBy('issue_date')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Issue Date') }}
                            @if($sortBy === 'issue_date')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th wire:click="sortBy('due_date')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Due Date') }}
                            @if($sortBy === 'due_date')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th wire:click="sortBy('total_amount')" class="cursor-pointer px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('Total') }}
                            @if($sortBy === 'total_amount')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="ml-1 inline size-3" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('superadmin.billing.invoices.show', $invoice) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $invoice->tenant?->name ?? 'Unknown' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500">
                                {{ $invoice->billing_period }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $invoice->issue_date->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $invoice->due_date->format('M d, Y') }}
                                @if($invoice->isOverdue())
                                    <span class="ml-1 text-xs text-red-500">({{ $invoice->daysOverdue() }}d)</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">
                                {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}
                                @if($invoice->balance_due > 0 && $invoice->balance_due < $invoice->total_amount)
                                    <br><span class="text-xs text-zinc-500">Due: {{ number_format((float) $invoice->balance_due, 2) }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <flux:badge :color="$invoice->status->color()" size="sm">
                                    {{ $invoice->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('superadmin.billing.invoices.show', $invoice) }}" wire:navigate icon="eye">
                                            {{ __('View') }}
                                        </flux:menu.item>
                                        @if($invoice->status->canBeSent())
                                            <flux:menu.item wire:click="sendInvoice('{{ $invoice->id }}')" icon="paper-airplane">
                                                {{ __('Send') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">
                                {{ __('No invoices found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->invoices->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->invoices->links() }}
            </div>
        @endif
    </div>
</div>
