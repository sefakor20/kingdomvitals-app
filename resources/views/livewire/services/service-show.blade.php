<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('services.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1">
                            <flux:icon.check class="size-4" />
                            {{ __('Save') }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
            @if($this->canDelete && !$editing)
                <flux:button variant="danger" wire:click="confirmDelete" icon="trash">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Service Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon icon="calendar" class="size-6 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="name" placeholder="{{ __('Service Name') }}" class="w-64" />
                        </div>
                        @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $service->name }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2">
                        @if($editing)
                            <flux:select wire:model="service_type" class="w-40">
                                @foreach($this->serviceTypes as $type)
                                    <flux:select.option value="{{ $type->value }}">
                                        {{ ucfirst($type->value) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:badge color="zinc" size="sm">
                                {{ ucfirst($service->service_type->value) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
            @if($editing)
                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>
            @else
                <flux:badge
                    :color="$service->is_active ? 'green' : 'zinc'"
                    size="lg"
                >
                    {{ $service->is_active ? __('Active') : __('Inactive') }}
                </flux:badge>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Service Schedule -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Schedule') }}</flux:heading>
            <dl class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Day of Week') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:select wire:model="day_of_week">
                                    <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                                    <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                                    <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                                    <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                                    <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                                    <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                                    <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                                </flux:select>
                            @else
                                {{ $this->getDayName($service->day_of_week) }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Time') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:input type="time" wire:model="time" />
                            @else
                                {{ $service->time }}
                            @endif
                        </dd>
                    </div>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="number" wire:model="capacity" min="1" class="w-32" placeholder="{{ __('No limit') }}" />
                        @else
                            {{ $service->capacity ?? __('No limit') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Statistics (Read-Only) -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Statistics') }}</flux:heading>
            <dl class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Attendance Records') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->attendanceCount }}
                        </dd>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Donations') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->donationCount }}
                        </dd>
                    </div>
                </div>
                <flux:text size="sm" class="text-zinc-500">
                    {{ __('Attendance and donation statistics are available when those features are implemented.') }}
                </flux:text>
            </dl>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-service" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Service') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $service->name]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Service') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toast -->
    <x-toast on="service-updated" type="success">
        {{ __('Service updated successfully.') }}
    </x-toast>
</section>
