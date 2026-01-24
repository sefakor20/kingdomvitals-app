<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Auto-Generate Rosters') }}</flux:heading>
            <flux:subheading>{{ __('Automatically create duty rosters with personnel assignments') }}</flux:subheading>
        </div>

        <flux:button href="{{ route('duty-rosters.index', $branch) }}" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Rosters') }}
        </flux:button>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @foreach([1 => __('Select Dates'), 2 => __('Select Pools'), 3 => __('Preview'), 4 => __('Complete')] as $stepNum => $stepLabel)
                <div class="flex items-center {{ $stepNum < 4 ? 'flex-1' : '' }}">
                    <button
                        type="button"
                        wire:click="goToStep({{ $stepNum }})"
                        @disabled($stepNum > $step)
                        class="flex items-center gap-2 {{ $step >= $stepNum ? 'text-blue-600' : 'text-zinc-400' }} {{ $stepNum <= $step ? 'cursor-pointer' : 'cursor-default' }}"
                    >
                        <span class="flex size-8 items-center justify-center rounded-full {{ $step >= $stepNum ? 'bg-blue-600 text-white' : 'bg-zinc-200 text-zinc-500' }}">
                            @if($step > $stepNum)
                                <flux:icon icon="check" class="size-4" />
                            @else
                                {{ $stepNum }}
                            @endif
                        </span>
                        <span class="hidden text-sm font-medium sm:inline">{{ $stepLabel }}</span>
                    </button>
                    @if($stepNum < 4)
                        <div class="mx-4 h-0.5 flex-1 {{ $step > $stepNum ? 'bg-blue-600' : 'bg-zinc-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <!-- Step 1: Select Dates -->
        @if($step === 1)
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('Select Service and Date Range') }}</flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date')" required />
                </div>

                <flux:separator />

                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('Option 1: Select a recurring service') }}</flux:heading>
                    <flux:select wire:model.live="service_id" :label="__('Service')">
                        <flux:select.option value="">{{ __('Select service...') }}</flux:select.option>
                        @foreach($this->services as $service)
                            <flux:select.option value="{{ $service->id }}">
                                {{ $service->name }} ({{ $this->daysOfWeekOptions[$service->day_of_week] ?? '' }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Rosters will be generated for each occurrence of this service in the date range.') }}
                    </flux:text>
                </div>

                @if(!$service_id)
                    <flux:separator />

                    <div class="space-y-4">
                        <flux:heading size="sm">{{ __('Option 2: Select specific days of the week') }}</flux:heading>
                        <div class="flex flex-wrap gap-3">
                            @foreach($this->daysOfWeekOptions as $value => $label)
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        wire:model.live="days_of_week"
                                        value="{{ $value }}"
                                        class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <flux:text class="text-sm text-zinc-500">
                            {{ __('Rosters will be generated for each selected day in the date range.') }}
                        </flux:text>
                    </div>
                @endif

                @error('service_id')
                    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            </div>
        @endif

        <!-- Step 2: Select Pools -->
        @if($step === 2)
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('Select Personnel Pools') }}</flux:heading>
                <flux:text class="text-zinc-500">
                    {{ __('Choose which pools to use for automatic personnel assignment. Leave empty to skip assignment for that role.') }}
                </flux:text>

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <flux:select wire:model="preacher_pool_id" :label="__('Preacher Pool')">
                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                            @foreach($this->preacherPools as $pool)
                                <flux:select.option value="{{ $pool->id }}">
                                    {{ $pool->name }} ({{ $pool->members_count }} members)
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if($this->preacherPools->isEmpty())
                            <flux:text class="mt-1 text-sm text-zinc-500">
                                {{ __('No preacher pools available.') }}
                                <a href="{{ route('duty-rosters.pools.index', $branch) }}" class="text-blue-600 hover:underline" wire:navigate>{{ __('Create one') }}</a>
                            </flux:text>
                        @endif
                    </div>

                    <div>
                        <flux:select wire:model="liturgist_pool_id" :label="__('Liturgist Pool')">
                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                            @foreach($this->liturgistPools as $pool)
                                <flux:select.option value="{{ $pool->id }}">
                                    {{ $pool->name }} ({{ $pool->members_count }} members)
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if($this->liturgistPools->isEmpty())
                            <flux:text class="mt-1 text-sm text-zinc-500">
                                {{ __('No liturgist pools available.') }}
                                <a href="{{ route('duty-rosters.pools.index', $branch) }}" class="text-blue-600 hover:underline" wire:navigate>{{ __('Create one') }}</a>
                            </flux:text>
                        @endif
                    </div>

                    <div>
                        <flux:select wire:model="reader_pool_id" :label="__('Reader Pool')">
                            <flux:select.option value="">{{ __('None') }}</flux:select.option>
                            @foreach($this->readerPools as $pool)
                                <flux:select.option value="{{ $pool->id }}">
                                    {{ $pool->name }} ({{ $pool->members_count }} members)
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if($this->readerPools->isEmpty())
                            <flux:text class="mt-1 text-sm text-zinc-500">
                                {{ __('No reader pools available.') }}
                                <a href="{{ route('duty-rosters.pools.index', $branch) }}" class="text-blue-600 hover:underline" wire:navigate>{{ __('Create one') }}</a>
                            </flux:text>
                        @endif
                    </div>
                </div>

                <flux:callout variant="info" icon="information-circle">
                    {{ __('Members will be assigned using round-robin rotation. Members marked as unavailable for a date will be skipped.') }}
                </flux:callout>
            </div>
        @endif

        <!-- Step 3: Preview -->
        @if($step === 3)
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Preview') }}</flux:heading>
                    <flux:checkbox wire:model="skipExisting" :label="__('Skip existing rosters')" />
                </div>

                @if(empty($preview))
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        {{ __('No rosters to generate. Please adjust your date range or service selection.') }}
                    </flux:callout>
                @else
                    <flux:text class="text-zinc-500">
                        {{ __(':count rosters will be created:', ['count' => count($preview)]) }}
                    </flux:text>

                    <div class="max-h-96 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Date') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Preacher') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Liturgist') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Reader') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                @foreach($preview as $item)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item['date']->format('D, M j, Y') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $item['preacher']?->fullName() ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $item['liturgist']?->fullName() ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $item['reader']?->fullName() ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            @if(!empty($item['conflicts']))
                                                <flux:badge color="amber" size="sm">{{ __('Conflict') }}</flux:badge>
                                            @else
                                                <flux:badge color="green" size="sm">{{ __('Ready') }}</flux:badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 4: Complete -->
        @if($step === 4)
            <div class="py-12 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                    <flux:icon icon="check" class="size-8 text-green-600 dark:text-green-400" />
                </div>
                <flux:heading size="lg">{{ __('Rosters Generated Successfully!') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    {{ __(':count duty rosters have been created.', ['count' => $generatedCount]) }}
                </flux:text>
            </div>
        @endif

        <!-- Navigation Buttons -->
        <div class="mt-8 flex justify-between border-t border-zinc-200 pt-6 dark:border-zinc-700">
            @if($step > 1 && $step < 4)
                <flux:button variant="ghost" wire:click="previousStep" icon="arrow-left">
                    {{ __('Previous') }}
                </flux:button>
            @else
                <div></div>
            @endif

            @if($step < 3)
                <flux:button variant="primary" wire:click="nextStep" icon-trailing="arrow-right">
                    {{ __('Next') }}
                </flux:button>
            @elseif($step === 3)
                <flux:button
                    variant="primary"
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    :disabled="empty($preview)"
                >
                    <span wire:loading.remove wire:target="generate">{{ __('Generate Rosters') }}</span>
                    <span wire:loading wire:target="generate">{{ __('Generating...') }}</span>
                </flux:button>
            @elseif($step === 4)
                <div class="flex gap-3">
                    <flux:button variant="ghost" wire:click="startOver">
                        {{ __('Generate More') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="finish">
                        {{ __('View Rosters') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</section>
