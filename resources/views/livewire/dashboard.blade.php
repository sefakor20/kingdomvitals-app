<section class="w-full">
    {{-- Header with Branch Context --}}
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
        @if($this->currentBranch)
            <flux:subheading>{{ __('Overview for :branch', ['branch' => $this->currentBranch->name]) }}</flux:subheading>
        @else
            <flux:subheading>{{ __('Select a branch to view metrics') }}</flux:subheading>
        @endif
    </div>

    @if($this->currentBranch)
        {{-- Primary KPI Cards Grid --}}
        <div class="mb-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {{-- Total Active Members --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Members') }}</flux:text>
                    <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                        <flux:icon icon="users" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <flux:heading size="2xl" class="mt-2">{{ number_format($this->totalActiveMembers) }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($this->newMembersThisMonth > 0)
                        <span class="font-medium text-green-600 dark:text-green-400">+{{ $this->newMembersThisMonth }}</span>
                        {{ __('this month') }}
                    @else
                        {{ __('Total active') }}
                    @endif
                </flux:text>
            </div>

            {{-- Visitors This Month --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('New Visitors') }}</flux:text>
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                        <flux:icon icon="user-plus" class="size-5 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <flux:heading size="2xl" class="mt-2">{{ number_format($this->newVisitorsThisMonth) }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Conversion rate:') }}
                    <span class="font-medium text-blue-600 dark:text-blue-400">{{ $this->conversionRate }}%</span>
                </flux:text>
            </div>

            {{-- Overdue Follow-ups --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Overdue Follow-ups') }}</flux:text>
                    <div class="rounded-full {{ $this->overdueFollowUps > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                        <flux:icon icon="clock" class="size-5 {{ $this->overdueFollowUps > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                    </div>
                </div>
                <flux:heading size="2xl" class="mt-2 {{ $this->overdueFollowUps > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                    {{ $this->overdueFollowUps }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($this->overdueFollowUps > 0)
                        {{ __('Needs attention') }}
                    @else
                        {{ __('All caught up') }}
                    @endif
                </flux:text>
            </div>

            {{-- Donations This Month --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Donations') }}</flux:text>
                    <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                        <flux:icon icon="banknotes" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
                <flux:heading size="2xl" class="mt-2">GHS {{ number_format($this->donationsThisMonth, 2) }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ now()->format('F Y') }}
                </flux:text>
            </div>
        </div>

        {{-- Secondary Content: Quick Actions + Pending Follow-ups --}}
        <div class="mb-6 grid gap-6 lg:grid-cols-3">
            {{-- Quick Actions --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Quick Actions') }}</flux:heading>
                <div class="space-y-3">
                    @can('create', [App\Models\Tenant\Member::class, $this->currentBranch])
                        <flux:button variant="ghost" class="w-full justify-start" icon="user-plus"
                            :href="route('members.index', $this->currentBranch)" wire:navigate>
                            {{ __('Add New Member') }}
                        </flux:button>
                    @endcan

                    @can('create', [App\Models\Tenant\Visitor::class, $this->currentBranch])
                        <flux:button variant="ghost" class="w-full justify-start" icon="clipboard-document-list"
                            :href="route('visitors.index', $this->currentBranch)" wire:navigate>
                            {{ __('Record Visitor') }}
                        </flux:button>
                    @endcan

                    @can('viewAny', [App\Models\Tenant\Service::class, $this->currentBranch])
                        <flux:button variant="ghost" class="w-full justify-start" icon="calendar-days"
                            :href="route('services.index', $this->currentBranch)" wire:navigate>
                            {{ __('View Services') }}
                        </flux:button>
                    @endcan

                    @can('viewAny', [App\Models\Tenant\Cluster::class, $this->currentBranch])
                        <flux:button variant="ghost" class="w-full justify-start" icon="user-group"
                            :href="route('clusters.index', $this->currentBranch)" wire:navigate>
                            {{ __('Manage Clusters') }}
                        </flux:button>
                    @endcan
                </div>
            </div>

            {{-- Pending Follow-ups --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Upcoming Follow-ups') }}</flux:heading>
                    @if($this->pendingFollowUps->isNotEmpty())
                        <flux:button variant="ghost" size="sm" icon="arrow-right"
                            :href="route('visitors.index', $this->currentBranch)" wire:navigate>
                            {{ __('View All') }}
                        </flux:button>
                    @endif
                </div>

                @if($this->pendingFollowUps->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon icon="check-circle" class="size-12 text-green-500" />
                        <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No pending follow-ups') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($this->pendingFollowUps as $followUp)
                            <div wire:key="followup-{{ $followUp->id }}"
                                 class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$followUp->visitor->fullName()" />
                                    <div>
                                        <flux:text class="font-medium">{{ $followUp->visitor->fullName() }}</flux:text>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ ucfirst($followUp->type->value) }} &bull; {{ $followUp->scheduled_at->format('M d, g:i A') }}
                                        </flux:text>
                                    </div>
                                </div>
                                @if($followUp->isOverdue())
                                    <flux:badge color="red" size="sm">{{ __('Overdue') }}</flux:badge>
                                @else
                                    <flux:badge color="yellow" size="sm">{{ __('Pending') }}</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Last Service Attendance + Recent Activity --}}
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Last Service Attendance --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Last Service Attendance') }}</flux:heading>

                @if($this->lastServiceAttendance)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</flux:text>
                            <flux:text class="font-medium">{{ \Carbon\Carbon::parse($this->lastServiceAttendance['date'])->format('M d, Y') }}</flux:text>
                        </div>
                        <div class="flex items-center justify-between">
                            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Total Attendance') }}</flux:text>
                            <flux:heading size="xl">{{ $this->lastServiceAttendance['total'] }}</flux:heading>
                        </div>

                        {{-- Simple bar visualization --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</span>
                                <span class="font-medium">{{ $this->lastServiceAttendance['members'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                @php
                                    $memberPercent = $this->lastServiceAttendance['total'] > 0
                                        ? ($this->lastServiceAttendance['members'] / $this->lastServiceAttendance['total']) * 100
                                        : 0;
                                @endphp
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $memberPercent }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</span>
                                <span class="font-medium">{{ $this->lastServiceAttendance['visitors'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                @php
                                    $visitorPercent = $this->lastServiceAttendance['total'] > 0
                                        ? ($this->lastServiceAttendance['visitors'] / $this->lastServiceAttendance['total']) * 100
                                        : 0;
                                @endphp
                                <div class="h-full rounded-full bg-green-500" style="width: {{ $visitorPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon icon="calendar" class="size-12 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No attendance records yet') }}</flux:text>
                    </div>
                @endif
            </div>

            {{-- Recent Activity Feed --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Recent Activity') }}</flux:heading>

                @if($this->recentActivity->isEmpty())
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon icon="clock" class="size-12 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No recent activity') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($this->recentActivity as $activity)
                            <div wire:key="activity-{{ $activity->id }}" class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon :icon="match($activity->event->value) {
                                            'created' => 'plus',
                                            'updated' => 'pencil',
                                            'deleted' => 'trash',
                                            'restored' => 'arrow-uturn-left',
                                            default => 'document',
                                        }" class="size-4 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <flux:text class="text-sm">{{ $activity->formatted_description }}</flux:text>
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $activity->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- AI Insights Section (only show if AI module is enabled) --}}
        @if($this->aiInsightsEnabled)
            <div class="mt-6">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="sparkles" class="size-5 text-purple-500" />
                        <flux:heading size="lg">{{ __('AI Insights') }}</flux:heading>
                        <flux:badge size="sm" color="purple">{{ __('Beta') }}</flux:badge>
                    </div>
                    @if($this->currentBranch)
                        <flux:button variant="ghost" size="sm" :href="route('ai-insights.dashboard', $this->currentBranch)" wire:navigate>
                            {{ __('View Full Dashboard') }}
                            <flux:icon icon="arrow-right" class="ml-1 size-4" />
                        </flux:button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    {{-- At-Risk Donors Card --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('At-Risk Donors') }}</flux:text>
                            <div class="rounded-full {{ $this->atRiskDonorsCount > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                                <flux:icon icon="exclamation-triangle" class="size-5 {{ $this->atRiskDonorsCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                            </div>
                        </div>
                        <flux:heading size="2xl" class="mt-2 {{ $this->atRiskDonorsCount > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                            {{ $this->atRiskDonorsCount }}
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Churn risk > 70%') }}
                        </flux:text>
                    </div>

                    {{-- Attendance Anomalies Card --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Attendance Alerts') }}</flux:text>
                            <div class="rounded-full {{ $this->attendanceAnomaliesCount > 0 ? 'bg-amber-100 dark:bg-amber-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                                <flux:icon icon="arrow-trending-down" class="size-5 {{ $this->attendanceAnomaliesCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                            </div>
                        </div>
                        <flux:heading size="2xl" class="mt-2 {{ $this->attendanceAnomaliesCount > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">
                            {{ $this->attendanceAnomaliesCount }}
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Significant drop detected') }}
                        </flux:text>
                    </div>

                    {{-- High Potential Visitors Card --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('High Potential Visitors') }}</flux:text>
                            <div class="rounded-full {{ $this->highPotentialVisitors->isNotEmpty() ? 'bg-green-100 dark:bg-green-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                                <flux:icon icon="star" class="size-5 {{ $this->highPotentialVisitors->isNotEmpty() ? 'text-green-600 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                            </div>
                        </div>
                        <flux:heading size="2xl" class="mt-2 {{ $this->highPotentialVisitors->isNotEmpty() ? 'text-green-600 dark:text-green-400' : '' }}">
                            {{ $this->highPotentialVisitors->count() }}
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Conversion score > 70%') }}
                        </flux:text>
                    </div>
                </div>

                {{-- Member Lifecycle Distribution Widget --}}
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <flux:heading size="base">{{ __('Member Lifecycle') }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Distribution by engagement stage') }}</flux:text>
                            </div>
                            @if($this->membersNeedingAttentionCount > 0)
                                <flux:badge color="amber">{{ $this->membersNeedingAttentionCount }} {{ __('need attention') }}</flux:badge>
                            @endif
                        </div>

                        @if(empty($this->lifecycleDistribution) || collect($this->lifecycleDistribution)->sum('count') === 0)
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon icon="users" class="size-12 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No lifecycle data available') }}</flux:text>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($this->lifecycleDistribution as $stageData)
                                    @if($stageData['count'] > 0)
                                        <div class="flex items-center gap-3">
                                            <div class="flex w-32 items-center gap-2">
                                                <flux:icon :icon="$stageData['stage']->icon()" class="size-4 {{ $stageData['stage']->color() }}" />
                                                <flux:text class="text-sm">{{ $stageData['stage']->label() }}</flux:text>
                                            </div>
                                            <div class="flex-1">
                                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                                    @php
                                                        $total = collect($this->lifecycleDistribution)->sum('count');
                                                        $percent = $total > 0 ? ($stageData['count'] / $total) * 100 : 0;
                                                    @endphp
                                                    <div class="h-full rounded-full {{ str_replace('text-', 'bg-', $stageData['stage']->color()) }}" style="width: {{ $percent }}%"></div>
                                                </div>
                                            </div>
                                            <flux:text class="w-12 text-right text-sm font-medium">{{ $stageData['count'] }}</flux:text>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Cluster Health Widget --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4">
                            <flux:heading size="base">{{ __('Cluster Health') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Small group health overview') }}</flux:text>
                        </div>

                        @if(empty($this->clusterHealthDistribution) || collect($this->clusterHealthDistribution)->sum('count') === 0)
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon icon="user-group" class="size-12 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No cluster data available') }}</flux:text>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($this->clusterHealthDistribution as $levelData)
                                    @if($levelData['count'] > 0)
                                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-2 dark:border-zinc-800">
                                            <div class="flex items-center gap-2">
                                                <div class="size-3 rounded-full {{ str_replace('text-', 'bg-', $levelData['level']->color()) }}"></div>
                                                <flux:text class="text-sm">{{ $levelData['level']->label() }}</flux:text>
                                            </div>
                                            <flux:badge size="sm" :color="match($levelData['level']->value) {
                                                'thriving' => 'green',
                                                'healthy' => 'lime',
                                                'stable' => 'zinc',
                                                'struggling' => 'amber',
                                                'critical' => 'red',
                                                default => 'zinc',
                                            }">{{ $levelData['count'] }}</flux:badge>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @if($this->clustersNeedingAttention->isNotEmpty())
                                <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                    <flux:text class="mb-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Needs Attention') }}</flux:text>
                                    @foreach($this->clustersNeedingAttention->take(3) as $cluster)
                                        <div class="flex items-center justify-between py-1">
                                            <flux:text class="text-sm">{{ $cluster->name }}</flux:text>
                                            <flux:badge size="sm" :color="$cluster->health_level === \App\Enums\ClusterHealthLevel::Critical ? 'red' : 'amber'">
                                                {{ $cluster->health_level->label() }}
                                            </flux:badge>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Household, Prayer, and SMS Widgets Row --}}
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    {{-- Household Engagement Widget --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <flux:heading size="base">{{ __('Household Engagement') }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Family unit engagement') }}</flux:text>
                            </div>
                        </div>

                        @if(empty($this->householdEngagementDistribution) || collect($this->householdEngagementDistribution)->sum('count') === 0)
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon icon="home" class="size-12 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No household data available') }}</flux:text>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($this->householdEngagementDistribution as $levelData)
                                    @if($levelData['count'] > 0)
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <flux:icon :icon="$levelData['level']->icon()" class="size-4 {{ $levelData['level']->color() }}" />
                                                <flux:text class="text-sm">{{ $levelData['level']->label() }}</flux:text>
                                            </div>
                                            <flux:text class="text-sm font-medium">{{ $levelData['count'] }}</flux:text>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @if($this->householdsNeedingOutreachCount > 0)
                                <div class="mt-4 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                                        <strong>{{ $this->householdsNeedingOutreachCount }}</strong> {{ __('households need outreach') }}
                                    </flux:text>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Prayer Request Triage Widget --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <flux:heading size="base">{{ __('Prayer Requests') }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Triage by urgency') }}</flux:text>
                            </div>
                            @if($this->openPrayerRequestsCount > 0)
                                <flux:badge color="zinc">{{ $this->openPrayerRequestsCount }} {{ __('open') }}</flux:badge>
                            @endif
                        </div>

                        @if($this->openPrayerRequestsCount === 0)
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon icon="heart" class="size-12 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No open prayer requests') }}</flux:text>
                            </div>
                        @else
                            <div class="space-y-3">
                                @if($this->urgentPrayerRequestsCount > 0)
                                    <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                        <div class="flex items-center gap-2">
                                            <flux:icon icon="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                                            <flux:text class="font-medium text-red-700 dark:text-red-300">
                                                {{ $this->urgentPrayerRequestsCount }} {{ __('urgent requests') }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @endif

                                @if($this->criticalPrayerRequests->isNotEmpty())
                                    <div class="space-y-2">
                                        <flux:text class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Critical') }}</flux:text>
                                        @foreach($this->criticalPrayerRequests as $request)
                                            <div class="rounded-lg border border-red-200 p-2 dark:border-red-800">
                                                <flux:text class="text-sm font-medium">{{ $request->member?->first_name ?? __('Anonymous') }}</flux:text>
                                                <flux:text class="line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($request->request, 50) }}</flux:text>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- SMS Engagement Widget --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <flux:heading size="base">{{ __('SMS Engagement') }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member response rates') }}</flux:text>
                            </div>
                        </div>

                        @if(empty($this->smsEngagementDistribution) || collect($this->smsEngagementDistribution)->sum('count') === 0)
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon icon="chat-bubble-left-right" class="size-12 text-zinc-400" />
                                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No SMS engagement data') }}</flux:text>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($this->smsEngagementDistribution as $levelData)
                                    @if($levelData['count'] > 0)
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <flux:icon :icon="$levelData['level']->icon()" class="size-4 {{ $levelData['level']->color() }}" />
                                                <flux:text class="text-sm">{{ $levelData['level']->label() }}</flux:text>
                                            </div>
                                            <flux:text class="text-sm font-medium">{{ $levelData['count'] }}</flux:text>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @if($this->lowSmsEngagementCount > 0)
                                <div class="mt-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                        <strong>{{ $this->lowSmsEngagementCount }}</strong> {{ __('with low engagement') }}
                                    </flux:text>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Plan Usage Section (only show if there are quota limits) --}}
        @if($this->hasAnyQuotaLimits)
            <div class="mt-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="chart-bar" class="size-5 text-zinc-500" />
                            <flux:heading size="lg">{{ __('Plan Usage') }}</flux:heading>
                            @if($this->planName)
                                <flux:badge size="sm" color="zinc">{{ $this->planName }}</flux:badge>
                            @endif
                        </div>
                        @if(Route::has('upgrade.required'))
                            <flux:button variant="ghost" size="sm" href="{{ route('upgrade.required', ['module' => 'dashboard']) }}">
                                {{ __('Upgrade Plan') }}
                            </flux:button>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {{-- Members Quota --}}
                        @unless($this->memberQuota['unlimited'])
                            @php
                                $memberColor = match(true) {
                                    $this->memberQuota['percent'] >= 100 => 'red',
                                    $this->memberQuota['percent'] >= 80 => 'amber',
                                    default => 'blue',
                                };
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Members') }}</span>
                                    <span class="font-medium">{{ $this->memberQuota['current'] }} / {{ $this->memberQuota['max'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-{{ $memberColor }}-500 transition-all"
                                         style="width: {{ min($this->memberQuota['percent'], 100) }}%"></div>
                                </div>
                                @if($this->memberQuota['percent'] >= 80)
                                    <flux:text class="text-xs text-{{ $memberColor }}-600 dark:text-{{ $memberColor }}-400">
                                        {{ $this->memberQuota['remaining'] }} {{ __('remaining') }}
                                    </flux:text>
                                @endif
                            </div>
                        @endunless

                        {{-- SMS Quota --}}
                        @unless($this->smsQuota['unlimited'])
                            @php
                                $smsColor = match(true) {
                                    $this->smsQuota['percent'] >= 100 => 'red',
                                    $this->smsQuota['percent'] >= 80 => 'amber',
                                    default => 'green',
                                };
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('SMS Credits') }}</span>
                                    <span class="font-medium">{{ $this->smsQuota['sent'] }} / {{ $this->smsQuota['max'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-{{ $smsColor }}-500 transition-all"
                                         style="width: {{ min($this->smsQuota['percent'], 100) }}%"></div>
                                </div>
                                @if($this->smsQuota['percent'] >= 80)
                                    <flux:text class="text-xs text-{{ $smsColor }}-600 dark:text-{{ $smsColor }}-400">
                                        {{ $this->smsQuota['remaining'] }} {{ __('remaining this month') }}
                                    </flux:text>
                                @endif
                            </div>
                        @endunless

                        {{-- Storage Quota --}}
                        @unless($this->storageQuota['unlimited'])
                            @php
                                $storageColor = match(true) {
                                    $this->storageQuota['percent'] >= 100 => 'red',
                                    $this->storageQuota['percent'] >= 80 => 'amber',
                                    default => 'purple',
                                };
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Storage') }}</span>
                                    <span class="font-medium">{{ $this->storageQuota['used'] }} / {{ $this->storageQuota['max'] }} GB</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-{{ $storageColor }}-500 transition-all"
                                         style="width: {{ min($this->storageQuota['percent'], 100) }}%"></div>
                                </div>
                                @if($this->storageQuota['percent'] >= 80)
                                    <flux:text class="text-xs text-{{ $storageColor }}-600 dark:text-{{ $storageColor }}-400">
                                        {{ $this->storageQuota['remaining'] }} GB {{ __('remaining') }}
                                    </flux:text>
                                @endif
                            </div>
                        @endunless

                        {{-- Branches Quota --}}
                        @unless($this->branchQuota['unlimited'])
                            @php
                                $branchColor = match(true) {
                                    $this->branchQuota['percent'] >= 100 => 'red',
                                    $this->branchQuota['percent'] >= 80 => 'amber',
                                    default => 'cyan',
                                };
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ __('Branches') }}</span>
                                    <span class="font-medium">{{ $this->branchQuota['current'] }} / {{ $this->branchQuota['max'] }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-{{ $branchColor }}-500 transition-all"
                                         style="width: {{ min($this->branchQuota['percent'], 100) }}%"></div>
                                </div>
                                @if($this->branchQuota['percent'] >= 80)
                                    <flux:text class="text-xs text-{{ $branchColor }}-600 dark:text-{{ $branchColor }}-400">
                                        {{ $this->branchQuota['remaining'] }} {{ __('remaining') }}
                                    </flux:text>
                                @endif
                            </div>
                        @endunless
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- No Branch Selected State --}}
        <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon icon="building-office" class="size-16 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No Branch Selected') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Please select a branch from the sidebar to view dashboard metrics.') }}</flux:text>
        </div>
    @endif
</section>
