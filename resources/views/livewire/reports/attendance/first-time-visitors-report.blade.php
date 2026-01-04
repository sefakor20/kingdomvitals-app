<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('First-time Visitors Report') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __(':count visitors in this period', ['count' => number_format($this->summaryStats['total'])]) }}
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

    <!-- Additional Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-4">
            <div class="w-40">
                <flux:select wire:model.live="status" label="{{ __('Status') }}">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    @foreach($this->statuses as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-48">
                <flux:select wire:model.live="source" label="{{ __('Source') }}">
                    <flux:select.option value="">{{ __('All Sources') }}</flux:select.option>
                    @foreach($this->sources as $sourceOption)
                        <flux:select.option value="{{ $sourceOption }}">{{ $sourceOption }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if($this->hasActiveFilters)
                <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Visitors') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['total']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->periodLabel }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Converted') }}</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->summaryStats['converted']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->summaryStats['conversion_rate'] }}% {{ __('rate') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Followed Up') }}</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->summaryStats['followed_up']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->summaryStats['follow_up_rate'] }}% {{ __('rate') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Conversion Rate') }}</div>
            <div class="text-2xl font-bold {{ $this->summaryStats['conversion_rate'] >= 20 ? 'text-green-600 dark:text-green-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $this->summaryStats['conversion_rate'] }}%
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('visitors to members') }}</div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Visitors Trend Chart -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Visitor Trend') }}</flux:heading>
            <div class="h-48">
                <canvas
                    id="visitorsChart"
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
                            const ctx = document.getElementById('visitorsChart');
                            this.chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: @js($this->chartData['labels']),
                                    datasets: [{
                                        label: '{{ __('Visitors') }}',
                                        data: @js($this->chartData['data']),
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

        <!-- Source Breakdown -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('How Did They Hear?') }}</flux:heading>
            @if($this->sourceBreakdown->isNotEmpty())
                <div class="space-y-3">
                    @foreach($this->sourceBreakdown as $source)
                        @php
                            $percentage = $this->summaryStats['total'] > 0
                                ? round(($source->count / $this->summaryStats['total']) * 100, 1)
                                : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $source->how_did_you_hear }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $source->count }} ({{ $percentage }}%)</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('No source data available.') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th wire:click="sortBy('first_name')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Name') }}
                                @if($sortBy === 'first_name')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Contact') }}
                        </th>
                        <th wire:click="sortBy('visit_date')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Visit Date') }}
                                @if($sortBy === 'visit_date')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Source') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Converted') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->visitors as $visitor)
                        <tr wire:key="visitor-{{ $visitor->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('visitors.show', [$branch, $visitor]) }}" wire:navigate class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400">
                                    {{ $visitor->fullName() }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div>{{ $visitor->email ?? '-' }}</div>
                                <div>{{ $visitor->phone ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $visitor->visit_date?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $visitor->how_did_you_hear ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge
                                    :color="match($visitor->status?->value) {
                                        'new' => 'blue',
                                        'contacted' => 'yellow',
                                        'follow_up' => 'purple',
                                        'converted' => 'green',
                                        'not_interested' => 'zinc',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ $visitor->status ? ucfirst(str_replace('_', ' ', $visitor->status->value)) : '-' }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @if($visitor->is_converted)
                                    <flux:icon.check-circle class="mx-auto size-5 text-green-500" />
                                @else
                                    <flux:icon.x-circle class="mx-auto size-5 text-zinc-300 dark:text-zinc-600" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No visitors found for this period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->visitors->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->visitors->links() }}
            </div>
        @endif
    </div>
</section>
