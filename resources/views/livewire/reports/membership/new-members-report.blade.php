<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('New Members Report') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __(':count new members in this period', ['count' => number_format($this->totalNewMembers)]) }}
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
                <flux:button
                    :variant="$period === 365 ? 'primary' : 'ghost'"
                    wire:click="setPeriod(365)"
                    size="sm"
                >
                    {{ __('1 Year') }}
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
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('New Members') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->comparisonStats['current']) }}</div>
            <div class="text-xs {{ $this->comparisonStats['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                @if($this->comparisonStats['trend'] === 'up')
                    <span>↑ {{ $this->comparisonStats['change'] }}%</span>
                @else
                    <span>↓ {{ abs($this->comparisonStats['change']) }}%</span>
                @endif
                {{ __('vs previous period') }}
            </div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Previous Period') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->comparisonStats['previous']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Daily Average') }}</div>
            @php
                $days = max(1, $this->startDate->diffInDays($this->endDate) + 1);
                $average = round($this->totalNewMembers / $days, 1);
            @endphp
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $average }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members/day') }}</div>
        </div>
    </div>

    <!-- Chart -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('New Member Trend') }}</flux:heading>
        <div class="h-64">
            <canvas
                id="newMembersChart"
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
                        const ctx = document.getElementById('newMembersChart');
                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: @js($this->chartData['labels']),
                                datasets: [{
                                    label: '{{ __('New Members') }}',
                                    data: @js($this->chartData['data']),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
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

    <!-- Results Table -->
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Gender') }}
                        </th>
                        <th wire:click="sortBy('joined_at')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Joined') }}
                                @if($sortBy === 'joined_at')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('City') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->members as $member)
                        <tr wire:key="member-{{ $member->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($member->photo_url)
                                        <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-8 rounded-full object-cover" />
                                    @else
                                        <flux:avatar size="sm" name="{{ $member->fullName() }}" />
                                    @endif
                                    <a href="{{ route('members.show', [$branch, $member]) }}" wire:navigate class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400">
                                        {{ $member->fullName() }}
                                    </a>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div>{{ $member->email ?? '-' }}</div>
                                <div>{{ $member->phone ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->gender ? ucfirst($member->gender->value) : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->city ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No new members found in this period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->members->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->members->links() }}
            </div>
        @endif
    </div>
</section>
