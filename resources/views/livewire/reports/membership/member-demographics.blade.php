<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Member Demographics') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Analysis of :count active members', ['count' => number_format($this->totalMembers)]) }}
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

    <!-- Summary Stats -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Active Members') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->totalMembers) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Average Age') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $this->averageAge ? $this->averageAge . ' ' . __('years') : __('N/A') }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Top Gender') }}</div>
            @php
                $topGender = collect($this->genderDistribution)->sortDesc()->keys()->first();
            @endphp
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $topGender ?? __('N/A') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Top Age Group') }}</div>
            @php
                $topAgeGroup = collect($this->ageDistribution)->filter(fn($v, $k) => $k !== 'Unknown')->sortDesc()->keys()->first();
            @endphp
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $topAgeGroup ?? __('N/A') }}</div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Gender Distribution -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Gender Distribution') }}</flux:heading>
            <div class="flex items-center gap-6">
                <div class="h-48 w-48">
                    <canvas
                        id="genderChart"
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
                                const ctx = document.getElementById('genderChart');
                                this.chart = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: @js($this->genderChartData['labels']),
                                        datasets: [{
                                            data: @js($this->genderChartData['data']),
                                            backgroundColor: @js($this->genderChartData['colors']),
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
                    @foreach($this->genderDistribution as $gender => $count)
                        @php
                            $percentage = $this->totalMembers > 0 ? round(($count / $this->totalMembers) * 100, 1) : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-3 rounded-full" style="background-color: {{ match($gender) {
                                    'Male' => 'rgb(59, 130, 246)',
                                    'Female' => 'rgb(236, 72, 153)',
                                    'Other' => 'rgb(168, 85, 247)',
                                    default => 'rgb(156, 163, 175)',
                                } }}"></div>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $gender }}</span>
                            </div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($count) }} ({{ $percentage }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Marital Status Distribution -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Marital Status') }}</flux:heading>
            <div class="flex items-center gap-6">
                <div class="h-48 w-48">
                    <canvas
                        id="maritalChart"
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
                                const ctx = document.getElementById('maritalChart');
                                this.chart = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: @js($this->maritalChartData['labels']),
                                        datasets: [{
                                            data: @js($this->maritalChartData['data']),
                                            backgroundColor: @js($this->maritalChartData['colors']),
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
                    @foreach($this->maritalStatusDistribution as $status => $count)
                        @php
                            $percentage = $this->totalMembers > 0 ? round(($count / $this->totalMembers) * 100, 1) : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-3 rounded-full" style="background-color: {{ match($status) {
                                    'Single' => 'rgb(34, 197, 94)',
                                    'Married' => 'rgb(59, 130, 246)',
                                    'Divorced' => 'rgb(249, 115, 22)',
                                    'Widowed' => 'rgb(107, 114, 128)',
                                    'Separated' => 'rgb(236, 72, 153)',
                                    default => 'rgb(156, 163, 175)',
                                } }}"></div>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $status }}</span>
                            </div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($count) }} ({{ $percentage }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Age Distribution -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <flux:heading size="lg" class="mb-4">{{ __('Age Distribution') }}</flux:heading>
            <div class="h-64">
                <canvas
                    id="ageChart"
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
                            const ctx = document.getElementById('ageChart');
                            this.chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: @js($this->ageChartData['labels']),
                                    datasets: [{
                                        label: '{{ __('Members') }}',
                                        data: @js($this->ageChartData['data']),
                                        backgroundColor: 'rgb(59, 130, 246)',
                                        borderRadius: 4,
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
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                @foreach($this->ageDistribution as $ageGroup => $count)
                    @php
                        $percentage = $this->totalMembers > 0 ? round(($count / $this->totalMembers) * 100, 1) : 0;
                    @endphp
                    <div class="text-center">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ageGroup }}</div>
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($count) }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $percentage }}%</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
