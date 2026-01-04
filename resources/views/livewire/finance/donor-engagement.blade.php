<section class="w-full">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Donor Engagement') }}</flux:heading>
            <flux:subheading>{{ __('Donor retention, trends, and engagement insights for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            {{-- Period Selector --}}
            <flux:select wire:model.live="period" class="w-40">
                <flux:select.option value="30">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="60">{{ __('Last 60 days') }}</flux:select.option>
                <flux:select.option value="90">{{ __('Last 90 days') }}</flux:select.option>
                <flux:select.option value="180">{{ __('Last 6 months') }}</flux:select.option>
                <flux:select.option value="365">{{ __('Last year') }}</flux:select.option>
            </flux:select>

            <flux:button variant="ghost" :href="route('finance.dashboard', $branch)" wire:navigate icon="presentation-chart-line">
                {{ __('Dashboard') }}
            </flux:button>
        </div>
    </div>

    {{-- Retention KPI Cards --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Donor Retention') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Returning Donors --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Returning Donors') }}</flux:text>
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="arrow-path" class="size-4 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                    {{ $this->retentionMetrics['returning_donors'] }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    {{ $this->retentionMetrics['retention_rate'] }}% {{ __('retention rate') }}
                </flux:text>
            </div>

            {{-- New Donors --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('New Donors') }}</flux:text>
                    <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                        <flux:icon icon="user-plus" class="size-4 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">
                    {{ $this->retentionMetrics['new_donors'] }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('First-time donors this period') }}</flux:text>
            </div>

            {{-- Lapsed Donors --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lapsed Donors') }}</flux:text>
                    <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                        <flux:icon icon="arrow-right-end-on-rectangle" class="size-4 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-red-600 dark:text-red-400">
                    {{ $this->retentionMetrics['lapsed_donors'] }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    {{ $this->retentionMetrics['churn_rate'] }}% {{ __('churn rate') }}
                </flux:text>
            </div>

            {{-- Reactivated Donors --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Reactivated Donors') }}</flux:text>
                    <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                        <flux:icon icon="arrow-uturn-left" class="size-4 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-purple-600 dark:text-purple-400">
                    {{ $this->retentionMetrics['reactivated_donors'] }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">{{ __('Returned after lapsing') }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Giving Trends Cards --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Giving Trends') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-3">
            {{-- Increasing Giving --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Increasing Giving') }}</flux:text>
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="arrow-trending-up" class="size-4 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                    {{ $this->givingTrends['increasing_count'] }}
                </flux:heading>
                <div class="mt-1 flex items-center justify-between">
                    <flux:text class="text-xs text-zinc-500">GHS {{ number_format($this->givingTrends['increasing_total'], 2) }}</flux:text>
                    @if($this->givingTrends['increasing_change'] > 0)
                        <flux:badge size="sm" color="green">+{{ $this->givingTrends['increasing_change'] }}%</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Declining Giving --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Declining Giving') }}</flux:text>
                    <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                        <flux:icon icon="arrow-trending-down" class="size-4 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-red-600 dark:text-red-400">
                    {{ $this->givingTrends['declining_count'] }}
                </flux:heading>
                <div class="mt-1 flex items-center justify-between">
                    <flux:text class="text-xs text-zinc-500">GHS {{ number_format($this->givingTrends['declining_total'], 2) }}</flux:text>
                    @if($this->givingTrends['declining_change'] < 0)
                        <flux:badge size="sm" color="red">{{ $this->givingTrends['declining_change'] }}%</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Consistent Giving --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Consistent Giving') }}</flux:text>
                    <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                        <flux:icon icon="minus" class="size-4 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">
                    {{ $this->givingTrends['consistent_count'] }}
                </flux:heading>
                <div class="mt-1">
                    <flux:text class="text-xs text-zinc-500">GHS {{ number_format($this->givingTrends['consistent_total'], 2) }}</flux:text>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        {{-- Retention Trend Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Retention Rate Trend') }}</flux:heading>
            <div
                x-data="retentionTrendChart(@js($this->retentionTrendData))"
                x-init="initChart()"
                class="h-64"
                wire:ignore
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Donor Segments Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Donor Segments') }} ({{ now()->year }})</flux:heading>
            @if(array_sum($this->donorSegments['chart_data']['data']) > 0)
                <div
                    x-data="donorSegmentsChart(@js($this->donorSegments['chart_data']))"
                    x-init="initChart()"
                    class="h-64"
                    wire:ignore
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="flex h-64 items-center justify-center text-zinc-500">
                    {{ __('No donor data this year') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Donor Segments Summary --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Segment Breakdown') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-3">
            {{-- Major Donors --}}
            <div class="rounded-lg border border-purple-100 bg-purple-50 p-4 dark:border-purple-900 dark:bg-purple-900/20">
                <div class="flex items-center gap-2">
                    <div class="size-3 rounded-full bg-purple-500"></div>
                    <flux:text class="font-medium text-purple-700 dark:text-purple-300">{{ __('Major Donors') }}</flux:text>
                </div>
                <flux:heading size="lg" class="mt-2 text-purple-600 dark:text-purple-400">
                    {{ $this->donorSegments['major']['count'] }}
                </flux:heading>
                <div class="mt-1 space-y-1">
                    <flux:text class="text-xs text-purple-600 dark:text-purple-400">
                        {{ __('Total') }}: GHS {{ number_format($this->donorSegments['major']['total'], 2) }}
                    </flux:text>
                    <flux:text class="text-xs text-purple-600 dark:text-purple-400">
                        {{ __('Avg') }}: GHS {{ number_format($this->donorSegments['major']['avg'], 2) }}
                    </flux:text>
                </div>
            </div>

            {{-- Regular Donors --}}
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <div class="size-3 rounded-full bg-blue-500"></div>
                    <flux:text class="font-medium text-blue-700 dark:text-blue-300">{{ __('Regular Donors') }}</flux:text>
                </div>
                <flux:heading size="lg" class="mt-2 text-blue-600 dark:text-blue-400">
                    {{ $this->donorSegments['regular']['count'] }}
                </flux:heading>
                <div class="mt-1 space-y-1">
                    <flux:text class="text-xs text-blue-600 dark:text-blue-400">
                        {{ __('Total') }}: GHS {{ number_format($this->donorSegments['regular']['total'], 2) }}
                    </flux:text>
                    <flux:text class="text-xs text-blue-600 dark:text-blue-400">
                        {{ __('Avg') }}: GHS {{ number_format($this->donorSegments['regular']['avg'], 2) }}
                    </flux:text>
                </div>
            </div>

            {{-- Occasional Donors --}}
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <div class="size-3 rounded-full bg-zinc-500"></div>
                    <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Occasional Donors') }}</flux:text>
                </div>
                <flux:heading size="lg" class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ $this->donorSegments['occasional']['count'] }}
                </flux:heading>
                <div class="mt-1 space-y-1">
                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">
                        {{ __('Total') }}: GHS {{ number_format($this->donorSegments['occasional']['total'], 2) }}
                    </flux:text>
                    <flux:text class="text-xs text-zinc-600 dark:text-zinc-400">
                        {{ __('Avg') }}: GHS {{ number_format($this->donorSegments['occasional']['avg'], 2) }}
                    </flux:text>
                </div>
            </div>
        </div>
    </div>

    {{-- Engagement Alerts --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Engagement Alerts') }}</flux:heading>

        <div x-data="{ activeTab: 'lapsing' }">
            {{-- Tab Navigation --}}
            <div class="mb-4 flex flex-wrap gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                <button
                    @click="activeTab = 'lapsing'"
                    :class="activeTab === 'lapsing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                >
                    <flux:icon icon="clock" class="size-4" />
                    {{ __('Lapsing') }}
                    @if($this->engagementAlerts['lapsing_count'] > 0)
                        <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs text-white">{{ $this->engagementAlerts['lapsing_count'] }}</span>
                    @endif
                </button>
                <button
                    @click="activeTab = 'declining'"
                    :class="activeTab === 'declining' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                >
                    <flux:icon icon="arrow-trending-down" class="size-4" />
                    {{ __('Declining') }}
                    @if($this->engagementAlerts['declining_count'] > 0)
                        <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs text-white">{{ $this->engagementAlerts['declining_count'] }}</span>
                    @endif
                </button>
                <button
                    @click="activeTab = 'at_risk'"
                    :class="activeTab === 'at_risk' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                >
                    <flux:icon icon="exclamation-triangle" class="size-4" />
                    {{ __('At-Risk Major') }}
                    @if($this->engagementAlerts['at_risk_count'] > 0)
                        <span class="rounded-full bg-orange-500 px-2 py-0.5 text-xs text-white">{{ $this->engagementAlerts['at_risk_count'] }}</span>
                    @endif
                </button>
                <button
                    @click="activeTab = 'potential'"
                    :class="activeTab === 'potential' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                >
                    <flux:icon icon="star" class="size-4" />
                    {{ __('Potential Major') }}
                    @if($this->engagementAlerts['potential_count'] > 0)
                        <span class="rounded-full bg-green-500 px-2 py-0.5 text-xs text-white">{{ $this->engagementAlerts['potential_count'] }}</span>
                    @endif
                </button>
            </div>

            {{-- Tab Content --}}
            <div class="min-h-[200px]">
                {{-- Lapsing Donors --}}
                <div x-show="activeTab === 'lapsing'" x-cloak>
                    @if($this->engagementAlerts['lapsing']->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->engagementAlerts['lapsing'] as $member)
                                <div class="flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-900/20">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" name="{{ $member->first_name }} {{ $member->last_name }}" />
                                        <div>
                                            <flux:text class="font-medium">{{ $member->first_name }} {{ $member->last_name }}</flux:text>
                                            <flux:text class="text-xs text-zinc-500">
                                                {{ __('Last donation') }}: {{ $member->donations->first()?->donation_date->diffForHumans() ?? 'N/A' }}
                                            </flux:text>
                                        </div>
                                    </div>
                                    <flux:button size="sm" variant="ghost" :href="route('members.show', [$branch, $member])" wire:navigate>
                                        {{ __('View') }}
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-32 items-center justify-center text-zinc-500">
                            {{ __('No lapsing donors detected') }}
                        </div>
                    @endif
                </div>

                {{-- Declining Donors --}}
                <div x-show="activeTab === 'declining'" x-cloak>
                    @if($this->engagementAlerts['declining']->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->engagementAlerts['declining'] as $donor)
                                <div class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50 p-3 dark:border-red-900 dark:bg-red-900/20">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" name="{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}" />
                                        <div>
                                            <flux:text class="font-medium">{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}</flux:text>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-zinc-500">GHS {{ number_format($donor->previous_total, 2) }}</span>
                                                <flux:icon icon="arrow-right" class="size-3 text-zinc-400" />
                                                <span class="text-red-600 dark:text-red-400">GHS {{ number_format($donor->current_total, 2) }}</span>
                                                <flux:badge size="sm" color="red">{{ $donor->change_percent }}%</flux:badge>
                                            </div>
                                        </div>
                                    </div>
                                    @if($donor->member)
                                        <flux:button size="sm" variant="ghost" :href="route('members.show', [$branch, $donor->member])" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-32 items-center justify-center text-zinc-500">
                            {{ __('No significantly declining donors') }}
                        </div>
                    @endif
                </div>

                {{-- At-Risk Major Donors --}}
                <div x-show="activeTab === 'at_risk'" x-cloak>
                    @if($this->engagementAlerts['at_risk_major']->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->engagementAlerts['at_risk_major'] as $donor)
                                <div class="flex items-center justify-between rounded-lg border border-orange-100 bg-orange-50 p-3 dark:border-orange-900 dark:bg-orange-900/20">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" name="{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <flux:text class="font-medium">{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}</flux:text>
                                                <flux:badge size="sm" color="purple">{{ __('Major') }}</flux:badge>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-zinc-500">GHS {{ number_format($donor->previous_total, 2) }}</span>
                                                <flux:icon icon="arrow-right" class="size-3 text-zinc-400" />
                                                <span class="text-orange-600 dark:text-orange-400">GHS {{ number_format($donor->current_total, 2) }}</span>
                                                <flux:badge size="sm" color="orange">{{ $donor->change_percent }}%</flux:badge>
                                            </div>
                                        </div>
                                    </div>
                                    @if($donor->member)
                                        <flux:button size="sm" variant="ghost" :href="route('members.show', [$branch, $donor->member])" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-32 items-center justify-center text-zinc-500">
                            {{ __('No at-risk major donors') }}
                        </div>
                    @endif
                </div>

                {{-- Potential Major Donors --}}
                <div x-show="activeTab === 'potential'" x-cloak>
                    @if($this->engagementAlerts['potential_major']->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->engagementAlerts['potential_major'] as $donor)
                                <div class="flex items-center justify-between rounded-lg border border-green-100 bg-green-50 p-3 dark:border-green-900 dark:bg-green-900/20">
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" name="{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}" />
                                        <div>
                                            <flux:text class="font-medium">{{ $donor->member?->first_name }} {{ $donor->member?->last_name }}</flux:text>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="text-green-600 dark:text-green-400">GHS {{ number_format($donor->period_total, 2) }}</span>
                                                <span class="text-zinc-500">({{ $donor->donation_count }} {{ __('donations') }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if($donor->member)
                                        <flux:button size="sm" variant="ghost" :href="route('members.show', [$branch, $donor->member])" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-32 items-center justify-center text-zinc-500">
                            {{ __('No new potential major donors') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Donor List Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">{{ __('Individual Donors') }}</flux:heading>
            <div class="flex items-center gap-2">
                {{-- Search --}}
                <flux:input
                    wire:model.live.debounce.300ms="donorSearch"
                    placeholder="{{ __('Search donors...') }}"
                    icon="magnifying-glass"
                    class="w-48"
                />

                {{-- Trend Filter --}}
                <flux:select wire:model.live="donorTrendFilter" class="w-36">
                    <flux:select.option value="all">{{ __('All trends') }}</flux:select.option>
                    <flux:select.option value="increasing">{{ __('Increasing') }}</flux:select.option>
                    <flux:select.option value="declining">{{ __('Declining') }}</flux:select.option>
                    <flux:select.option value="consistent">{{ __('Consistent') }}</flux:select.option>
                    <flux:select.option value="new">{{ __('New') }}</flux:select.option>
                    <flux:select.option value="lapsed">{{ __('Lapsed') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3">{{ __('Donor') }}</th>
                        <th class="px-4 py-3">{{ __('Trend') }}</th>
                        <th class="cursor-pointer px-4 py-3" wire:click="sortBy('lifetime_total')">
                            <div class="flex items-center gap-1">
                                {{ __('Lifetime Total') }}
                                @if($donorSortBy === 'lifetime_total')
                                    <flux:icon icon="{{ $donorSortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer px-4 py-3" wire:click="sortBy('period_total')">
                            <div class="flex items-center gap-1">
                                {{ __('Period Total') }}
                                @if($donorSortBy === 'period_total')
                                    <flux:icon icon="{{ $donorSortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer px-4 py-3" wire:click="sortBy('average_donation')">
                            <div class="flex items-center gap-1">
                                {{ __('Average') }}
                                @if($donorSortBy === 'average_donation')
                                    <flux:icon icon="{{ $donorSortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="cursor-pointer px-4 py-3" wire:click="sortBy('last_donation_date')">
                            <div class="flex items-center gap-1">
                                {{ __('Last Donation') }}
                                @if($donorSortBy === 'last_donation_date')
                                    <flux:icon icon="{{ $donorSortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($this->donorsList as $donor)
                        <tr wire:key="donor-{{ $donor->member_id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $donor->first_name }} {{ $donor->last_name }}" />
                                    <div>
                                        <flux:text class="font-medium">{{ $donor->first_name }} {{ $donor->last_name }}</flux:text>
                                        <flux:text class="text-xs text-zinc-500">{{ $donor->total_donations }} {{ __('donations') }}</flux:text>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @switch($donor->trend)
                                    @case('increasing')
                                        <flux:badge color="green" size="sm">
                                            <flux:icon icon="arrow-trending-up" class="mr-1 size-3" />
                                            {{ __('Increasing') }}
                                        </flux:badge>
                                        @break
                                    @case('declining')
                                        <flux:badge color="red" size="sm">
                                            <flux:icon icon="arrow-trending-down" class="mr-1 size-3" />
                                            {{ __('Declining') }}
                                        </flux:badge>
                                        @break
                                    @case('consistent')
                                        <flux:badge color="blue" size="sm">
                                            <flux:icon icon="minus" class="mr-1 size-3" />
                                            {{ __('Consistent') }}
                                        </flux:badge>
                                        @break
                                    @case('new')
                                        <flux:badge color="purple" size="sm">
                                            <flux:icon icon="sparkles" class="mr-1 size-3" />
                                            {{ __('New') }}
                                        </flux:badge>
                                        @break
                                    @case('lapsed')
                                        <flux:badge color="zinc" size="sm">
                                            <flux:icon icon="clock" class="mr-1 size-3" />
                                            {{ __('Lapsed') }}
                                        </flux:badge>
                                        @break
                                    @default
                                        <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endswitch
                            </td>
                            <td class="px-4 py-3 font-medium">GHS {{ number_format($donor->lifetime_total, 2) }}</td>
                            <td class="px-4 py-3">GHS {{ number_format($donor->period_total, 2) }}</td>
                            <td class="px-4 py-3">GHS {{ number_format($donor->average_donation, 2) }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <flux:text class="text-sm">{{ \Carbon\Carbon::parse($donor->last_donation_date)->format('M d, Y') }}</flux:text>
                                    <flux:text class="text-xs text-zinc-500">{{ $donor->days_since_last }} {{ __('days ago') }}</flux:text>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                {{ __('No donors found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->donorsList->hasPages())
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                {{ $this->donorsList->links() }}
            </div>
        @endif
    </div>
</section>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endassets

@script
<script>
    // Retention Trend Line Chart
    Alpine.data('retentionTrendChart', (initialData) => ({
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
                        label: 'Retention Rate %',
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
                            grid: { color: isDark ? '#27272a' : '#e4e4e7' }
                        },
                        x: {
                            ticks: { color: isDark ? '#a1a1aa' : '#71717a' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }));

    // Donor Segments Doughnut Chart
    Alpine.data('donorSegmentsChart', (initialData) => ({
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
                            labels: { color: isDark ? '#a1a1aa' : '#71717a' }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': GHS ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }));
</script>
@endscript
