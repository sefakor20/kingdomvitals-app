<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Financial Reports') }}</flux:heading>
            <flux:subheading>{{ __('Financial analytics and insights for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                {{ __('Export CSV') }}
            </flux:button>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="flex flex-wrap gap-2">
            <flux:button
                variant="{{ $period === 7 ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setPeriod(7)"
            >
                {{ __('7 Days') }}
            </flux:button>
            <flux:button
                variant="{{ $period === 30 ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setPeriod(30)"
            >
                {{ __('30 Days') }}
            </flux:button>
            <flux:button
                variant="{{ $period === 90 ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setPeriod(90)"
            >
                {{ __('90 Days') }}
            </flux:button>
            <flux:button
                variant="{{ $period === 365 ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setPeriod(365)"
            >
                {{ __('Year') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            <flux:input type="date" wire:model="dateFrom" size="sm" />
            <span class="text-zinc-500">{{ __('to') }}</span>
            <flux:input type="date" wire:model="dateTo" size="sm" />
            <flux:button size="sm" wire:click="applyCustomDateRange">{{ __('Apply') }}</flux:button>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Income') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="arrow-trending-up" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                GHS {{ number_format($this->summaryStats['total_income'], 2) }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ $this->summaryStats['donation_count'] }} {{ __('donations') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="arrow-trending-down" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 text-red-600 dark:text-red-400">
                GHS {{ number_format($this->summaryStats['total_expenses'], 2) }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ $this->summaryStats['expense_count'] }} {{ __('expenses') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Net Position') }}</flux:text>
                <div class="rounded-full {{ $this->summaryStats['net_position'] >= 0 ? 'bg-blue-100 dark:bg-blue-900' : 'bg-orange-100 dark:bg-orange-900' }} p-2">
                    <flux:icon icon="scale" class="size-4 {{ $this->summaryStats['net_position'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 {{ $this->summaryStats['net_position'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}">
                GHS {{ number_format($this->summaryStats['net_position'], 2) }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ __('Income - Expenses') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pledge Fulfillment') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="hand-raised" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 text-purple-600 dark:text-purple-400">
                {{ $this->summaryStats['pledge_fulfillment'] }}%
            </flux:heading>
            <flux:text class="text-xs text-zinc-500">{{ __('Active pledges') }}</flux:text>
        </div>
    </div>

    <!-- Report Type Tabs -->
    <div class="mb-6 flex flex-wrap gap-2 border-b border-zinc-200 pb-4 dark:border-zinc-700">
        <flux:button
            variant="{{ $reportType === 'summary' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setReportType('summary')"
            icon="chart-bar"
        >
            {{ __('Summary') }}
        </flux:button>
        <flux:button
            variant="{{ $reportType === 'donations' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setReportType('donations')"
            icon="banknotes"
        >
            {{ __('Donations') }}
        </flux:button>
        <flux:button
            variant="{{ $reportType === 'expenses' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setReportType('expenses')"
            icon="credit-card"
        >
            {{ __('Expenses') }}
        </flux:button>
        <flux:button
            variant="{{ $reportType === 'pledges' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setReportType('pledges')"
            icon="hand-raised"
        >
            {{ __('Pledges') }}
        </flux:button>
    </div>

    <!-- Report Content -->
    @if($reportType === 'summary')
        <!-- Summary Report -->
        <div class="grid gap-6 lg:grid-cols-2" wire:key="report-summary-{{ $period }}-{{ $dateFrom }}-{{ $dateTo }}">
            <!-- Income vs Expenses Chart -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Income vs Expenses') }}</flux:heading>
                <div
                    x-data="incomeVsExpensesChart(@js($this->incomeVsExpensesData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->incomeVsExpensesData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Monthly Net Trend -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Monthly Net Position') }}</flux:heading>
                <div
                    x-data="monthlyTrendChart(@js($this->monthlyTrendData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->monthlyTrendData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

    @elseif($reportType === 'donations')
        <!-- Donations Report -->
        <div class="grid gap-6 lg:grid-cols-2" wire:key="report-donations-{{ $period }}-{{ $dateFrom }}-{{ $dateTo }}">
            <!-- Donations by Type -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Donations by Type') }}</flux:heading>
                @if(count($this->donationsByTypeData['data']) > 0)
                    <div
                        x-data="donationsByTypeChart(@js($this->donationsByTypeData))"
                        x-init="initChart()"
                        @charts-updated.window="updateChart(@js($this->donationsByTypeData))"
                        class="h-64"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center text-zinc-500">
                        {{ __('No donation data available') }}
                    </div>
                @endif
            </div>

            <!-- Donations by Payment Method -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Donations by Payment Method') }}</flux:heading>
                @if(count($this->donationsByPaymentMethodData['data']) > 0)
                    <div
                        x-data="donationsByPaymentChart(@js($this->donationsByPaymentMethodData))"
                        x-init="initChart()"
                        @charts-updated.window="updateChart(@js($this->donationsByPaymentMethodData))"
                        class="h-64"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center text-zinc-500">
                        {{ __('No donation data available') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Top Donors Table -->
        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Top 10 Donors') }}</flux:heading>
            @if($this->topDonorsData->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500">#</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500">{{ __('Member') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500">{{ __('Total Amount') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500">{{ __('Donations') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->topDonorsData as $index => $donor)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="donor-{{ $donor->member_id }}">
                                    <td class="px-4 py-3 text-sm text-zinc-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" name="{{ $donor->member?->fullName() ?? 'Unknown' }}" />
                                            <flux:text class="text-sm text-zinc-900 dark:text-zinc-100">
                                                {{ $donor->member?->fullName() ?? 'Unknown' }}
                                            </flux:text>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:text class="font-medium text-green-600 dark:text-green-400">
                                            GHS {{ number_format($donor->total_amount, 2) }}
                                        </flux:text>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-zinc-500">
                                        {{ $donor->donation_count }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-zinc-500">
                    {{ __('No donation data available for this period') }}
                </div>
            @endif
        </div>

    @elseif($reportType === 'expenses')
        <!-- Expenses Report -->
        <div class="grid gap-6 lg:grid-cols-2" wire:key="report-expenses-{{ $period }}-{{ $dateFrom }}-{{ $dateTo }}">
            <!-- Expenses by Category -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Expenses by Category') }}</flux:heading>
                @if(count($this->expensesByCategoryData['data']) > 0)
                    <div
                        x-data="expensesByCategoryChart(@js($this->expensesByCategoryData))"
                        x-init="initChart()"
                        @charts-updated.window="updateChart(@js($this->expensesByCategoryData))"
                        class="h-64"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center text-zinc-500">
                        {{ __('No expense data available') }}
                    </div>
                @endif
            </div>

            <!-- Expenses by Status -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Expenses by Status') }}</flux:heading>
                @if(count($this->expensesByStatusData['data']) > 0)
                    <div
                        x-data="expensesByStatusChart(@js($this->expensesByStatusData))"
                        x-init="initChart()"
                        @charts-updated.window="updateChart(@js($this->expensesByStatusData))"
                        class="h-64"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center text-zinc-500">
                        {{ __('No expense data available') }}
                    </div>
                @endif
            </div>
        </div>

    @elseif($reportType === 'pledges')
        <!-- Pledges Report -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Pledge Stats Cards -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">{{ __('Total Pledged') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-purple-600 dark:text-purple-400">
                    GHS {{ number_format($this->pledgeFulfillmentData['total_pledged'], 2) }}
                </flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">{{ __('Total Fulfilled') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-green-600 dark:text-green-400">
                    GHS {{ number_format($this->pledgeFulfillmentData['total_fulfilled'], 2) }}
                </flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">{{ __('Outstanding') }}</flux:text>
                <flux:heading size="xl" class="mt-1 text-orange-600 dark:text-orange-400">
                    GHS {{ number_format($this->pledgeFulfillmentData['outstanding'], 2) }}
                </flux:heading>
            </div>
        </div>

        <!-- Pledge Status Summary -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Pledge Status') }}</flux:heading>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="size-3 rounded-full bg-blue-500"></div>
                            <flux:text>{{ __('Active') }}</flux:text>
                        </div>
                        <flux:text class="font-medium">{{ $this->pledgeFulfillmentData['active_count'] }}</flux:text>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="size-3 rounded-full bg-green-500"></div>
                            <flux:text>{{ __('Completed') }}</flux:text>
                        </div>
                        <flux:text class="font-medium">{{ $this->pledgeFulfillmentData['completed_count'] }}</flux:text>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="size-3 rounded-full bg-red-500"></div>
                            <flux:text>{{ __('Cancelled') }}</flux:text>
                        </div>
                        <flux:text class="font-medium">{{ $this->pledgeFulfillmentData['cancelled_count'] }}</flux:text>
                    </div>
                </div>

                <!-- Fulfillment Progress Bar -->
                <div class="mt-6">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:text class="text-sm text-zinc-500">{{ __('Fulfillment Rate') }}</flux:text>
                        <flux:text class="font-medium">{{ $this->pledgeFulfillmentData['fulfillment_rate'] }}%</flux:text>
                    </div>
                    <div class="h-4 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-purple-500 to-purple-600 transition-all duration-500"
                            style="width: {{ min($this->pledgeFulfillmentData['fulfillment_rate'], 100) }}%"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Pledges Table -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Top Outstanding Pledges') }}</flux:heading>
                @if($this->outstandingPledgesData->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->outstandingPledgesData as $pledge)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800" wire:key="pledge-{{ $pledge->id }}">
                                <div>
                                    <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $pledge->member?->fullName() ?? 'Unknown' }}
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-500">{{ $pledge->campaign_name }}</flux:text>
                                </div>
                                <div class="text-right">
                                    <flux:text class="font-medium text-orange-600 dark:text-orange-400">
                                        GHS {{ number_format($pledge->remainingAmount(), 2) }}
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ $pledge->completionPercentage() }}% {{ __('fulfilled') }}
                                    </flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-zinc-500">
                        {{ __('No outstanding pledges') }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endassets

@script
<script>
    // Income vs Expenses Bar Chart
    Alpine.data('incomeVsExpensesChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

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
                                callback: (value) => 'GHS ' + value.toLocaleString(),
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
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.income;
            this.chart.data.datasets[1].data = newData.expenses;
            this.chart.update();
        }
    }));

    // Monthly Trend Line Chart
    Alpine.data('monthlyTrendChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [{
                        label: 'Net Position',
                        data: this.data.data,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: (value) => 'GHS ' + value.toLocaleString(),
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        },
                        x: {
                            ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.data;
            this.chart.update();
        }
    }));

    // Donations by Type Doughnut Chart
    Alpine.data('donationsByTypeChart', (initialData) => ({
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
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.data;
            this.chart.data.datasets[0].backgroundColor = newData.colors;
            this.chart.update();
        }
    }));

    // Donations by Payment Method Doughnut Chart
    Alpine.data('donationsByPaymentChart', (initialData) => ({
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
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.data;
            this.chart.data.datasets[0].backgroundColor = newData.colors;
            this.chart.update();
        }
    }));

    // Expenses by Category Doughnut Chart
    Alpine.data('expensesByCategoryChart', (initialData) => ({
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
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.data;
            this.chart.data.datasets[0].backgroundColor = newData.colors;
            this.chart.update();
        }
    }));

    // Expenses by Status Doughnut Chart
    Alpine.data('expensesByStatusChart', (initialData) => ({
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
        },

        updateChart(newData) {
            this.data = newData;
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.data;
            this.chart.data.datasets[0].backgroundColor = newData.colors;
            this.chart.update();
        }
    }));
</script>
@endscript
