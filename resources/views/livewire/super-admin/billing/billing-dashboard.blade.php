<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Billing Dashboard') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('Overview of platform billing and revenue') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('superadmin.billing.invoices') }}" variant="ghost" icon="document-text" wire:navigate>
                {{ __('View Invoices') }}
            </flux:button>
            <flux:button href="{{ route('superadmin.billing.invoices.create') }}" icon="plus" wire:navigate>
                {{ __('Create Invoice') }}
            </flux:button>
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export') }}
            </flux:button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.banknotes class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Revenue YTD') }}</flux:text>
                    <flux:heading size="lg">{{ $this->billingStats['totalRevenueYtd'] }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.clock class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Outstanding') }}</flux:text>
                    <flux:heading size="lg">{{ $this->billingStats['outstandingBalance'] }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Overdue') }}</flux:text>
                    <flux:heading size="lg">{{ $this->billingStats['overdueAmount'] }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon.check-circle class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Paid This Month') }}</flux:text>
                    <flux:heading size="lg">{{ $this->billingStats['paidThisMonth'] }}</flux:heading>
                </div>
            </div>
        </div>
    </div>

    {{-- Invoice Status Summary --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <a href="{{ route('superadmin.billing.invoices', ['status' => 'draft']) }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
            <flux:text class="text-sm text-zinc-500">{{ __('Draft') }}</flux:text>
            <flux:heading size="lg">{{ $this->invoiceCounts['draft'] }}</flux:heading>
        </a>
        <a href="{{ route('superadmin.billing.invoices', ['status' => 'sent']) }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
            <flux:text class="text-sm text-zinc-500">{{ __('Sent') }}</flux:text>
            <flux:heading size="lg">{{ $this->invoiceCounts['sent'] }}</flux:heading>
        </a>
        <a href="{{ route('superadmin.billing.overdue') }}" wire:navigate class="rounded-lg border border-red-200 bg-red-50 p-4 transition hover:border-red-300 dark:border-red-900/50 dark:bg-red-900/20 dark:hover:border-red-800">
            <flux:text class="text-sm text-red-600 dark:text-red-400">{{ __('Overdue') }}</flux:text>
            <flux:heading size="lg" class="text-red-700 dark:text-red-300">{{ $this->invoiceCounts['overdue'] }}</flux:heading>
        </a>
        <a href="{{ route('superadmin.billing.invoices', ['status' => 'paid']) }}" wire:navigate class="rounded-lg border border-green-200 bg-green-50 p-4 transition hover:border-green-300 dark:border-green-900/50 dark:bg-green-900/20 dark:hover:border-green-800">
            <flux:text class="text-sm text-green-600 dark:text-green-400">{{ __('Paid This Month') }}</flux:text>
            <flux:heading size="lg" class="text-green-700 dark:text-green-300">{{ $this->invoiceCounts['paid'] }}</flux:heading>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Revenue Chart --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Monthly Revenue') }}</flux:heading>
            <div class="h-64" x-data="{
                init() {
                    const ctx = this.$refs.chart.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: @js($this->monthlyRevenueData['labels']),
                            datasets: [{
                                label: 'Revenue (GHS)',
                                data: @js($this->monthlyRevenueData['amounts']),
                                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                                borderRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'GHS ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }">
                <canvas x-ref="chart"></canvas>
            </div>
        </div>

        {{-- Overdue Invoices Alert --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Overdue Invoices') }}</flux:heading>
                <flux:button variant="ghost" size="sm" href="{{ route('superadmin.billing.overdue') }}" wire:navigate>
                    {{ __('View All') }}
                </flux:button>
            </div>

            @if($this->overdueInvoices->isEmpty())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <flux:icon.check-circle class="size-12 text-green-500" />
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('No overdue invoices') }}</flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($this->overdueInvoices as $invoice)
                        <a href="{{ route('superadmin.billing.invoices.show', $invoice['id']) }}" wire:navigate class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50 p-3 transition hover:bg-red-100 dark:border-red-900/30 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                            <div>
                                <flux:text class="font-medium">{{ $invoice['tenant'] }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">{{ $invoice['invoice_number'] }}</flux:text>
                            </div>
                            <div class="text-right">
                                <flux:text class="font-medium text-red-600 dark:text-red-400">{{ $invoice['amount'] }}</flux:text>
                                <flux:text class="text-sm text-red-500">{{ $invoice['days_overdue'] }} days overdue</flux:text>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Payments') }}</flux:heading>
                <flux:button variant="ghost" size="sm" href="{{ route('superadmin.billing.payments') }}" wire:navigate>
                    {{ __('View All') }}
                </flux:button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Reference') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Tenant') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Invoice') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Method') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->recentPayments as $payment)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm">{{ $payment['paid_at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono">{{ $payment['reference'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">{{ $payment['tenant'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono">{{ $payment['invoice'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">{{ $payment['method'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium">{{ $payment['amount'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('No recent payments') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
