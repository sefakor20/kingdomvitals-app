<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Member Growth Trends') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Membership growth over :months months', ['months' => $months]) }}
                </flux:text>
            </div>
        </div>

        <!-- Export Dropdown -->
        <flux:dropdown>
            <flux:button variant="primary" icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="exportCsv" icon="document-text">
                    {{ __('Export CSV') }}
                </flux:menu.item>
                <flux:menu.item wire:click="exportExcel" icon="table-cells">
                    {{ __('Export Excel') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    <!-- Period Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-2">
                <flux:button
                    :variant="$months === 6 ? 'primary' : 'ghost'"
                    wire:click="setMonths(6)"
                    size="sm"
                >
                    {{ __('6 Months') }}
                </flux:button>
                <flux:button
                    :variant="$months === 12 ? 'primary' : 'ghost'"
                    wire:click="setMonths(12)"
                    size="sm"
                >
                    {{ __('1 Year') }}
                </flux:button>
                <flux:button
                    :variant="$months === 24 ? 'primary' : 'ghost'"
                    wire:click="setMonths(24)"
                    size="sm"
                >
                    {{ __('2 Years') }}
                </flux:button>
            </div>
            <div>
                <flux:switch
                    wire:model.live="showComparison"
                    wire:click="toggleComparison"
                    label="{{ __('Compare with previous year') }}"
                />
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total New Members') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['total_new']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('in this period') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Monthly Average') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->summaryStats['avg_per_month'] }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members/month') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Growth Rate') }}</div>
            <div class="text-2xl font-bold {{ $this->summaryStats['growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->summaryStats['growth_rate'] >= 0 ? '+' : '' }}{{ $this->summaryStats['growth_rate'] }}%
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('over period') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Current Total') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['current_members']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('active members') }}</div>
        </div>
    </div>

    @if($showComparison && $this->summaryStats['yoy_change'] !== null)
        <flux:callout class="mb-6" :variant="$this->summaryStats['yoy_change'] >= 0 ? 'success' : 'warning'">
            <flux:callout.heading>{{ __('Year-over-Year Comparison') }}</flux:callout.heading>
            <flux:callout.text>
                @if($this->summaryStats['yoy_change'] >= 0)
                    {{ __('Membership acquisition is up :percent% compared to the same period last year.', ['percent' => $this->summaryStats['yoy_change']]) }}
                @else
                    {{ __('Membership acquisition is down :percent% compared to the same period last year.', ['percent' => abs($this->summaryStats['yoy_change'])]) }}
                @endif
            </flux:callout.text>
        </flux:callout>
    @endif

    <!-- Charts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- New Members Trend -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('New Members Trend') }}</flux:heading>
            <div class="h-64">
                <canvas
                    id="growthChart"
                    wire:ignore
                    x-data="{
                        chart: null,
                        init() {
                            this.renderChart();
                            Livewire.on('charts-updated', () => {
                                this.$nextTick(() => this.renderChart());
                            });
                        },
                        renderChart() {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            const ctx = document.getElementById('growthChart');
                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: @js($this->chartData['labels']),
                                    datasets: @js($this->chartData['datasets'])
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: {{ $showComparison ? 'true' : 'false' }},
                                            position: 'bottom'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }"
                ></canvas>
            </div>
        </div>

        <!-- Cumulative Growth -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Cumulative Membership') }}</flux:heading>
            <div class="h-64">
                <canvas
                    id="cumulativeChart"
                    wire:ignore
                    x-data="{
                        chart: null,
                        init() {
                            this.renderChart();
                            Livewire.on('charts-updated', () => {
                                this.$nextTick(() => this.renderChart());
                            });
                        },
                        renderChart() {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            const ctx = document.getElementById('cumulativeChart');
                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: @js($this->cumulativeChartData['labels']),
                                    datasets: [{
                                        label: '{{ __('Total Members') }}',
                                        data: @js($this->cumulativeChartData['data']),
                                        borderColor: 'rgb(34, 197, 94)',
                                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                        fill: true,
                                        tension: 0.3,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: false
                                        }
                                    }
                                }
                            });
                        }
                    }"
                ></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown Table -->
    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Monthly Breakdown') }}</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Month') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('New Members') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Cumulative Total') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Change') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php
                        $previousCumulative = null;
                    @endphp
                    @foreach($this->growthData as $data)
                        @php
                            $change = $previousCumulative !== null
                                ? round((($data['cumulative'] - $previousCumulative) / max($previousCumulative, 1)) * 100, 1)
                                : 0;
                            $previousCumulative = $data['cumulative'];
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $data['month'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                @if($data['new_members'] > 0)
                                    <span class="text-green-600 dark:text-green-400">+{{ $data['new_members'] }}</span>
                                @else
                                    {{ $data['new_members'] }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($data['cumulative']) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                @if($change > 0)
                                    <span class="text-green-600 dark:text-green-400">+{{ $change }}%</span>
                                @elseif($change < 0)
                                    <span class="text-red-600 dark:text-red-400">{{ $change }}%</span>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
