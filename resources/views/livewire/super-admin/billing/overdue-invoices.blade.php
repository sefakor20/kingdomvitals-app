<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Overdue Invoices') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('Manage overdue invoices and send payment reminders') }}
            </flux:text>
        </div>
        @if(count($selectedInvoices) > 0)
            <flux:button variant="primary" icon="paper-airplane" wire:click="sendBulkReminders" wire:loading.attr="disabled">
                {{ __('Send Reminders') }} ({{ count($selectedInvoices) }})
            </flux:button>
        @endif
    </div>

    {{-- Summary Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Overdue') }}</p>
                    <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->summary['total_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="currency-dollar" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Amount') }}</p>
                    <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->summary['total_amount'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="clock" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Over 30 Days') }}</p>
                    <p class="text-xl font-semibold text-red-600 dark:text-red-400">{{ $this->summary['over_30_days'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="clock" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('14-30 Days') }}</p>
                    <p class="text-xl font-semibold text-amber-600 dark:text-amber-400">{{ $this->summary['over_14_days'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Invoice') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Tenant') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Amount Due') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Due Date') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Days Overdue') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Last Reminder') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->overdueInvoices as $invoice)
                        <tr wire:key="overdue-{{ $invoice['id'] }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-4 py-3">
                                <flux:checkbox value="{{ $invoice['id'] }}" wire:model.live="selectedInvoices" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('superadmin.billing.invoices.show', $invoice['id']) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    {{ $invoice['invoice_number'] }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $invoice['tenant']?->name ?? 'Unknown' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">
                                {{ $invoice['amount'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                {{ $invoice['due_date'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @php
                                    $days = $invoice['days_overdue'];
                                    $color = match(true) {
                                        $days >= 30 => 'red',
                                        $days >= 14 => 'amber',
                                        default => 'yellow',
                                    };
                                @endphp
                                <flux:badge :color="$color" size="sm">
                                    {{ $days }} {{ __('days') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                                @if($invoice['last_reminder'])
                                    <div class="text-zinc-600 dark:text-zinc-400">{{ $invoice['last_reminder'] }}</div>
                                    <div class="text-xs text-zinc-400">{{ $invoice['last_reminder_type'] }}</div>
                                    <div class="text-xs text-zinc-400">({{ $invoice['reminder_count'] }} {{ __('sent') }})</div>
                                @else
                                    <span class="text-zinc-400">{{ __('None') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="sendReminder('{{ $invoice['id'] }}')" wire:loading.attr="disabled" icon="paper-airplane">
                                        {{ __('Send Reminder') }}
                                    </flux:button>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item href="{{ route('superadmin.billing.invoices.show', $invoice['id']) }}" wire:navigate icon="eye">
                                                {{ __('View Invoice') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon name="check-circle" class="size-12 text-green-500" />
                                    <p class="text-lg font-medium text-zinc-600 dark:text-zinc-400">{{ __('No overdue invoices') }}</p>
                                    <p class="text-sm text-zinc-500">{{ __('All invoices are paid or within their due dates.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
