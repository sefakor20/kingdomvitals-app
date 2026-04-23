<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('My Giving') }}</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ __('View your giving history and download statements.') }}
            </flux:text>
        </div>
        <flux:button href="{{ route('giving.form', $this->member->primaryBranch) }}" variant="primary" icon="heart">
            {{ __('Give Now') }}
        </flux:button>
    </div>

    {{-- Year Selector --}}
    <div class="mb-6 flex items-center gap-4">
        <flux:text class="font-medium">{{ __('Year:') }}</flux:text>
        <flux:select wire:model.live="year" class="w-32">
            @foreach($this->availableYears as $y)
                <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Summary Card --}}
    <flux:card class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
            <div>
                <flux:text class="text-sm text-zinc-500">{{ __('Total Giving in :year', ['year' => $year]) }}</flux:text>
                <flux:heading size="xl">{{ $this->currency->symbol() }}{{ number_format($this->yearlyTotal, 2) }}</flux:heading>
            </div>
            <flux:button wire:click="downloadStatement" variant="filled" icon="document-arrow-down">
                {{ __('Download Statement') }}
            </flux:button>
        </div>
    </flux:card>

    {{-- Monthly Breakdown --}}
    <flux:card class="mb-6">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Monthly Breakdown') }}</flux:heading>
        </div>

        @php
            $monthlyTotals = $this->monthlyTotals;
            $maxMonthly = max($monthlyTotals) ?: 1;
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $levelClasses = [
                0 => 'bg-zinc-100 dark:bg-zinc-700',
                1 => 'bg-emerald-100 dark:bg-emerald-900/50',
                2 => 'bg-emerald-300 dark:bg-emerald-700',
                3 => 'bg-emerald-500 dark:bg-emerald-500',
                4 => 'bg-emerald-700 dark:bg-emerald-400',
            ];
        @endphp

        <div x-data="{ tooltip: null }" class="relative px-6 py-5">
            <div class="flex items-end gap-1.5">
                @foreach($monthlyTotals as $month => $total)
                    @php
                        $ratio = $total > 0 ? $total / $maxMonthly : 0;
                        $level = match(true) {
                            $total == 0    => 0,
                            $ratio <= 0.25 => 1,
                            $ratio <= 0.50 => 2,
                            $ratio <= 0.75 => 3,
                            default        => 4,
                        };
                        $formatted = $this->currency->symbol().number_format($total, 2);
                        $monthName = $monthNames[$month - 1];
                        $isSelected = $selectedMonth === $month;
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-1.5">
                        <div
                            @mouseenter="tooltip = { month: '{{ $monthName }}', amount: '{{ $formatted }}' }"
                            @mouseleave="tooltip = null"
                            wire:click="selectMonth({{ $month }})"
                            class="relative w-full cursor-pointer rounded-md transition-all duration-150 hover:ring-2 hover:ring-emerald-400 hover:ring-offset-1 {{ $levelClasses[$level] }} {{ $isSelected ? 'ring-2 ring-emerald-500 ring-offset-1' : '' }}"
                            style="aspect-ratio: 1"
                        >
                            <template x-if="tooltip && tooltip.month === '{{ $monthName }}'">
                                <div class="absolute -top-10 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-xs text-white shadow-lg dark:bg-zinc-100 dark:text-zinc-900">
                                    <span x-text="tooltip.month + ' · ' + tooltip.amount"></span>
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-zinc-900 dark:border-t-zinc-100"></div>
                                </div>
                            </template>
                        </div>
                        <span class="text-[10px] {{ $isSelected ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500' }}">{{ $monthName }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="mt-4 flex items-center justify-end gap-1.5">
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500">{{ __('None') }}</span>
                @foreach([0, 1, 2, 3, 4] as $l)
                    <div class="size-3 rounded-sm {{ $levelClasses[$l] }}"></div>
                @endforeach
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500">{{ __('High') }}</span>
            </div>
        </div>

        {{-- Daily Breakdown (expands on month click) --}}
        @if($selectedMonth && count($this->dailyTotals) > 0)
            @php
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $year);
                $dailyLabels = [];
                $dailyValues = [];
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dailyLabels[] = $d;
                    $dailyValues[] = (float) ($this->dailyTotals[$d] ?? 0);
                }
                $selectedMonthName = $monthNames[$selectedMonth - 1];
            @endphp
            <div
                wire:key="daily-chart-{{ $selectedMonth }}-{{ $year }}"
                x-data="givingDailyChart(@js($dailyLabels), @js($dailyValues), '{{ $this->currency->symbol() }}')"
                x-init="init()"
                x-destroy="destroy()"
                class="border-t border-zinc-200 px-6 pb-5 pt-4 dark:border-zinc-700"
            >
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __(':month daily giving', ['month' => $selectedMonthName.' '.$year]) }}
                    </p>
                    <button
                        wire:click="selectMonth({{ $selectedMonth }})"
                        class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    >
                        {{ __('Close') }} ×
                    </button>
                </div>
                <div class="h-36">
                    <canvas x-ref="chart"></canvas>
                </div>
            </div>
        @elseif($selectedMonth && count($this->dailyTotals) === 0)
            <div class="border-t border-zinc-200 px-6 py-4 text-center text-sm text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">
                {{ __('No giving recorded in :month.', ['month' => $monthNames[$selectedMonth - 1].' '.$year]) }}
            </div>
        @endif
    </flux:card>

    <script>
    function givingDailyChart(labels, values, symbol) {
        return {
            chart: null,
            destroy() {
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }
            },
            init() {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? '#a1a1aa' : '#71717a';

                this.chart = new Chart(this.$refs.chart, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: values.map(v => v > 0 ? '#009866' : 'transparent'),
                            borderRadius: 3,
                            borderSkipped: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 300 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: ctx => 'Day ' + ctx[0].label,
                                    label: ctx => ctx.parsed.y > 0 ? symbol + ctx.parsed.y.toFixed(2) : 'No giving',
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { size: 10 } },
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    font: { size: 10 },
                                    callback: v => v > 0 ? symbol + v.toFixed(0) : '',
                                },
                                beginAtZero: true,
                            },
                        },
                    },
                });
            },
        };
    }
    </script>

    {{-- Donations Table --}}
    <flux:card>
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Giving History') }}</flux:heading>
        </div>

        @if($this->donations->isEmpty())
            <div class="p-8 text-center text-zinc-500">
                {{ __('No giving records for :year.', ['year' => $year]) }}
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Amount') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Payment Method') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->donations as $donation)
                        <flux:table.row>
                            <flux:table.cell>{{ $donation->donation_date->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $this->currency->symbol() }}{{ number_format($donation->amount, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                @if($donation->donation_type)
                                    <flux:badge size="sm">{{ __(str()->headline($donation->donation_type->value)) }}</flux:badge>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $donation->payment_method ?? '-' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="p-4">
                {{ $this->donations->links() }}
            </div>
        @endif
    </flux:card>
</div>
