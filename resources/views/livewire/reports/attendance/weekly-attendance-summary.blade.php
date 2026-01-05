<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Weekly Attendance Summary') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $this->weekLabel }}
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

    <!-- Week Navigation -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:button wire:click="previousWeek" icon="chevron-left" variant="ghost">
                {{ __('Previous') }}
            </flux:button>
            <div class="flex items-center gap-4">
                <flux:heading size="lg">{{ $this->weekLabel }}</flux:heading>
                <flux:button wire:click="goToCurrentWeek" size="sm" variant="ghost">
                    {{ __('Today') }}
                </flux:button>
            </div>
            <flux:button wire:click="nextWeek" icon="chevron-right" icon-trailing variant="ghost">
                {{ __('Next') }}
            </flux:button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Attendance') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->weeklyTotals['total']) }}</div>
            <div class="text-xs {{ $this->weeklyTotals['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                @if($this->weeklyTotals['change'] >= 0)
                    <span>↑ {{ $this->weeklyTotals['change'] }}%</span>
                @else
                    <span>↓ {{ abs($this->weeklyTotals['change']) }}%</span>
                @endif
                {{ __('vs last week') }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Members') }}</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->weeklyTotals['members']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('check-ins') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Visitors') }}</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->weeklyTotals['visitors']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('check-ins') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Daily Average') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->weeklyTotals['daily_average'] }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('per day') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Chart -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Daily Attendance') }}</flux:heading>
            <div class="h-64">
                <canvas
                    id="weeklyChart"
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
                            const ctx = document.getElementById('weeklyChart');
                            this.chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: @js($this->chartData['labels']),
                                    datasets: [
                                        {
                                            label: '{{ __('Members') }}',
                                            data: @js($this->chartData['members']),
                                            backgroundColor: 'rgb(59, 130, 246)',
                                            borderRadius: 4,
                                        },
                                        {
                                            label: '{{ __('Visitors') }}',
                                            data: @js($this->chartData['visitors']),
                                            backgroundColor: 'rgb(34, 197, 94)',
                                            borderRadius: 4,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    },
                                    scales: {
                                        x: {
                                            stacked: true,
                                        },
                                        y: {
                                            stacked: true,
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

        <!-- Service Breakdown -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('By Service') }}</flux:heading>
            @if($this->serviceBreakdown->isNotEmpty())
                <div class="space-y-3">
                    @foreach($this->serviceBreakdown as $service)
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $service->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($service->service_type) }}</div>
                            </div>
                            <flux:badge size="sm">{{ $service->attendance_count }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('No service attendance recorded.') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Daily Breakdown') }}</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Day') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->dailyBreakdown as $day)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $day['day_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $day['short_date'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-blue-600 dark:text-blue-400">
                                {{ number_format($day['members']) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                {{ number_format($day['visitors']) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($day['total']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ __('Weekly Total') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($this->weeklyTotals['members']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-green-600 dark:text-green-400">
                            {{ number_format($this->weeklyTotals['visitors']) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->weeklyTotals['total']) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>
