<section class="w-full">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Financial Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Executive summary for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('finance.reports', $branch)" wire:navigate icon="document-chart-bar">
            {{ __('View Reports') }}
        </flux:button>
    </div>

    {{-- Current Month KPI Cards --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Current Month') }} ({{ now()->format('F Y') }})</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Monthly Income --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Income') }}</flux:text>
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="arrow-trending-up" class="size-4 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->monthlyStats['income'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ $this->monthlyStats['income_count'] }} {{ __('donations') }}</flux:text>
            </div>

            {{-- Monthly Expenses --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Expenses') }}</flux:text>
                    <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                        <flux:icon icon="arrow-trending-down" class="size-4 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-red-600 dark:text-red-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->monthlyStats['expenses'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ $this->monthlyStats['expenses_count'] }} {{ __('expenses') }}</flux:text>
            </div>

            {{-- Net Position --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Net Position') }}</flux:text>
                    <div class="rounded-full {{ $this->monthlyStats['net_position'] >= 0 ? 'bg-blue-100 dark:bg-blue-900' : 'bg-orange-100 dark:bg-orange-900' }} p-2">
                        <flux:icon icon="scale" class="size-4 {{ $this->monthlyStats['net_position'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 {{ $this->monthlyStats['net_position'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}">
                    {{ $this->currency->symbol() }}{{ number_format($this->monthlyStats['net_position'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Income - Expenses') }}</flux:text>
            </div>

            {{-- YTD Income with YoY Growth --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('YTD Income') }}</flux:text>
                    <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                        <flux:icon icon="chart-bar" class="size-4 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-purple-600 dark:text-purple-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->yearToDateStats['income'], 2) }}
                </flux:heading>
                <flux:text class="text-xs">
                    @if($this->yearToDateStats['income_growth_percent'] >= 0)
                        <span class="font-medium text-green-600 dark:text-green-400">+{{ $this->yearToDateStats['income_growth_percent'] }}%</span>
                    @else
                        <span class="font-medium text-red-600 dark:text-red-400">{{ $this->yearToDateStats['income_growth_percent'] }}%</span>
                    @endif
                    <span class="text-zinc-500">{{ __('vs last year') }}</span>
                </flux:text>
            </div>

            {{-- Outstanding Pledges --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Outstanding Pledges') }}</flux:text>
                    <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                        <flux:icon icon="hand-raised" class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-amber-600 dark:text-amber-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->outstandingPledgesTotal, 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Unfulfilled amount') }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Event Revenue Section --}}
    @if($this->eventRevenueStats['monthly_revenue'] > 0 || $this->eventRevenueStats['ytd_revenue'] > 0 || $this->eventRevenueStats['pending_payments'] > 0)
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Event Revenue') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Monthly Event Revenue --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Event Revenue') }}</flux:text>
                    <div class="rounded-full bg-pink-100 p-2 dark:bg-pink-900">
                        <flux:icon icon="ticket" class="size-4 text-pink-600 dark:text-pink-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-pink-600 dark:text-pink-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->eventRevenueStats['monthly_revenue'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ $this->eventRevenueStats['monthly_count'] }} {{ __('registrations') }}</flux:text>
            </div>

            {{-- YTD Event Revenue --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('YTD Event Revenue') }}</flux:text>
                    <div class="rounded-full bg-indigo-100 p-2 dark:bg-indigo-900">
                        <flux:icon icon="calendar" class="size-4 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-indigo-600 dark:text-indigo-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->eventRevenueStats['ytd_revenue'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Year to date') }}</flux:text>
            </div>

            {{-- Pending Event Payments --}}
            @if($this->eventRevenueStats['pending_payments'] > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-amber-700 dark:text-amber-400">{{ __('Pending Payments') }}</flux:text>
                    <div class="rounded-full bg-amber-200 p-2 dark:bg-amber-800">
                        <flux:icon icon="clock" class="size-4 text-amber-700 dark:text-amber-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-amber-700 dark:text-amber-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->eventRevenueStats['pending_payments'], 2) }}
                </flux:heading>
                <flux:text class="text-xs text-amber-600 dark:text-amber-500">{{ __('Awaiting payment') }}</flux:text>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Year-over-Year Comparison --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Year-over-Year Comparison') }}</flux:heading>
        <div class="grid gap-6 sm:grid-cols-3">
            {{-- YoY Income --}}
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Income Growth') }}</flux:text>
                <div class="mt-2 flex items-baseline gap-2">
                    <flux:heading size="xl" class="{{ $this->yearToDateStats['income_growth_percent'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->yearToDateStats['income_growth_percent'] >= 0 ? '+' : '' }}{{ $this->yearToDateStats['income_growth_percent'] }}%
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        ({{ $this->currency->symbol() }}{{ number_format($this->yearToDateStats['income_last_year'], 2) }} {{ __('last year') }})
                    </flux:text>
                </div>
            </div>

            {{-- YoY Expenses --}}
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Expense Change') }}</flux:text>
                <div class="mt-2 flex items-baseline gap-2">
                    <flux:heading size="xl" class="{{ $this->yearToDateStats['expenses_growth_percent'] <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->yearToDateStats['expenses_growth_percent'] >= 0 ? '+' : '' }}{{ $this->yearToDateStats['expenses_growth_percent'] }}%
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        ({{ $this->currency->symbol() }}{{ number_format($this->yearToDateStats['expenses_last_year'], 2) }} {{ __('last year') }})
                    </flux:text>
                </div>
            </div>

            {{-- YoY Donation Count --}}
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Donation Count Change') }}</flux:text>
                <div class="mt-2 flex items-baseline gap-2">
                    <flux:heading size="xl" class="{{ $this->yearToDateStats['donation_count_growth_percent'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $this->yearToDateStats['donation_count_growth_percent'] >= 0 ? '+' : '' }}{{ $this->yearToDateStats['donation_count_growth_percent'] }}%
                    </flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        ({{ $this->yearToDateStats['donation_count_last_year'] }} {{ __('last year') }})
                    </flux:text>
                </div>
            </div>
        </div>
    </div>

    {{-- Member Giving Statistics --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Member Giving Statistics') }} ({{ now()->format('F Y') }})</flux:heading>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Average Donation --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                    {{ $this->currency->symbol() }}{{ number_format($this->memberGivingStats['average_donation'], 2) }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Average Donation') }}</flux:text>
            </div>

            {{-- Unique Donors --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                    {{ $this->memberGivingStats['unique_donors'] }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Unique Donors') }}</flux:text>
            </div>

            {{-- Active Members --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-zinc-700 dark:text-zinc-300">
                    {{ $this->memberGivingStats['active_members'] }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Active Members') }}</flux:text>
            </div>

            {{-- Giving Percentage --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-purple-600 dark:text-purple-400">
                    {{ $this->memberGivingStats['giving_percentage'] }}%
                </flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('Members Giving') }}</flux:text>
            </div>

            {{-- First-time Donors --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">
                    {{ $this->memberGivingStats['first_time_donors'] }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ __('First-time Donors') }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        {{-- Monthly Income Trend Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Monthly Income Trend') }}</flux:heading>
            <div
                x-data="monthlyIncomeChart(@js($this->monthlyIncomeChartData), @js($this->currency->symbol()))"
                x-init="initChart()"
                class="h-64"
                wire:ignore
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Donation Types Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Donations by Type') }}</flux:heading>
            @if(count($this->donationTypesChartData['data']) > 0)
                <div
                    x-data="donationTypesChart(@js($this->donationTypesChartData))"
                    x-init="initChart()"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="flex h-64 items-center justify-center text-zinc-500">
                    {{ __('No donation data this month') }}
                </div>
            @endif
        </div>

        {{-- Income vs Expenses Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Income vs Expenses') }}</flux:heading>
            <div
                x-data="incomeVsExpensesChart(@js($this->incomeVsExpensesChartData), @js($this->currency->symbol()))"
                x-init="initChart()"
                class="h-64"
                wire:ignore
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Donation Growth Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Cumulative Donation Growth') }}</flux:heading>
            <div
                x-data="donationGrowthChart(@js($this->donationGrowthChartData), @js($this->currency->symbol()))"
                x-init="initChart()"
                class="h-64"
                wire:ignore
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Donors Tier Distribution --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Donor Distribution') }} ({{ now()->year }})</flux:heading>
        @if(array_sum($this->topDonorsTierData['counts']) > 0)
            <div class="grid gap-4 sm:grid-cols-4">
                @foreach($this->topDonorsTierData['tiers'] as $index => $tier)
                    <div class="rounded-lg border border-zinc-100 p-4 dark:border-zinc-800">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $tier }}</flux:text>
                        <flux:heading size="lg" class="mt-1 text-blue-600 dark:text-blue-400">
                            {{ $this->currency->symbol() }}{{ number_format($this->topDonorsTierData['amounts'][$index], 2) }}
                        </flux:heading>
                        <flux:text class="text-xs text-zinc-500">
                            {{ $this->topDonorsTierData['counts'][$index] }} {{ __('donors') }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                {{ __('No donor data available for this year') }}
            </div>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Quick Links') }}</flux:heading>
        <div class="flex flex-wrap gap-3">
            <flux:button variant="ghost" :href="route('finance.donor-engagement', $branch)" wire:navigate icon="heart">
                {{ __('Donor Engagement') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('donations.index', $branch)" wire:navigate icon="banknotes">
                {{ __('Donations') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('expenses.index', $branch)" wire:navigate icon="credit-card">
                {{ __('Expenses') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('pledges.index', $branch)" wire:navigate icon="hand-raised">
                {{ __('Pledges') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('budgets.index', $branch)" wire:navigate icon="calculator">
                {{ __('Budgets') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('finance.reports', $branch)" wire:navigate icon="document-chart-bar">
                {{ __('Reports') }}
            </flux:button>
        </div>
    </div>
</section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endassets

@script
<script>
    // Monthly Income Trend Line Chart (with YoY comparison)
    Alpine.data('monthlyIncomeChart', (initialData, currencySymbol) => ({
        chart: null,
        data: initialData,
        currencySymbol: currencySymbol,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const symbol = this.currencySymbol;

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [
                        {
                            label: 'This Year',
                            data: this.data.current_year,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'Last Year',
                            data: this.data.previous_year,
                            borderColor: '#a1a1aa',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0.4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => symbol + ' ' + value.toLocaleString(),
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        },
                        x: {
                            ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: isDark ? '#a1a1aa' : '#71717a' }
                        }
                    }
                }
            });
        }
    }));

    // Donation Types Doughnut Chart
    Alpine.data('donationTypesChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.data.labels,
                    datasets: [{
                        data: this.data.data,
                        backgroundColor: this.data.colors,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: isDark ? '#a1a1aa' : '#71717a' }
                        }
                    }
                }
            });
        }
    }));

    // Income vs Expenses Bar Chart
    Alpine.data('incomeVsExpensesChart', (initialData, currencySymbol) => ({
        chart: null,
        data: initialData,
        currencySymbol: currencySymbol,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const symbol = this.currencySymbol;

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.data.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: this.data.income,
                            backgroundColor: '#22c55e',
                            borderRadius: 4,
                        },
                        {
                            label: 'Expenses',
                            data: this.data.expenses,
                            backgroundColor: '#ef4444',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => symbol + ' ' + value.toLocaleString(),
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        },
                        x: {
                            ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: isDark ? '#a1a1aa' : '#71717a' }
                        }
                    }
                }
            });
        }
    }));

    // Donation Growth Area Chart
    Alpine.data('donationGrowthChart', (initialData, currencySymbol) => ({
        chart: null,
        data: initialData,
        currencySymbol: currencySymbol,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const symbol = this.currencySymbol;

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [{
                        label: 'Cumulative Total',
                        data: this.data.data,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.2)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => symbol + ' ' + value.toLocaleString(),
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        },
                        x: {
                            ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }));
</script>
@endscript
