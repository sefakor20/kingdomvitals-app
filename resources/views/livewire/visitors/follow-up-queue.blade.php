<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Follow-Up Queue') }}</flux:heading>
            <flux:subheading>{{ __('Manage pending visitor follow-ups for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" href="{{ route('visitors.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back to Visitors') }}
            </flux:button>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pending') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="clock" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Overdue') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="exclamation-circle" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 {{ $this->stats['overdue'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                {{ number_format($this->stats['overdue']) }}
            </flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Due Today') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="calendar" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 {{ $this->stats['dueToday'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : '' }}">
                {{ number_format($this->stats['dueToday']) }}
            </flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Upcoming (7 days)') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="arrow-trending-up" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['upcoming']) }}</flux:heading>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search visitor name, phone, email...') }}"
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option :value="null">{{ __('All Types') }}</flux:select.option>
                @foreach($this->followUpTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ ucfirst($type->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="memberFilter">
                <flux:select.option :value="null">{{ __('All Assignees') }}</flux:select.option>
                <flux:select.option value="unassigned">{{ __('Unassigned') }}</flux:select.option>
                @foreach($this->members as $member)
                    <flux:select.option value="{{ $member->id }}">
                        {{ $member->fullName() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    <!-- Overdue Section -->
    @if($this->overdueFollowUps->isNotEmpty())
        <div class="mb-8">
            <div class="mb-4 flex items-center gap-2">
                <flux:badge color="red" size="sm">{{ __('Overdue') }}</flux:badge>
                <flux:text class="text-sm text-zinc-500">{{ $this->overdueFollowUps->count() }} {{ __('follow-ups') }}</flux:text>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->overdueFollowUps as $followUp)
                    <x-follow-up-card :follow-up="$followUp" :branch="$branch" urgency="overdue" />
                @endforeach
            </div>
        </div>
    @endif

    <!-- Due Today Section -->
    @if($this->dueTodayFollowUps->isNotEmpty())
        <div class="mb-8">
            <div class="mb-4 flex items-center gap-2">
                <flux:badge color="yellow" size="sm">{{ __('Due Today') }}</flux:badge>
                <flux:text class="text-sm text-zinc-500">{{ $this->dueTodayFollowUps->count() }} {{ __('follow-ups') }}</flux:text>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->dueTodayFollowUps as $followUp)
                    <x-follow-up-card :follow-up="$followUp" :branch="$branch" urgency="today" />
                @endforeach
            </div>
        </div>
    @endif

    <!-- Upcoming Section -->
    @if($this->upcomingFollowUps->isNotEmpty())
        <div class="mb-8">
            <div class="mb-4 flex items-center gap-2">
                <flux:badge color="blue" size="sm">{{ __('Upcoming') }}</flux:badge>
                <flux:text class="text-sm text-zinc-500">{{ $this->upcomingFollowUps->count() }} {{ __('follow-ups in next 7 days') }}</flux:text>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->upcomingFollowUps as $followUp)
                    <x-follow-up-card :follow-up="$followUp" :branch="$branch" urgency="upcoming" />
                @endforeach
            </div>
        </div>
    @endif

    <!-- Empty State -->
    @if($this->overdueFollowUps->isEmpty() && $this->dueTodayFollowUps->isEmpty() && $this->upcomingFollowUps->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-lg border border-zinc-200 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon icon="clipboard-document-check" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No pending follow-ups') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your filters or search criteria.') }}
                @else
                    {{ __('All follow-ups have been completed. Great job!') }}
                @endif
            </flux:text>
            @if($this->hasActiveFilters)
                <flux:button variant="ghost" wire:click="clearFilters" class="mt-4">
                    {{ __('Clear Filters') }}
                </flux:button>
            @endif
        </div>
    @endif

    <!-- Complete Follow-up Modal -->
    <flux:modal wire:model.self="showCompleteModal" name="complete-follow-up" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Complete Follow-Up') }}</flux:heading>

            @if($completingFollowUp)
                <div class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:text class="font-medium">{{ $completingFollowUp->visitor->fullName() }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">
                        {{ ucfirst($completingFollowUp->type->value) }} - {{ __('Scheduled for') }} {{ $completingFollowUp->scheduled_at?->format('M d, Y g:i A') }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:select wire:model="completionOutcome" :label="__('Outcome')">
                        @foreach($this->followUpOutcomes as $outcome)
                            <flux:select.option value="{{ $outcome->value }}">
                                {{ ucwords(str_replace('_', ' ', $outcome->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="completionPerformedBy" :label="__('Performed By')">
                        <flux:select.option :value="null">{{ __('Select member (optional)') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    @if($this->followUpTemplates->isNotEmpty())
                        <flux:select wire:model.live="selectedTemplateId" :label="__('Template')">
                            <flux:select.option value="">{{ __('None (keep current notes)') }}</flux:select.option>
                            @foreach($this->followUpTemplates as $template)
                                <flux:select.option value="{{ $template->id }}">
                                    {{ $template->name }}
                                    @if(!$template->type)
                                        ({{ __('Generic') }})
                                    @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    <flux:textarea wire:model="completionNotes" :label="__('Notes')" rows="3" />
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeCompleteModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="completeFollowUp">
                    {{ __('Complete Follow-Up') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Reschedule Modal -->
    <flux:modal wire:model.self="showRescheduleModal" name="reschedule-follow-up" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Reschedule Follow-Up') }}</flux:heading>

            @if($reschedulingFollowUp)
                <div class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:text class="font-medium">{{ $reschedulingFollowUp->visitor->fullName() }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">
                        {{ ucfirst($reschedulingFollowUp->type->value) }} - {{ __('Currently scheduled for') }} {{ $reschedulingFollowUp->scheduled_at?->format('M d, Y g:i A') }}
                    </flux:text>
                </div>

                <flux:input
                    type="datetime-local"
                    wire:model="rescheduleDate"
                    :label="__('New Date & Time')"
                />
                @error('rescheduleDate')
                    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeRescheduleModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="rescheduleFollowUp">
                    {{ __('Reschedule') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="follow-up-completed" type="success">
        {{ __('Follow-up completed successfully.') }}
    </x-toast>

    <x-toast on="follow-up-rescheduled" type="success">
        {{ __('Follow-up rescheduled successfully.') }}
    </x-toast>
</section>
