<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Usage Analytics') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ __('Platform-wide usage metrics and tenant analytics') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <!-- Period Selector -->
            <flux:dropdown>
                <flux:button variant="ghost" icon="calendar">{{ $this->periodLabel }}</flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="setPeriod(7)">{{ __('Last 7 days') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(30)">{{ __('Last 30 days') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(90)">{{ __('Last 90 days') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export CSV') }}
            </flux:button>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <!-- Total Tenants -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                    <flux:icon.building-office-2 class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Tenants') }}</flux:text>
                    <flux:heading size="lg">{{ $this->overviewStats['totalTenants'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                    <flux:icon.check-badge class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active') }}</flux:text>
                    <flux:heading size="lg">{{ $this->overviewStats['activeTenants'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Total Members -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                    <flux:icon.users class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Members') }}</flux:text>
                    <flux:heading size="lg">{{ number_format($this->overviewStats['totalMembers']) }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- SMS Sent -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                    <flux:icon.chat-bubble-left-right class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('SMS Sent') }}</flux:text>
                    <flux:heading size="lg">{{ number_format($this->overviewStats['totalSmsSent']) }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Donations -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900">
                    <flux:icon.currency-dollar class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Donations') }}</flux:text>
                    <flux:heading size="lg">{{ $this->overviewStats['totalDonations'] }}</flux:heading>
                </div>
            </div>
        </div>

        <!-- Avg Members/Tenant -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                    <flux:icon.chart-bar class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg Members') }}</flux:text>
                    <flux:heading size="lg">{{ $this->overviewStats['avgMembersPerTenant'] }}</flux:heading>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout: Quota Alerts & Feature Adoption -->
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Quota Alerts -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Quota Alerts') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Tenants approaching their plan limits (>80%)') }}</flux:text>
            </div>
            <div class="p-6">
                @forelse($this->tenantsApproachingLimits as $alert)
                    <div wire:key="alert-{{ $alert['tenant']->id }}" class="mb-4 last:mb-0">
                        <div class="mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">{{ $alert['tenant']->name }}</flux:text>
                                <flux:badge color="zinc" size="sm">{{ $alert['tenant']->subscriptionPlan?->name ?? 'No Plan' }}</flux:badge>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($alert['alerts'] as $quotaAlert)
                                <flux:badge color="{{ $quotaAlert['usage'] >= 95 ? 'red' : 'amber' }}" size="sm">
                                    {{ ucfirst($quotaAlert['type']) }}: {{ $quotaAlert['usage'] }}%
                                </flux:badge>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:icon.check-circle class="mx-auto size-12 text-green-500" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('All tenants within limits') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Feature Adoption -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Feature Adoption') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Module usage across all tenants') }}</flux:text>
            </div>
            <div class="p-6">
                @forelse($this->featureAdoption as $key => $module)
                    <div wire:key="feature-{{ $key }}" class="mb-4 last:mb-0">
                        <div class="mb-1 flex items-center justify-between">
                            <flux:text class="text-sm font-medium">{{ $module['label'] }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $module['count'] }} {{ __('tenants') }} ({{ $module['percentage'] }}%)</flux:text>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-2 rounded-full bg-indigo-600 transition-all duration-300"
                                style="width: {{ $module['percentage'] }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:text class="text-zinc-500">{{ __('No usage data available') }}</flux:text>
                        <flux:text class="mt-1 text-xs text-zinc-400">{{ __('Run analytics:aggregate-usage to collect data') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Activity Trends Chart -->
    @if(count($this->activityTrends['labels']) > 0)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Activity Trends') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Platform activity over the last 30 days') }}</flux:text>
            </div>
            <div class="p-6">
                <div
                    x-data="activityTrendsChart(@js($this->activityTrends))"
                    x-init="initChart()"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    @endif

    <!-- Top Tenants Table -->
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Top Tenants') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('Most active tenants by engagement metrics') }}</flux:text>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                                {{ __('Tenant') }}
                                @if($sortBy === 'name')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Plan') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <button wire:click="sortBy('active_members')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                                {{ __('Members') }}
                                @if($sortBy === 'active_members')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <button wire:click="sortBy('sms_sent_this_month')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                                {{ __('SMS') }}
                                @if($sortBy === 'sms_sent_this_month')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <button wire:click="sortBy('donations_this_month')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                                {{ __('Donations') }}
                                @if($sortBy === 'donations_this_month')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                            <button wire:click="sortBy('attendance_this_month')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                                {{ __('Attendance') }}
                                @if($sortBy === 'attendance_this_month')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->topTenants as $item)
                        <tr wire:key="tenant-{{ $item['tenant']?->id ?? 'unknown' }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $item['tenant']?->name ?? 'Unknown' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="zinc" size="sm">{{ $item['plan']?->name ?? 'No Plan' }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ number_format($item['active_members']) }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ number_format($item['sms_sent']) }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-semibold">{{ $item['donations'] }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ number_format($item['attendance']) }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $status = $item['tenant']?->status;
                                    $statusColor = match($status?->value ?? '') {
                                        'active' => 'green',
                                        'trial' => 'amber',
                                        'suspended' => 'red',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">
                                    {{ ucfirst($status?->value ?? 'Unknown') }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <flux:text class="text-zinc-500">{{ __('No tenant data available') }}</flux:text>
                                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('Run "php artisan analytics:aggregate-usage" to collect data') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('activityTrendsChart', (data) => ({
        chart: null,
        data: data,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [
                        {
                            label: 'Total Members',
                            data: this.data.members,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'SMS Sent',
                            data: this.data.sms,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                color: isDark ? '#27272a' : '#e4e4e7',
                            },
                            ticks: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDark ? '#27272a' : '#e4e4e7',
                            },
                            ticks: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
