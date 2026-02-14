<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('SMS Analytics') }}</flux:heading>
            <flux:subheading>{{ __('Analytics and insights for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('sms.index', $branch)" icon="arrow-left" wire:navigate>
                {{ __('Back to SMS') }}
            </flux:button>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="mb-6 flex flex-wrap gap-2">
        <flux:button
            variant="{{ $period === 7 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(7)"
        >
            {{ __('Last 7 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 30 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(30)"
        >
            {{ __('Last 30 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 90 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(90)"
        >
            {{ __('Last 90 Days') }}
        </flux:button>
    </div>

    <!-- Summary Stats -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Messages') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="paper-airplane" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->summaryStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Delivery Rate') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->summaryStats['delivery_rate'] }}%</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Failed') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="x-circle" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->summaryStats['failed']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Cost') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="banknotes" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->summaryStats['total_cost'], 2) }}</flux:heading>
        </div>
    </div>

    <!-- Charts Grid -->
    @if($this->summaryStats['total'] === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="chart-bar" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No data available') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                {{ __('Send some SMS messages to see analytics here.') }}
            </flux:text>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Delivery Rate Over Time (Line Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Delivery Rate Over Time') }}</flux:heading>
                <div
                    x-data="deliveryRateChart(@js($this->deliveryRateData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->deliveryRateData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Messages by Type (Doughnut Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Messages by Type') }}</flux:heading>
                <div
                    x-data="messagesByTypeChart(@js($this->messagesByTypeData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->messagesByTypeData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Status Distribution (Doughnut Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Status Distribution') }}</flux:heading>
                <div
                    x-data="statusDistributionChart(@js($this->statusDistributionData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->statusDistributionData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Daily Cost Trend (Bar Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Daily Cost Trend') }}</flux:heading>
                <div
                    x-data="dailyCostChart(@js($this->dailyCostData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->dailyCostData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    @endif
</section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endassets

@script
<script>
    // Delivery Rate Line Chart
    Alpine.data('deliveryRateChart', (initialData) => ({
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
                        label: 'Delivery Rate (%)',
                        data: this.data.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
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
                            max: 100,
                            ticks: {
                                callback: (value) => value + '%',
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: {
                                color: isDark ? '#27272a' : '#e4e4e7',
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: {
                                color: isDark ? '#27272a' : '#e4e4e7',
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        }
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

    // Messages by Type Doughnut Chart
    Alpine.data('messagesByTypeChart', (initialData) => ({
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
                        backgroundColor: [
                            '#ec4899', // pink - birthday
                            '#f59e0b', // yellow - reminder
                            '#22c55e', // green - welcome
                            '#3b82f6', // blue - announcement
                            '#8b5cf6', // purple - follow_up
                            '#71717a', // zinc - custom
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            }
                        }
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

    // Status Distribution Doughnut Chart
    Alpine.data('statusDistributionChart', (initialData) => ({
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
                            labels: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            }
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

    // Daily Cost Bar Chart
    Alpine.data('dailyCostChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.data.labels,
                    datasets: [{
                        label: 'Cost ({{ $this->currency->code() }})',
                        data: this.data.data,
                        backgroundColor: '#8b5cf6',
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => '{{ $this->currency->symbol() }}' + value.toFixed(2),
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: {
                                color: isDark ? '#27272a' : '#e4e4e7',
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#a1a1aa' : '#71717a',
                            },
                            grid: {
                                display: false,
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false,
                        }
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
</script>
@endscript
