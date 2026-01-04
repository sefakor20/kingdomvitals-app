<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Monthly Attendance Comparison') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Compare attendance across months') }}
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

    <!-- Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-2">
                <flux:button
                    :variant="$months === 3 ? 'primary' : 'ghost'"
                    wire:click="setMonths(3)"
                    size="sm"
                >
                    {{ __('3 Months') }}
                </flux:button>
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
                    {{ __('12 Months') }}
                </flux:button>
            </div>
            <div>
                <flux:switch
                    wire:model.live="showYoY"
                    wire:click="toggleYoY"
                    label="{{ __('Compare with previous year') }}"
                />
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Attendance') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['total']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('over :months months', ['months' => $months]) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Monthly Average') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['avg_monthly']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('per month') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Highest Month') }}</div>
            @if($this->summaryStats['highest'])
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->summaryStats['highest']['total']) }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->summaryStats['highest']['month'] }}</div>
            @else
                <div class="text-2xl font-bold text-zinc-400">-</div>
            @endif
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Trend') }}</div>
            <div class="text-2xl font-bold {{ $this->summaryStats['trend'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->summaryStats['trend'] >= 0 ? '+' : '' }}{{ $this->summaryStats['trend'] }}%
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('first to last month') }}</div>
        </div>
    </div>

    <!-- Chart -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Monthly Comparison') }}</flux:heading>
        <div class="h-80">
            <canvas
                id="monthlyChart"
                wire:ignore
                x-data="{
                    chart: null,
                    init() {
                        this.renderChart();
                    },
                    renderChart() {
                        if (this.chart) {
                            this.chart.destroy();
                        }
                        const ctx = document.getElementById('monthlyChart');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($this->chartData['labels']),
                                datasets: @js($this->chartData['datasets'])
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: {{ $showYoY ? 'true' : 'false' }},
                                        position: 'bottom'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }"
            ></canvas>
        </div>
    </div>

    <!-- Monthly Breakdown Table -->
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
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
                            {{ __('Members') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Visitors') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Total') }}
                        </th>
                        @if($showYoY)
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Last Year') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('YoY Change') }}
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->monthlyData as $index => $item)
                        @php
                            $yoyItem = $showYoY && $this->yoyData ? $this->yoyData[$index] ?? null : null;
                            $yoyChange = $yoyItem && $yoyItem['total'] > 0
                                ? round((($item['total'] - $yoyItem['total']) / $yoyItem['total']) * 100, 1)
                                : null;
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item['month'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-blue-600 dark:text-blue-400">
                                {{ number_format($item['members']) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                {{ number_format($item['visitors']) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($item['total']) }}
                            </td>
                            @if($showYoY)
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $yoyItem ? number_format($yoyItem['total']) : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    @if($yoyChange !== null)
                                        <span class="{{ $yoyChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $yoyChange >= 0 ? '+' : '' }}{{ $yoyChange }}%
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
