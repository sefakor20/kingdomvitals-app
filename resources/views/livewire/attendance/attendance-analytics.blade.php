<section class="w-full">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Attendance Analytics') }}</flux:heading>
            <flux:subheading>{{ __('Member engagement and attendance insights for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            {{-- Period Selector --}}
            <flux:dropdown>
                <flux:button variant="ghost" icon-trailing="chevron-down">
                    {{ __('Last :days days', ['days' => $period]) }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="setPeriod(30)">{{ __('Last 30 days') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(60)">{{ __('Last 60 days') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(90)">{{ __('Last 90 days') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(180)">{{ __('Last 6 months') }}</flux:menu.item>
                    <flux:menu.item wire:click="setPeriod(365)">{{ __('Last year') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            {{-- Service Filter --}}
            @if($this->services->count() > 1)
                <flux:dropdown>
                    <flux:button variant="ghost" icon-trailing="chevron-down">
                        {{ $serviceFilter ? $this->services->firstWhere('id', $serviceFilter)?->name : __('All Services') }}
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="$set('serviceFilter', null)">{{ __('All Services') }}</flux:menu.item>
                        <flux:separator />
                        @foreach($this->services as $service)
                            <flux:menu.item wire:click="$set('serviceFilter', '{{ $service->id }}')">
                                {{ $service->name }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif

            <flux:button variant="ghost" :href="route('attendance.index', $branch)" wire:navigate icon="list-bullet">
                {{ __('View All') }}
            </flux:button>
        </div>
    </div>

    {{-- Executive Summary Cards --}}
    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Summary') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Total Attendance --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Attendance') }}</flux:text>
                    <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                        <flux:icon icon="users" class="size-4 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">
                    {{ number_format($this->summaryStats['total_attendance']) }}
                </flux:heading>
                <flux:text class="text-xs">
                    @if($this->summaryStats['total_growth'] >= 0)
                        <span class="font-medium text-green-600 dark:text-green-400">+{{ $this->summaryStats['total_growth'] }}%</span>
                    @else
                        <span class="font-medium text-red-600 dark:text-red-400">{{ $this->summaryStats['total_growth'] }}%</span>
                    @endif
                    <span class="text-zinc-500">{{ __('vs previous period') }}</span>
                </flux:text>
            </div>

            {{-- Unique Members --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Unique Members') }}</flux:text>
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="user-group" class="size-4 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                    {{ number_format($this->summaryStats['unique_members']) }}
                </flux:heading>
                <flux:text class="text-xs">
                    @if($this->summaryStats['member_growth'] >= 0)
                        <span class="font-medium text-green-600 dark:text-green-400">+{{ $this->summaryStats['member_growth'] }}%</span>
                    @else
                        <span class="font-medium text-red-600 dark:text-red-400">{{ $this->summaryStats['member_growth'] }}%</span>
                    @endif
                    <span class="text-zinc-500">{{ __('vs previous period') }}</span>
                </flux:text>
            </div>

            {{-- Visitors --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</flux:text>
                    <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                        <flux:icon icon="user-plus" class="size-4 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-purple-600 dark:text-purple-400">
                    {{ number_format($this->summaryStats['total_visitors']) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    {{ $this->visitorConversionRate['conversion_rate'] }}% {{ __('conversion rate') }}
                </flux:text>
            </div>

            {{-- Average Per Service --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg. Per Service') }}</flux:text>
                    <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                        <flux:icon icon="chart-bar" class="size-4 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
                <flux:heading size="xl" class="mt-2 text-amber-600 dark:text-amber-400">
                    {{ number_format($this->summaryStats['avg_per_service'], 1) }}
                </flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    {{ $this->summaryStats['service_dates'] }} {{ __('service dates') }}
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Engagement Metrics --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Member Engagement') }}</flux:heading>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Regular Attenders --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">
                    {{ number_format($this->engagementMetrics['regular_count']) }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Regular Attenders') }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('75%+ attendance') }}</flux:text>
            </div>

            {{-- Casual Attenders --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-blue-600 dark:text-blue-400">
                    {{ number_format($this->engagementMetrics['casual_count']) }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Casual Attenders') }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('25-74% attendance') }}</flux:text>
            </div>

            {{-- At-Risk --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-orange-600 dark:text-orange-400">
                    {{ number_format($this->engagementMetrics['at_risk_count']) }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('At-Risk') }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('Declining attendance') }}</flux:text>
            </div>

            {{-- Lapsed --}}
            <div class="text-center">
                <flux:heading size="xl" class="text-red-600 dark:text-red-400">
                    {{ number_format($this->engagementMetrics['lapsed_count']) }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lapsed') }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('No attendance in :weeks+ weeks', ['weeks' => $lapsedWeeksThreshold]) }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        {{-- Attendance Trend Chart --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Weekly Attendance Trend') }}</flux:heading>
            <div class="h-64" x-data="{
                chart: null,
                init() {
                    this.renderChart();
                },
                renderChart() {
                    const ctx = this.$refs.trendChart.getContext('2d');
                    if (this.chart) this.chart.destroy();
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @js($this->attendanceTrendData['labels']),
                            datasets: [
                                {
                                    label: '{{ __('This Year') }}',
                                    data: @js($this->attendanceTrendData['current_year']),
                                    borderColor: '#3b82f6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.3,
                                },
                                {
                                    label: '{{ __('Last Year') }}',
                                    data: @js($this->attendanceTrendData['previous_year']),
                                    borderColor: '#9ca3af',
                                    backgroundColor: 'transparent',
                                    borderDash: [5, 5],
                                    tension: 0.3,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }
            }" x-init="init()" wire:ignore>
                <canvas x-ref="trendChart"></canvas>
            </div>
        </div>

        {{-- Service Utilization --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Service Utilization') }}</flux:heading>
            @if(count($this->serviceUtilization) > 0)
                <div class="space-y-4">
                    @foreach($this->serviceUtilization as $service)
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <flux:text class="text-sm font-medium">{{ $service['name'] }}</flux:text>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $service['avg_attendance'] }} / {{ $service['capacity'] ?: '?' }}
                                    @if($service['capacity'])
                                        ({{ $service['capacity_percent'] }}%)
                                    @endif
                                </flux:text>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full transition-all duration-300
                                    {{ $service['capacity_percent'] >= 90 ? 'bg-red-500' : ($service['capacity_percent'] >= 70 ? 'bg-amber-500' : 'bg-green-500') }}"
                                    style="width: {{ min($service['capacity_percent'], 100) }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-center text-zinc-500">{{ __('No service data available') }}</flux:text>
            @endif
        </div>
    </div>

    {{-- Engagement Alerts --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Engagement Alerts') }}</flux:heading>

        <div x-data="{ activeTab: 'lapsed' }">
            {{-- Tabs --}}
            <div class="mb-4 flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
                <button @click="activeTab = 'lapsed'"
                    :class="activeTab === 'lapsed' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-zinc-500'"
                    class="px-4 py-2 text-sm font-medium transition-colors">
                    {{ __('Lapsed') }}
                    <flux:badge size="sm" color="red">{{ $this->engagementAlerts['lapsed_count'] }}</flux:badge>
                </button>
                <button @click="activeTab = 'at_risk'"
                    :class="activeTab === 'at_risk' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-zinc-500'"
                    class="px-4 py-2 text-sm font-medium transition-colors">
                    {{ __('At-Risk') }}
                    <flux:badge size="sm" color="amber">{{ $this->engagementAlerts['at_risk_count'] }}</flux:badge>
                </button>
                <button @click="activeTab = 'visitors'"
                    :class="activeTab === 'visitors' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-zinc-500'"
                    class="px-4 py-2 text-sm font-medium transition-colors">
                    {{ __('Visitors Not Returning') }}
                    <flux:badge size="sm" color="zinc">{{ $this->engagementAlerts['not_returning_count'] }}</flux:badge>
                </button>
            </div>

            {{-- Lapsed Members Tab --}}
            <div x-show="activeTab === 'lapsed'" x-cloak>
                @if($this->engagementAlerts['lapsed']->count() > 0)
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->engagementAlerts['lapsed'] as $member)
                            <div class="flex items-center gap-4 py-3">
                                <flux:avatar src="{{ $member['photo_url'] }}" name="{{ $member['name'] }}" size="sm" />
                                <div class="flex-1">
                                    <flux:text class="font-medium">{{ $member['name'] }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('Last seen: :date', ['date' => $member['last_attendance'] ?? 'Never']) }}
                                        @if($member['days_since'])
                                            ({{ $member['days_since'] }} {{ __('days ago') }})
                                        @endif
                                    </flux:text>
                                </div>
                                <flux:button variant="ghost" size="sm" :href="route('members.show', [$branch, $member['id']])" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="py-8 text-center text-zinc-500">{{ __('No lapsed members in this period') }}</flux:text>
                @endif
            </div>

            {{-- At-Risk Members Tab --}}
            <div x-show="activeTab === 'at_risk'" x-cloak>
                @if($this->engagementAlerts['at_risk']->count() > 0)
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->engagementAlerts['at_risk'] as $member)
                            <div class="flex items-center gap-4 py-3">
                                <flux:avatar src="{{ $member['photo_url'] }}" name="{{ $member['name'] }}" size="sm" />
                                <div class="flex-1">
                                    <flux:text class="font-medium">{{ $member['name'] }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('Attendance dropped from :prev% to :curr%', ['prev' => $member['previous_score'], 'curr' => $member['current_score']]) }}
                                    </flux:text>
                                </div>
                                <flux:badge color="red" size="sm">{{ $member['change'] }}%</flux:badge>
                                <flux:button variant="ghost" size="sm" :href="route('members.show', [$branch, $member['id']])" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="py-8 text-center text-zinc-500">{{ __('No at-risk members detected') }}</flux:text>
                @endif
            </div>

            {{-- Visitors Not Returning Tab --}}
            <div x-show="activeTab === 'visitors'" x-cloak>
                @if($this->engagementAlerts['not_returning_visitors']->count() > 0)
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->engagementAlerts['not_returning_visitors'] as $visitor)
                            <div class="flex items-center gap-4 py-3">
                                <flux:avatar name="{{ $visitor['name'] }}" size="sm" />
                                <div class="flex-1">
                                    <flux:text class="font-medium">{{ $visitor['name'] }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('Visited: :date', ['date' => $visitor['visit_date'] ?? 'Unknown']) }}
                                        @if($visitor['phone'])
                                            &bull; {{ $visitor['phone'] }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:text class="py-8 text-center text-zinc-500">{{ __('All recent visitors have returned or been converted') }}</flux:text>
                @endif
            </div>
        </div>
    </div>

    {{-- Member Engagement Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">{{ __('Member Engagement Scores') }}</flux:heading>
            <flux:input
                wire:model.live.debounce.300ms="memberSearch"
                placeholder="{{ __('Search members...') }}"
                icon="magnifying-glass"
                class="w-full sm:w-64"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</th>
                        <th class="cursor-pointer pb-3 font-medium text-zinc-500 dark:text-zinc-400" wire:click="sortBy('engagement_score')">
                            <span class="flex items-center gap-1">
                                {{ __('Score') }}
                                @if($memberSortBy === 'engagement_score')
                                    <flux:icon icon="{{ $memberSortDirection === 'desc' ? 'chevron-down' : 'chevron-up' }}" class="size-4" />
                                @endif
                            </span>
                        </th>
                        <th class="cursor-pointer pb-3 font-medium text-zinc-500 dark:text-zinc-400" wire:click="sortBy('attendance_count')">
                            <span class="flex items-center gap-1">
                                {{ __('Attended') }}
                                @if($memberSortBy === 'attendance_count')
                                    <flux:icon icon="{{ $memberSortDirection === 'desc' ? 'chevron-down' : 'chevron-up' }}" class="size-4" />
                                @endif
                            </span>
                        </th>
                        <th class="cursor-pointer pb-3 font-medium text-zinc-500 dark:text-zinc-400" wire:click="sortBy('last_attendance')">
                            <span class="flex items-center gap-1">
                                {{ __('Last Attendance') }}
                                @if($memberSortBy === 'last_attendance')
                                    <flux:icon icon="{{ $memberSortDirection === 'desc' ? 'chevron-down' : 'chevron-up' }}" class="size-4" />
                                @endif
                            </span>
                        </th>
                        <th class="pb-3 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->memberEngagementList as $member)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <flux:avatar src="{{ $member->photo_url }}" name="{{ $member->first_name }} {{ $member->last_name }}" size="sm" />
                                    <a href="{{ route('members.show', [$branch, $member->id]) }}" wire:navigate class="font-medium hover:text-blue-600">
                                        {{ $member->first_name }} {{ $member->last_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div class="h-full rounded-full transition-all duration-300
                                            {{ $member->engagement_score >= 75 ? 'bg-green-500' : ($member->engagement_score >= 25 ? 'bg-blue-500' : 'bg-red-500') }}"
                                            style="width: {{ min($member->engagement_score, 100) }}%">
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium">{{ $member->engagement_score }}%</span>
                                </div>
                            </td>
                            <td class="py-3">{{ $member->attendance_count }}</td>
                            <td class="py-3">
                                @if($member->last_attendance)
                                    {{ $member->last_attendance->format('M d, Y') }}
                                    <span class="text-xs text-zinc-500">({{ $member->days_since }}d ago)</span>
                                @else
                                    <span class="text-zinc-400">{{ __('Never') }}</span>
                                @endif
                            </td>
                            <td class="py-3">
                                @if($member->engagement_score >= 75)
                                    <flux:badge color="green" size="sm">{{ __('Regular') }}</flux:badge>
                                @elseif($member->engagement_score >= 25)
                                    <flux:badge color="blue" size="sm">{{ __('Casual') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-500">
                                {{ __('No members found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->memberEngagementList->hasPages())
            <div class="mt-4">
                {{ $this->memberEngagementList->links() }}
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
