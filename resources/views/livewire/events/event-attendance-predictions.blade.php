<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" size="sm" :href="route('branches.events.show', [$branch, $event])" wire:navigate>
                <flux:icon icon="arrow-left" class="size-4" />
            </flux:button>
            <div class="rounded-lg bg-indigo-100 p-2 dark:bg-indigo-900/50">
                <flux:icon icon="chart-bar" class="size-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
                <flux:heading size="xl">{{ __('Attendance Predictions') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $event->name }} - {{ $event->starts_at->format('M j, Y') }}
                </flux:text>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($this->featureEnabled)
                <flux:button wire:click="regeneratePredictions" variant="ghost" size="sm">
                    <flux:icon icon="arrow-path" class="size-4" />
                    {{ __('Regenerate') }}
                </flux:button>
                <flux:button wire:click="sendBulkInvitations" size="sm">
                    <flux:icon icon="paper-airplane" class="size-4" />
                    {{ __('Send Invitations') }}
                </flux:button>
            @endif
        </div>
    </div>

    @if(!$this->featureEnabled)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon icon="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                <div>
                    <flux:heading size="base">{{ __('Feature Disabled') }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ __('Event attendance prediction is currently disabled. Enable it in AI settings to use this feature.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    @else
        {{-- Summary Stats --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Predictions') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ number_format($this->summaryStats['total']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('High Probability') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-green-600 dark:text-green-400">{{ number_format($this->summaryStats['high']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Medium Probability') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-amber-600 dark:text-amber-400">{{ number_format($this->summaryStats['medium']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Low Probability') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-zinc-600 dark:text-zinc-400">{{ number_format($this->summaryStats['low']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Already Invited') }}</flux:text>
                <flux:heading size="2xl" class="mt-1 text-blue-600 dark:text-blue-400">{{ number_format($this->summaryStats['invited']) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Avg. Probability') }}</flux:text>
                <flux:heading size="2xl" class="mt-1">{{ $this->summaryStats['avg_probability'] }}%</flux:heading>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <flux:icon icon="funnel" class="size-4 text-zinc-500" />
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Filters') }}</flux:text>
                </div>

                {{-- Search --}}
                <flux:input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search members...') }}" size="sm" class="w-48" />

                {{-- Tier Filter --}}
                <flux:select wire:model.live="tierFilter" size="sm" class="w-36" placeholder="{{ __('All Tiers') }}">
                    <option value="">{{ __('All Tiers') }}</option>
                    @foreach($this->availableTiers as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- Invited Filter --}}
                <flux:select wire:model.live="invitedFilter" size="sm" class="w-36" placeholder="{{ __('All Status') }}">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="invited">{{ __('Invited') }}</option>
                    <option value="not_invited">{{ __('Not Invited') }}</option>
                </flux:select>

                {{-- Reset Filters --}}
                @if($tierFilter !== '' || $invitedFilter !== '' || $search !== '')
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                        <flux:icon icon="x-mark" class="size-4" />
                        {{ __('Reset') }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Predictions Table --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Member') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Probability') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Tier') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Top Factors') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Status') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($this->predictions as $prediction)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-8 items-center justify-center rounded-full bg-zinc-200 text-sm font-medium dark:bg-zinc-700">
                                            {{ substr($prediction->member->first_name, 0, 1) }}{{ substr($prediction->member->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <flux:text class="font-medium">{{ $prediction->member->fullName() }}</flux:text>
                                            @if($prediction->member->phone)
                                                <flux:text class="text-xs text-zinc-500">{{ $prediction->member->phone }}</flux:text>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                            <div class="h-full rounded-full {{ $prediction->prediction_tier === \App\Enums\PredictionTier::High ? 'bg-green-500' : ($prediction->prediction_tier === \App\Enums\PredictionTier::Medium ? 'bg-amber-500' : 'bg-zinc-400') }}"
                                                 style="width: {{ min($prediction->attendance_probability, 100) }}%"></div>
                                        </div>
                                        <flux:text class="text-sm font-medium">{{ number_format($prediction->attendance_probability, 0) }}%</flux:text>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:badge :color="$prediction->prediction_tier->color()">
                                        {{ $prediction->prediction_tier->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if($prediction->factors)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(array_slice(array_keys($prediction->factors), 0, 2) as $factor)
                                                <flux:badge size="sm" color="zinc">{{ str_replace('_', ' ', ucfirst($factor)) }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <flux:text class="text-sm text-zinc-400">-</flux:text>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($prediction->invitation_sent)
                                        <flux:badge color="green">
                                            <flux:icon icon="check" class="mr-1 size-3" />
                                            {{ __('Invited') }}
                                        </flux:badge>
                                    @else
                                        <flux:badge color="zinc">{{ __('Pending') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if(!$prediction->invitation_sent)
                                        <flux:button wire:click="markAsInvited('{{ $prediction->id }}')" variant="ghost" size="sm">
                                            <flux:icon icon="paper-airplane" class="size-4" />
                                        </flux:button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <flux:icon icon="chart-bar" class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                                    <flux:text class="mt-2 text-zinc-500">{{ __('No predictions yet.') }}</flux:text>
                                    <flux:button wire:click="regeneratePredictions" variant="ghost" size="sm" class="mt-4">
                                        {{ __('Generate Predictions') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->predictions->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->predictions->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
