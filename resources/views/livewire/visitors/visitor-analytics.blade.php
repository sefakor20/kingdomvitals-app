<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Visitor Analytics') }}</flux:heading>
            <flux:subheading>{{ __('Analytics and insights for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('visitors.index', $branch)" icon="arrow-left" wire:navigate>
                {{ __('Back to Visitors') }}
            </flux:button>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="mb-6 flex flex-wrap gap-2">
        <flux:button
            variant="{{ $period === 30 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(30)"
        >
            {{ __('Last 30 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 60 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(60)"
        >
            {{ __('Last 60 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 90 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(90)"
        >
            {{ __('Last 90 Days') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 180 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(180)"
        >
            {{ __('Last 6 Months') }}
        </flux:button>
        <flux:button
            variant="{{ $period === 365 ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="setPeriod(365)"
        >
            {{ __('Last Year') }}
        </flux:button>
    </div>

    <!-- Summary Stats -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Visitors') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="users" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->summaryStats['total_visitors']) }}</flux:heading>
            @if($this->summaryStats['visitor_growth'] != 0)
                <flux:text class="mt-1 text-sm {{ $this->summaryStats['visitor_growth'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $this->summaryStats['visitor_growth'] > 0 ? '+' : '' }}{{ $this->summaryStats['visitor_growth'] }}% {{ __('vs previous period') }}
                </flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Conversion Rate') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="arrow-trending-up" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->summaryStats['conversion_rate'] }}%</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ number_format($this->summaryStats['converted_visitors']) }} {{ __('converted') }}
            </flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Follow-up Success') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="phone" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->summaryStats['follow_up_success_rate'] }}%</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ number_format($this->summaryStats['successful_follow_ups']) }}/{{ number_format($this->summaryStats['total_follow_ups']) }} {{ __('successful') }}
            </flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg Days to Convert') }}</flux:text>
                <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                    <flux:icon icon="clock" class="size-4 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->summaryStats['avg_days_to_convert'] }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('days on average') }}
            </flux:text>
        </div>
    </div>

    @if($this->summaryStats['total_visitors'] === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="chart-bar" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No data available') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                {{ __('Add some visitors to see analytics here.') }}
            </flux:text>
        </div>
    @else
        <!-- Conversion Funnel -->
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Conversion Funnel') }}</flux:heading>
            <div class="flex flex-wrap items-center justify-center gap-2">
                @foreach($this->conversionFunnelData['labels'] as $index => $label)
                    <div class="flex items-center">
                        <div class="flex flex-col items-center rounded-lg px-4 py-3" style="background-color: {{ $this->conversionFunnelData['colors'][$index] }}20;">
                            <flux:heading size="lg" style="color: {{ $this->conversionFunnelData['colors'][$index] }}">
                                {{ number_format($this->conversionFunnelData['data'][$index]) }}
                            </flux:heading>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</flux:text>
                        </div>
                        @if($index < count($this->conversionFunnelData['labels']) - 1)
                            <flux:icon icon="chevron-right" class="mx-2 size-5 text-zinc-400" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <!-- Visitors Over Time (Line Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Visitors Over Time') }}</flux:heading>
                <div
                    x-data="visitorsOverTimeChart(@js($this->visitorsOverTimeData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->visitorsOverTimeData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Follow-up Effectiveness (Bar Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Follow-up Effectiveness by Type') }}</flux:heading>
                <div
                    x-data="followUpEffectivenessChart(@js($this->followUpEffectivenessData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->followUpEffectivenessData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Conversion Funnel Chart (Doughnut) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Status Distribution') }}</flux:heading>
                <div
                    x-data="statusDistributionChart(@js($this->conversionFunnelData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->conversionFunnelData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Follow-up Trend (Line Chart) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Follow-up Trend') }}</flux:heading>
                <div
                    x-data="followUpTrendChart(@js($this->followUpTrendData))"
                    x-init="initChart()"
                    @charts-updated.window="updateChart(@js($this->followUpTrendData))"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Source Analysis -->
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <!-- Source Chart (Doughnut) -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Visitor Sources') }}</flux:heading>
                @if(count($this->visitorSourceData['labels']) > 0)
                    <div
                        x-data="visitorSourceChart(@js($this->visitorSourceData))"
                        x-init="initChart()"
                        @charts-updated.window="updateChart(@js($this->visitorSourceData))"
                        class="h-64"
                        wire:ignore
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @else
                    <div class="flex h-64 items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No source data available') }}</flux:text>
                    </div>
                @endif
            </div>

            <!-- Source Table -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Source Performance') }}</flux:heading>
                @if(count($this->visitorSourceData['table_data']) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Source') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500 dark:text-zinc-400">{{ __('Converted') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500 dark:text-zinc-400">{{ __('Rate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->visitorSourceData['table_data'] as $source)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="py-3 text-zinc-900 dark:text-zinc-100">{{ $source['source'] }}</td>
                                        <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($source['visitors']) }}</td>
                                        <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($source['converted']) }}</td>
                                        <td class="py-3 text-right">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $source['rate'] >= 50 ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : ($source['rate'] >= 25 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300') }}">
                                                {{ $source['rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex h-48 items-center justify-center">
                        <flux:text class="text-zinc-500">{{ __('No source data available') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Conversions -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Conversions') }}</flux:heading>
            @if($this->recentConversions->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                                <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Visit Date') }}</th>
                                <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Converted') }}</th>
                                <th class="pb-3 text-right font-medium text-zinc-500 dark:text-zinc-400">{{ __('Days') }}</th>
                                <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Source') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->recentConversions as $conversion)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="py-3">
                                        <a href="{{ route('visitors.show', [$branch, $conversion['id']]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                            {{ $conversion['name'] }}
                                        </a>
                                    </td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $conversion['visit_date'] }}</td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $conversion['converted_at'] }}</td>
                                    <td class="py-3 text-right">
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                            {{ $conversion['days_to_convert'] }} {{ __('days') }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $conversion['source'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex h-32 items-center justify-center">
                    <flux:text class="text-zinc-500">{{ __('No recent conversions') }}</flux:text>
                </div>
            @endif
        </div>
    @endif
</section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endassets

@script
<script>
    // Visitors Over Time Line Chart
    Alpine.data('visitorsOverTimeChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [
                        {
                            label: 'Total Visitors',
                            data: this.data.visitors,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'Converted',
                            data: this.data.converted,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
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
            this.chart.data.datasets[0].data = newData.visitors;
            this.chart.data.datasets[1].data = newData.converted;
            this.chart.update();
        }
    }));

    // Follow-up Effectiveness Bar Chart
    Alpine.data('followUpEffectivenessChart', (initialData) => ({
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
                            label: 'Total Attempts',
                            data: this.data.total_attempts,
                            backgroundColor: '#94a3b8',
                            borderRadius: 4,
                        },
                        {
                            label: 'Successful',
                            data: this.data.successful,
                            backgroundColor: '#22c55e',
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
            this.chart.data.datasets[0].data = newData.total_attempts;
            this.chart.data.datasets[1].data = newData.successful;
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

    // Follow-up Trend Line Chart
    Alpine.data('followUpTrendChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.data.labels,
                    datasets: [
                        {
                            label: 'Completed',
                            data: this.data.completed,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'Pending',
                            data: this.data.pending,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            fill: true,
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
            this.chart.data.datasets[0].data = newData.completed;
            this.chart.data.datasets[1].data = newData.pending;
            this.chart.update();
        }
    }));

    // Visitor Source Doughnut Chart
    Alpine.data('visitorSourceChart', (initialData) => ({
        chart: null,
        data: initialData,

        initChart() {
            const ctx = this.$refs.canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');

            const colors = [
                '#3b82f6', // blue
                '#22c55e', // green
                '#f59e0b', // amber
                '#8b5cf6', // purple
                '#ec4899', // pink
                '#14b8a6', // teal
                '#f97316', // orange
                '#71717a', // zinc
            ];

            this.chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.data.labels,
                    datasets: [{
                        data: this.data.visitor_counts,
                        backgroundColor: colors.slice(0, this.data.labels.length),
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
            const colors = [
                '#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6',
                '#ec4899', '#14b8a6', '#f97316', '#71717a',
            ];
            this.chart.data.labels = newData.labels;
            this.chart.data.datasets[0].data = newData.visitor_counts;
            this.chart.data.datasets[0].backgroundColor = colors.slice(0, newData.labels.length);
            this.chart.update();
        }
    }));
</script>
@endscript
