<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/50">
                <flux:icon icon="sparkles" class="size-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:heading size="xl">{{ __('AI Insights Dashboard') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Predictive analytics and engagement insights for :branch', ['branch' => $branch->name]) }}
                </flux:text>
            </div>
        </div>
        <flux:badge color="purple">{{ __('Beta') }}</flux:badge>
    </div>

    {{-- Summary Stats Row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Members') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total_members']) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Clusters') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total_clusters']) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Households') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total_households']) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Visitors') }}</flux:text>
            <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total_visitors']) }}</flux:heading>
        </div>
    </div>

    {{-- AI Alerts Panel --}}
    @if($this->recentAlerts->isNotEmpty())
        <div class="rounded-xl border {{ $this->highPriorityAlertsCount > 0 ? 'border-red-200 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="rounded-full {{ $this->highPriorityAlertsCount > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-purple-100 dark:bg-purple-900' }} p-2">
                        <flux:icon icon="bell-alert" class="size-5 {{ $this->highPriorityAlertsCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-purple-600 dark:text-purple-400' }}" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('AI Alerts') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Recent alerts requiring attention') }}
                        </flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($this->alertStats['critical'] > 0)
                        <flux:badge color="red">{{ $this->alertStats['critical'] }} {{ __('Critical') }}</flux:badge>
                    @endif
                    @if($this->alertStats['high'] > 0)
                        <flux:badge color="orange">{{ $this->alertStats['high'] }} {{ __('High') }}</flux:badge>
                    @endif
                    @if($this->alertStats['unread'] > 0)
                        <flux:badge color="zinc">{{ $this->alertStats['unread'] }} {{ __('Unread') }}</flux:badge>
                    @endif
                </div>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($this->recentAlerts as $alert)
                    <div class="flex items-start gap-4 px-6 py-4 {{ !$alert->is_read ? 'bg-zinc-50 dark:bg-zinc-800/50' : '' }}">
                        <div class="rounded-full p-2 {{ match($alert->severity->value) {
                            'critical' => 'bg-red-100 dark:bg-red-900',
                            'high' => 'bg-orange-100 dark:bg-orange-900',
                            'medium' => 'bg-amber-100 dark:bg-amber-900',
                            default => 'bg-zinc-100 dark:bg-zinc-800',
                        } }}">
                            <flux:icon :icon="$alert->icon" class="size-4 {{ match($alert->severity->value) {
                                'critical' => 'text-red-600 dark:text-red-400',
                                'high' => 'text-orange-600 dark:text-orange-400',
                                'medium' => 'text-amber-600 dark:text-amber-400',
                                default => 'text-zinc-600 dark:text-zinc-400',
                            } }}" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium">{{ $alert->title }}</flux:text>
                                @if(!$alert->is_read)
                                    <span class="size-2 rounded-full bg-blue-500"></span>
                                @endif
                            </div>
                            <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $alert->description }}
                            </flux:text>
                            <div class="mt-2 flex items-center gap-3">
                                <flux:badge size="sm" :color="$alert->color">{{ $alert->severity->label() }}</flux:badge>
                                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $alert->created_at->diffForHumans() }}
                                </flux:text>
                                @if($alert->relatedEntityName)
                                    <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $alert->relatedEntityName }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!$alert->is_read)
                                <flux:button size="xs" variant="ghost" wire:click="markAlertAsRead('{{ $alert->id }}')">
                                    <flux:icon icon="check" class="size-4" />
                                </flux:button>
                            @endif
                            @if(!$alert->is_acknowledged)
                                <flux:button size="xs" variant="ghost" wire:click="acknowledgeAlert('{{ $alert->id }}')">
                                    <flux:icon icon="check-circle" class="size-4" />
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if($this->alertStats['total'] > 10)
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:text class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Showing :shown of :total alerts', ['shown' => min(10, $this->alertStats['total']), 'total' => $this->alertStats['total']]) }}
                    </flux:text>
                </div>
            @endif
        </div>
    @endif

    {{-- Alert Cards Row --}}
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

            @if($this->atRiskDonors->isNotEmpty())
                <div class="mt-4 space-y-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    @foreach($this->atRiskDonors->take(5) as $donor)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$donor->fullName()" />
                                <flux:text class="text-sm">{{ $donor->fullName() }}</flux:text>
                            </div>
                            <flux:badge size="sm" color="red">{{ number_format($donor->churn_risk_score) }}%</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
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

            @if($this->attendanceAnomalies->isNotEmpty())
                <div class="mt-4 space-y-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    @foreach($this->attendanceAnomalies->take(5) as $member)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$member->fullName()" />
                                <flux:text class="text-sm">{{ $member->fullName() }}</flux:text>
                            </div>
                            <flux:badge size="sm" color="amber">{{ number_format($member->attendance_anomaly_score) }}%</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- High Potential Visitors Card --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('High Potential Visitors') }}</flux:text>
                <div class="rounded-full {{ $this->highPotentialVisitorsCount > 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                    <flux:icon icon="star" class="size-5 {{ $this->highPotentialVisitorsCount > 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                </div>
            </div>
            <flux:heading size="2xl" class="mt-2 {{ $this->highPotentialVisitorsCount > 0 ? 'text-green-600 dark:text-green-400' : '' }}">
                {{ $this->highPotentialVisitorsCount }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Conversion score > 70%') }}
            </flux:text>

            @if($this->highPotentialVisitors->isNotEmpty())
                <div class="mt-4 space-y-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    @foreach($this->highPotentialVisitors->take(5) as $visitor)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$visitor->fullName()" />
                                <flux:text class="text-sm">{{ $visitor->fullName() }}</flux:text>
                            </div>
                            <flux:badge size="sm" color="green">{{ number_format($visitor->conversion_score) }}%</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Member Lifecycle Distribution --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <flux:heading size="base">{{ __('Member Lifecycle Distribution') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Where are your members in their engagement journey?') }}</flux:text>
            </div>
            @if($this->membersNeedingAttentionCount > 0)
                <flux:badge color="amber">{{ $this->membersNeedingAttentionCount }} {{ __('need attention') }}</flux:badge>
            @endif
        </div>

        @if(empty($this->lifecycleDistribution) || collect($this->lifecycleDistribution)->sum('count') === 0)
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <flux:icon icon="users" class="size-12 text-zinc-400" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No lifecycle data available') }}</flux:text>
                <flux:text class="text-sm text-zinc-400">{{ __('Lifecycle stages are calculated weekly') }}</flux:text>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($this->lifecycleDistribution as $stageData)
                    @if($stageData['count'] > 0)
                        <div class="rounded-lg border border-zinc-100 p-4 dark:border-zinc-800">
                            <div class="flex items-center gap-2">
                                <flux:icon :icon="$stageData['stage']->icon()" class="size-5 {{ $stageData['stage']->color() }}" />
                                <flux:text class="font-medium">{{ $stageData['stage']->label() }}</flux:text>
                            </div>
                            <div class="mt-2">
                                <flux:heading size="xl">{{ $stageData['count'] }}</flux:heading>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-full rounded-full {{ str_replace('text-', 'bg-', $stageData['stage']->color()) }}" style="width: {{ $stageData['percentage'] }}%"></div>
                                </div>
                                <flux:text class="mt-1 text-xs text-zinc-500">{{ $stageData['percentage'] }}%</flux:text>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Two Column Layout: Cluster Health & Household Engagement --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Cluster Health --}}
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
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="flex items-center gap-3">
                                    <div class="size-3 rounded-full {{ str_replace('text-', 'bg-', $levelData['level']->color()) }}"></div>
                                    <flux:text>{{ $levelData['level']->label() }}</flux:text>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:text class="text-sm text-zinc-500">{{ $levelData['percentage'] }}%</flux:text>
                                    <flux:badge size="sm" :color="match($levelData['level']->value) {
                                        'thriving' => 'green',
                                        'healthy' => 'lime',
                                        'stable' => 'zinc',
                                        'struggling' => 'amber',
                                        'critical' => 'red',
                                        default => 'zinc',
                                    }">{{ $levelData['count'] }}</flux:badge>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if($this->clustersNeedingAttention->isNotEmpty())
                    <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Needs Attention') }}</flux:text>
                        @foreach($this->clustersNeedingAttention as $cluster)
                            <a href="{{ route('clusters.show', [$branch, $cluster]) }}" wire:navigate class="flex items-center justify-between rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <flux:text>{{ $cluster->name }}</flux:text>
                                <flux:badge size="sm" :color="$cluster->health_level === \App\Enums\ClusterHealthLevel::Critical ? 'red' : 'amber'">
                                    {{ $cluster->health_level->label() }}
                                </flux:badge>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Household Engagement --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="base">{{ __('Household Engagement') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Family engagement levels') }}</flux:text>
                </div>
                @if($this->householdsNeedingOutreachCount > 0)
                    <flux:badge color="amber">{{ $this->householdsNeedingOutreachCount }} {{ __('need outreach') }}</flux:badge>
                @endif
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
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="flex items-center gap-3">
                                    <div class="size-3 rounded-full {{ str_replace('text-', 'bg-', $levelData['level']->color()) }}"></div>
                                    <flux:text>{{ $levelData['level']->label() }}</flux:text>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:text class="text-sm text-zinc-500">{{ $levelData['percentage'] }}%</flux:text>
                                    <flux:badge size="sm" :color="match($levelData['level']->value) {
                                        'fully_engaged' => 'green',
                                        'partially_engaged' => 'blue',
                                        'low' => 'amber',
                                        'disengaged' => 'red',
                                        default => 'zinc',
                                    }">{{ $levelData['count'] }}</flux:badge>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Two Column Layout: Prayer Requests & SMS Engagement --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Prayer Request Urgency --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="base">{{ __('Prayer Requests') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Open requests by urgency') }}</flux:text>
                </div>
                <flux:badge>{{ $this->openPrayerRequestsCount }} {{ __('open') }}</flux:badge>
            </div>

            @if(empty($this->prayerUrgencyDistribution) || collect($this->prayerUrgencyDistribution)->sum('count') === 0)
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <flux:icon icon="hand-raised" class="size-12 text-zinc-400" />
                    <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No open prayer requests') }}</flux:text>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($this->prayerUrgencyDistribution as $levelData)
                        <div class="rounded-lg border border-zinc-100 p-3 text-center dark:border-zinc-800">
                            <flux:heading size="lg" class="{{ $levelData['level']->color() }}">{{ $levelData['count'] }}</flux:heading>
                            <flux:text class="text-xs">{{ $levelData['level']->label() }}</flux:text>
                        </div>
                    @endforeach
                </div>

                @if($this->criticalPrayerRequests->isNotEmpty())
                    <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Urgent Requests') }}</flux:text>
                        @foreach($this->criticalPrayerRequests as $prayer)
                            <div class="mb-2 rounded-lg border border-red-100 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <flux:text class="font-medium">{{ $prayer->member?->fullName() ?? __('Anonymous') }}</flux:text>
                                        <flux:text class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ Str::limit($prayer->request, 100) }}</flux:text>
                                    </div>
                                    <flux:badge size="sm" color="red">{{ $prayer->urgency_level->label() }}</flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- SMS Engagement --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <flux:heading size="base">{{ __('SMS Engagement') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member engagement with SMS campaigns') }}</flux:text>
                </div>
                @if($this->lowSmsEngagementCount > 0)
                    <flux:badge color="amber">{{ $this->lowSmsEngagementCount }} {{ __('low engagement') }}</flux:badge>
                @endif
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
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="flex items-center gap-3">
                                    <flux:icon :icon="$levelData['level']->icon()" class="size-4 {{ $levelData['level']->color() }}" />
                                    <flux:text>{{ $levelData['level']->label() }}</flux:text>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:text class="text-sm text-zinc-500">{{ $levelData['percentage'] }}%</flux:text>
                                    <flux:badge size="sm" :color="$levelData['level']->color()">{{ $levelData['count'] }}</flux:badge>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Prayer Summary --}}
    @if($this->latestPrayerSummary)
        <div class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-white p-6 dark:border-purple-800 dark:from-purple-950 dark:to-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900">
                        <flux:icon icon="sparkles" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <flux:heading size="base">{{ __('AI Prayer Summary') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ ucfirst($this->latestPrayerSummary->period_type) }}: {{ $this->latestPrayerSummary->period_label }}
                        </flux:text>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge color="purple">{{ $this->latestPrayerSummary->total_requests }} {{ __('requests') }}</flux:badge>
                    @if($this->latestPrayerSummary->answer_rate > 0)
                        <flux:badge color="green">{{ $this->latestPrayerSummary->answer_rate }}% {{ __('answered') }}</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Summary Text --}}
            <div class="prose prose-sm max-w-none dark:prose-invert">
                <p class="text-zinc-700 dark:text-zinc-300">{{ $this->latestPrayerSummary->summary_text }}</p>
            </div>

            {{-- Key Themes & Recommendations --}}
            <div class="mt-6 grid gap-6 md:grid-cols-2">
                {{-- Key Themes --}}
                @if(!empty($this->latestPrayerSummary->key_themes))
                    <div>
                        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wider text-purple-600 dark:text-purple-400">{{ __('Key Themes') }}</flux:text>
                        <ul class="space-y-1.5">
                            @foreach($this->latestPrayerSummary->key_themes as $theme)
                                <li class="flex items-start gap-2">
                                    <flux:icon icon="check-circle" class="mt-0.5 size-4 shrink-0 text-purple-500" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $theme }}</flux:text>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Pastoral Recommendations --}}
                @if(!empty($this->latestPrayerSummary->pastoral_recommendations))
                    <div>
                        <flux:text class="mb-2 text-xs font-medium uppercase tracking-wider text-purple-600 dark:text-purple-400">{{ __('Recommendations') }}</flux:text>
                        <ul class="space-y-1.5">
                            @foreach($this->latestPrayerSummary->pastoral_recommendations as $recommendation)
                                <li class="flex items-start gap-2">
                                    <flux:icon icon="light-bulb" class="mt-0.5 size-4 shrink-0 text-amber-500" />
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $recommendation }}</flux:text>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Category Breakdown --}}
            @if(!empty($this->latestPrayerSummary->category_breakdown))
                <div class="mt-6 border-t border-purple-100 pt-4 dark:border-purple-900">
                    <flux:text class="mb-3 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Request Categories') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($this->latestPrayerSummary->category_breakdown as $category => $count)
                            <flux:badge size="sm" color="zinc">
                                {{ ucfirst($category) }}: {{ $count }}
                            </flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Financial Forecasts --}}
    <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-6 dark:border-emerald-800 dark:from-emerald-950 dark:to-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900">
                    <flux:icon icon="currency-dollar" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:heading size="base">{{ __('Financial Forecasts') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Predicted giving for upcoming periods') }}</flux:text>
                </div>
            </div>
            @if($this->financialForecastSummary['average_confidence'] > 0)
                <flux:badge :color="$this->financialForecastSummary['average_confidence'] >= 70 ? 'green' : ($this->financialForecastSummary['average_confidence'] >= 50 ? 'amber' : 'zinc')">
                    {{ $this->financialForecastSummary['average_confidence'] }}% {{ __('confidence') }}
                </flux:badge>
            @endif
        </div>

        @if($this->financialForecasts->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon icon="banknotes" class="size-12 text-zinc-400" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No financial forecasts available') }}</flux:text>
                <flux:text class="text-sm text-zinc-400">{{ __('Forecasts are generated based on historical giving data') }}</flux:text>
            </div>
        @else
            {{-- Summary Cards --}}
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-emerald-100 bg-white p-4 dark:border-emerald-900 dark:bg-zinc-900">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Predicted') }}</flux:text>
                    <flux:heading size="lg" class="text-emerald-600 dark:text-emerald-400">
                        {{ number_format($this->financialForecastSummary['total_predicted'], 2) }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400">{{ __('Next 4 months') }}</flux:text>
                </div>
                @if($this->financialForecastSummary['total_budget'] > 0)
                    <div class="rounded-lg border border-emerald-100 bg-white p-4 dark:border-emerald-900 dark:bg-zinc-900">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Budget Target') }}</flux:text>
                        <flux:heading size="lg">
                            {{ number_format($this->financialForecastSummary['total_budget'], 2) }}
                        </flux:heading>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-white p-4 dark:border-emerald-900 dark:bg-zinc-900">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Gap') }}</flux:text>
                        @php
                            $gap = $this->financialForecastSummary['total_gap'];
                            $gapColor = $gap >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        @endphp
                        <flux:heading size="lg" class="{{ $gapColor }}">
                            {{ $gap >= 0 ? '+' : '' }}{{ number_format($gap, 2) }}
                        </flux:heading>
                    </div>
                @endif
            </div>

            {{-- Forecast Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-emerald-100 dark:border-emerald-900">
                            <th class="pb-3 text-left text-sm font-medium text-zinc-500">{{ __('Period') }}</th>
                            <th class="pb-3 text-right text-sm font-medium text-zinc-500">{{ __('Predicted') }}</th>
                            <th class="pb-3 text-right text-sm font-medium text-zinc-500">{{ __('Confidence') }}</th>
                            <th class="pb-3 text-right text-sm font-medium text-zinc-500">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-50 dark:divide-emerald-900/50">
                        @foreach($this->financialForecasts as $forecast)
                            <tr>
                                <td class="py-3">
                                    <flux:text class="font-medium">{{ $forecast->period_label }}</flux:text>
                                </td>
                                <td class="py-3 text-right">
                                    <flux:text>{{ number_format($forecast->predicted_total, 2) }}</flux:text>
                                </td>
                                <td class="py-3 text-right">
                                    <flux:badge size="sm" :color="$forecast->confidenceBadgeColor()">
                                        {{ number_format($forecast->confidence_score, 0) }}%
                                    </flux:badge>
                                </td>
                                <td class="py-3 text-right">
                                    @if($forecast->isOnTrack() === true)
                                        <flux:badge size="sm" color="green">{{ __('On Track') }}</flux:badge>
                                    @elseif($forecast->isOnTrack() === false)
                                        <flux:badge size="sm" color="red">{{ __('At Risk') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">{{ __('No Target') }}</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Giving Breakdown for First Forecast --}}
            @php $firstForecast = $this->financialForecasts->first(); @endphp
            @if($firstForecast && $firstForecast->predicted_total > 0)
                <div class="mt-6 border-t border-emerald-100 pt-4 dark:border-emerald-900">
                    <flux:text class="mb-3 text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('Expected Giving Breakdown for :period', ['period' => $firstForecast->period_label]) }}
                    </flux:text>
                    <div class="flex flex-wrap gap-3">
                        @if($firstForecast->predicted_tithes > 0)
                            <div class="rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/30">
                                <flux:text class="text-xs text-zinc-500">{{ __('Tithes') }}</flux:text>
                                <flux:text class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($firstForecast->predicted_tithes, 2) }}</flux:text>
                            </div>
                        @endif
                        @if($firstForecast->predicted_offerings > 0)
                            <div class="rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/30">
                                <flux:text class="text-xs text-zinc-500">{{ __('Offerings') }}</flux:text>
                                <flux:text class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($firstForecast->predicted_offerings, 2) }}</flux:text>
                            </div>
                        @endif
                        @if($firstForecast->predicted_special > 0)
                            <div class="rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/30">
                                <flux:text class="text-xs text-zinc-500">{{ __('Special') }}</flux:text>
                                <flux:text class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($firstForecast->predicted_special, 2) }}</flux:text>
                            </div>
                        @endif
                        @if($firstForecast->predicted_other > 0)
                            <div class="rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/30">
                                <flux:text class="text-xs text-zinc-500">{{ __('Other') }}</flux:text>
                                <flux:text class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($firstForecast->predicted_other, 2) }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Attendance Forecasts --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4">
            <flux:heading size="base">{{ __('Attendance Forecasts') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Predicted attendance for upcoming services') }}</flux:text>
        </div>

        @if($this->attendanceForecasts->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon icon="chart-bar" class="size-12 text-zinc-400" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('No forecasts available') }}</flux:text>
                <flux:text class="text-sm text-zinc-400">{{ __('Forecasts are generated weekly') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="pb-3 text-left text-sm font-medium text-zinc-500">{{ __('Date') }}</th>
                            <th class="pb-3 text-left text-sm font-medium text-zinc-500">{{ __('Service') }}</th>
                            <th class="pb-3 text-right text-sm font-medium text-zinc-500">{{ __('Predicted') }}</th>
                            <th class="pb-3 text-right text-sm font-medium text-zinc-500">{{ __('Confidence') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->attendanceForecasts->take(8) as $forecast)
                            <tr>
                                <td class="py-3">
                                    <flux:text>{{ $forecast->forecast_date->format('M j, Y') }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:text>{{ $forecast->service?->name ?? __('Unknown') }}</flux:text>
                                </td>
                                <td class="py-3 text-right">
                                    <flux:badge size="sm">{{ $forecast->predicted_total }}</flux:badge>
                                </td>
                                <td class="py-3 text-right">
                                    @php
                                        $confidence = $forecast->confidence ?? 0;
                                        $color = match(true) {
                                            $confidence >= 80 => 'green',
                                            $confidence >= 60 => 'blue',
                                            $confidence >= 40 => 'amber',
                                            default => 'red',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$color">{{ $confidence }}%</flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
