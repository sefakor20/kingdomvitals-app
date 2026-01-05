<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Service-wise Attendance') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Attendance breakdown by service type') }}
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
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex gap-2">
                <flux:button
                    :variant="$period === 7 ? 'primary' : 'ghost'"
                    wire:click="setPeriod(7)"
                    size="sm"
                >
                    {{ __('7 Days') }}
                </flux:button>
                <flux:button
                    :variant="$period === 30 ? 'primary' : 'ghost'"
                    wire:click="setPeriod(30)"
                    size="sm"
                >
                    {{ __('30 Days') }}
                </flux:button>
                <flux:button
                    :variant="$period === 90 ? 'primary' : 'ghost'"
                    wire:click="setPeriod(90)"
                    size="sm"
                >
                    {{ __('90 Days') }}
                </flux:button>
            </div>
            <div class="flex items-end gap-2">
                <flux:input
                    type="date"
                    wire:model="dateFrom"
                    label="{{ __('From') }}"
                />
                <flux:input
                    type="date"
                    wire:model="dateTo"
                    label="{{ __('To') }}"
                />
                <flux:button wire:click="applyCustomDateRange" size="sm">
                    {{ __('Apply') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Attendance') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['total_attendance']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->periodLabel }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Active Services') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->summaryStats['total_services'] }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('with attendance') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Average Per Service') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['avg_per_service']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('attendees') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Top Service') }}</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->summaryStats['top_service'] }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($this->summaryStats['top_service_count']) }} {{ __('attendees') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Distribution Chart -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Attendance Distribution') }}</flux:heading>
            @if($this->serviceData->isNotEmpty())
                <div class="flex items-center gap-6">
                    <div class="h-48 w-48">
                        <canvas
                            id="distributionChart"
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
                                    const ctx = document.getElementById('distributionChart');
                                    this.chart = new Chart(ctx, {
                                        type: 'doughnut',
                                        data: {
                                            labels: @js($this->chartData['labels']),
                                            datasets: [{
                                                data: @js($this->chartData['data']),
                                                backgroundColor: @js($this->chartData['colors']),
                                                borderWidth: 0,
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: true,
                                            plugins: {
                                                legend: {
                                                    display: false
                                                }
                                            },
                                            cutout: '60%'
                                        }
                                    });
                                }
                            }"
                        ></canvas>
                    </div>
                    <div class="flex-1 space-y-2">
                        @foreach($this->serviceData->take(5) as $index => $service)
                            @php
                                $percentage = $this->summaryStats['total_attendance'] > 0
                                    ? round(($service->total_attendance / $this->summaryStats['total_attendance']) * 100, 1)
                                    : 0;
                                $colors = ['rgb(59, 130, 246)', 'rgb(34, 197, 94)', 'rgb(168, 85, 247)', 'rgb(249, 115, 22)', 'rgb(236, 72, 153)'];
                            @endphp
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full" style="background-color: {{ $colors[$index] ?? 'rgb(107, 114, 128)' }}"></div>
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $service->name }}</span>
                                </div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $percentage }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex h-48 items-center justify-center text-zinc-500 dark:text-zinc-400">
                    {{ __('No attendance data for this period.') }}
                </div>
            @endif
        </div>

        <!-- Trend Chart -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Attendance Trend') }}</flux:heading>
            @if($this->serviceData->isNotEmpty())
                <div class="h-48">
                    <canvas
                        id="trendChart"
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
                                const ctx = document.getElementById('trendChart');
                                this.chart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: @js($this->trendData['labels']),
                                        datasets: @js($this->trendData['datasets'])
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                                labels: {
                                                    boxWidth: 12
                                                }
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
            @else
                <div class="flex h-48 items-center justify-center text-zinc-500 dark:text-zinc-400">
                    {{ __('No attendance data for this period.') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Service Breakdown Table -->
    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Service Details') }}</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Service') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Total') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Unique Members') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Unique Visitors') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Days Held') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Avg/Day') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->serviceData as $service)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $service->name }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <flux:badge size="sm">{{ ucfirst($service->service_type) }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($service->total_attendance) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-blue-600 dark:text-blue-400">
                                {{ number_format($service->unique_members) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                {{ number_format($service->unique_visitors) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $service->service_days }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $service->avg_per_service }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No service attendance found for this period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
