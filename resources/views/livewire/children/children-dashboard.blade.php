<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __("Children's Ministry Dashboard") }}</flux:heading>
            <flux:subheading>{{ __('Overview for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="primary" href="{{ route('children.index', $branch) }}" icon="users">
                {{ __('View Directory') }}
            </flux:button>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Children') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="users" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['totalChildren']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Checked In Today') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['checkedInToday']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Unassigned') }}</flux:text>
                <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                    <flux:icon icon="exclamation-triangle" class="size-4 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['unassignedChildren']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('With Emergency Contact') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="phone" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['withEmergencyContact']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('With Medical Info') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="heart" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['withMedicalInfo']) }}</flux:heading>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Children by Age Group -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Children by Age Group') }}</flux:heading>
                <flux:button variant="ghost" href="{{ route('children.age-groups', $branch) }}" size="sm">
                    {{ __('Manage') }}
                </flux:button>
            </div>

            @if($this->ageGroups->isEmpty())
                <div class="flex flex-col items-center justify-center py-8">
                    <flux:icon icon="user-group" class="size-8 text-zinc-400" />
                    <flux:text class="mt-2 text-zinc-500">{{ __('No age groups created yet.') }}</flux:text>
                    <flux:button variant="primary" href="{{ route('children.age-groups', $branch) }}" size="sm" class="mt-4">
                        {{ __('Create Age Groups') }}
                    </flux:button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($this->childrenByAgeGroup as $group)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-3 rounded-full" style="background-color: {{ $group['color'] }}"></div>
                                <flux:text>{{ $group['name'] }}</flux:text>
                            </div>
                            <flux:badge>{{ $group['count'] }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Check-Ins -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Check-Ins') }}</flux:heading>

            @if($this->recentCheckIns->isEmpty())
                <div class="flex flex-col items-center justify-center py-8">
                    <flux:icon icon="clipboard-document-check" class="size-8 text-zinc-400" />
                    <flux:text class="mt-2 text-zinc-500">{{ __('No recent check-ins.') }}</flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($this->recentCheckIns as $checkIn)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" name="{{ $checkIn->child?->fullName() ?? 'Unknown' }}" />
                                <div>
                                    <flux:text class="font-medium">{{ $checkIn->child?->fullName() ?? 'Unknown' }}</flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ $checkIn->attendance?->service?->name ?? '-' }}
                                    </flux:text>
                                </div>
                            </div>
                            <div class="text-right">
                                @if($checkIn->is_checked_out)
                                    <flux:badge color="zinc">{{ __('Checked Out') }}</flux:badge>
                                @else
                                    <flux:badge color="green">{{ __('Checked In') }}</flux:badge>
                                @endif
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $checkIn->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Quick Actions') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('children.index', $branch) }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 transition hover:border-blue-500 hover:bg-blue-50 dark:border-zinc-700 dark:hover:border-blue-500 dark:hover:bg-blue-900/20">
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="users" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="font-medium">{{ __('Children Directory') }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ __('View all children') }}</flux:text>
                </div>
            </a>

            <a href="{{ route('children.age-groups', $branch) }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 transition hover:border-green-500 hover:bg-green-50 dark:border-zinc-700 dark:hover:border-green-500 dark:hover:bg-green-900/20">
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="user-group" class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:text class="font-medium">{{ __('Age Groups') }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ __('Manage age groups') }}</flux:text>
                </div>
            </a>

            <a href="{{ route('members.index', $branch) }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 transition hover:border-purple-500 hover:bg-purple-50 dark:border-zinc-700 dark:hover:border-purple-500 dark:hover:bg-purple-900/20">
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="user-plus" class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <flux:text class="font-medium">{{ __('Add New Member') }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ __('Register a child') }}</flux:text>
                </div>
            </a>

            <a href="{{ route('households.index', $branch) }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 transition hover:border-amber-500 hover:bg-amber-50 dark:border-zinc-700 dark:hover:border-amber-500 dark:hover:bg-amber-900/20">
                <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                    <flux:icon icon="home" class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:text class="font-medium">{{ __('Households') }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ __('Manage families') }}</flux:text>
                </div>
            </a>
        </div>
    </div>
</section>
