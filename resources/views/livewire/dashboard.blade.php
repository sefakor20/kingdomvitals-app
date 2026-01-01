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
    @else
        {{-- No Branch Selected State --}}
        <div class="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon icon="building-office" class="size-16 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No Branch Selected') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Please select a branch from the sidebar to view dashboard metrics.') }}</flux:text>
        </div>
    @endif
</section>
