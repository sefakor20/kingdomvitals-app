<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Events') }}</flux:heading>
            <flux:subheading>{{ __('Manage events for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        @if($this->canCreate)
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('Create Event') }}
            </flux:button>
        @endif
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search events...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->eventTypes as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->eventStatuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="timeFilter">
                <flux:select.option value="">{{ __('All Time') }}</flux:select.option>
                <flux:select.option value="upcoming">{{ __('Upcoming') }}</flux:select.option>
                <flux:select.option value="ongoing">{{ __('Ongoing') }}</flux:select.option>
                <flux:select.option value="past">{{ __('Past') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if($this->hasActiveFilters)
        <div class="mb-4">
            <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                {{ __('Clear Filters') }}
            </flux:button>
        </div>
    @endif

    @if($events->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 py-12 dark:border-zinc-700">
            <flux:icon icon="calendar-days" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No events found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by creating your first event.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Create Event') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($events as $event)
                <div wire:key="event-{{ $event->id }}" class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition-shadow hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                    {{-- Event Header with Status --}}
                    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" :color="$event->event_type->color()">
                                {{ $event->event_type->label() }}
                            </flux:badge>
                            @if($event->isRecurring())
                                <flux:badge size="sm" color="purple" icon="arrow-path">
                                    {{ __('Recurring') }}
                                </flux:badge>
                            @elseif($event->isOccurrence())
                                <flux:badge size="sm" color="sky" icon="arrow-path">
                                    {{ __('Series') }}
                                </flux:badge>
                            @endif
                        </div>
                        <flux:badge size="sm" :color="$event->status->color()">
                            {{ $event->status->label() }}
                        </flux:badge>
                    </div>

                    {{-- Event Content --}}
                    <div class="p-4">
                        <a href="{{ route('events.show', [$branch, $event]) }}" class="block" wire:navigate>
                            <h3 class="text-lg font-semibold text-zinc-900 group-hover:text-blue-600 dark:text-zinc-100 dark:group-hover:text-blue-400">
                                {{ $event->name }}
                            </h3>
                        </a>

                        <div class="mt-3 space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="calendar" class="size-4" />
                                <span>{{ $event->starts_at->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:icon icon="map-pin" class="size-4" />
                                <span class="truncate">{{ $event->location }}</span>
                            </div>
                            @if($event->capacity)
                                <div class="flex items-center gap-2">
                                    <flux:icon icon="users" class="size-4" />
                                    <span>{{ $event->registered_count }}/{{ $event->capacity }} {{ __('registered') }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Price Badge --}}
                        <div class="mt-3">
                            @if($event->is_paid)
                                <flux:badge color="green" size="sm">
                                    {{ $event->formatted_price }}
                                </flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">
                                    {{ __('Free') }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <a href="{{ route('events.show', [$branch, $event]) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300" wire:navigate>
                            {{ __('View Details') }}
                        </a>
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                @can('update', $event)
                                    <flux:menu.item wire:click="edit('{{ $event->id }}')" icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                @endcan
                                @can('manageRegistrations', $event)
                                    <flux:menu.item href="{{ route('events.registrations', [$branch, $event]) }}" icon="users" wire:navigate>
                                        {{ __('Registrations') }}
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('events.check-in', [$branch, $event]) }}" icon="qr-code" wire:navigate>
                                        {{ __('Check-In') }}
                                    </flux:menu.item>
                                @endcan
                                @can('delete', $event)
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete('{{ $event->id }}')" icon="trash" variant="danger">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($events->hasPages())
            <div class="mt-6">
                {{ $events->links() }}
            </div>
        @endif
    @endif

    {{-- Create Modal --}}
    <flux:modal wire:model.self="showCreateModal" name="create-event" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Create Event') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Event Name')" placeholder="{{ __('e.g., Annual Youth Conference') }}" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="3" placeholder="{{ __('Brief description of the event...') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="event_type" :label="__('Event Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->eventTypes as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="category" :label="__('Category')" placeholder="{{ __('e.g., Youth, Music') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="starts_at" type="datetime-local" :label="__('Start Date & Time')" required />
                    <flux:input wire:model="ends_at" type="datetime-local" :label="__('End Date & Time')" />
                </div>

                <flux:input wire:model="location" :label="__('Location')" placeholder="{{ __('e.g., Main Auditorium') }}" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="address" :label="__('Address')" placeholder="{{ __('Street address') }}" />
                    <flux:input wire:model="city" :label="__('City')" placeholder="{{ __('City') }}" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" placeholder="{{ __('Leave empty for unlimited') }}" />
                    <flux:select wire:model="visibility" :label="__('Visibility')" required>
                        @foreach($this->visibilityOptions as $option)
                            <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Registration Settings') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model="allow_registration" />
                        <flux:text>{{ __('Allow Registration') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="registration_opens_at" type="datetime-local" :label="__('Registration Opens')" />
                        <flux:input wire:model="registration_closes_at" type="datetime-local" :label="__('Registration Closes')" />
                    </div>
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Pricing') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model.live="is_paid" />
                        <flux:text>{{ __('This is a paid event') }}</flux:text>
                    </div>

                    @if($is_paid)
                        <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Price (GHS)')" placeholder="0.00" />
                    @endif
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Recurrence') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model.live="is_recurring" />
                        <flux:text>{{ __('This is a recurring event') }}</flux:text>
                    </div>

                    @if($is_recurring)
                        <flux:select wire:model="recurrence_pattern" :label="__('Repeat')" required>
                            <option value="">{{ __('Select pattern...') }}</option>
                            @foreach($this->recurrencePatterns as $pattern)
                                <option value="{{ $pattern->value }}">{{ $pattern->label() }}</option>
                            @endforeach
                        </flux:select>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="recurrence_ends_at" type="date" :label="__('End Date')" :description="__('Leave empty for no end date')" />
                            <flux:input wire:model="recurrence_count" type="number" min="2" max="52" :label="__('Or after # occurrences')" placeholder="e.g., 12" />
                        </div>

                        <flux:text size="sm" class="text-zinc-500">
                            {{ __('Occurrences will be generated for the next 3 months automatically.') }}
                        </flux:text>
                    @endif
                </div>

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach($this->eventStatuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Event') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model.self="showEditModal" name="edit-event" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Event') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Event Name')" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="event_type" :label="__('Event Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->eventTypes as $type)
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="category" :label="__('Category')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="starts_at" type="datetime-local" :label="__('Start Date & Time')" required />
                    <flux:input wire:model="ends_at" type="datetime-local" :label="__('End Date & Time')" />
                </div>

                <flux:input wire:model="location" :label="__('Location')" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="address" :label="__('Address')" />
                    <flux:input wire:model="city" :label="__('City')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" />
                    <flux:select wire:model="visibility" :label="__('Visibility')" required>
                        @foreach($this->visibilityOptions as $option)
                            <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Registration Settings') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model="allow_registration" />
                        <flux:text>{{ __('Allow Registration') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="registration_opens_at" type="datetime-local" :label="__('Registration Opens')" />
                        <flux:input wire:model="registration_closes_at" type="datetime-local" :label="__('Registration Closes')" />
                    </div>
                </div>

                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Pricing') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model.live="is_paid" />
                        <flux:text>{{ __('This is a paid event') }}</flux:text>
                    </div>

                    @if($is_paid)
                        <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Price (GHS)')" />
                    @endif
                </div>

                @if(!$editingEvent?->isOccurrence())
                <div class="space-y-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:heading size="sm">{{ __('Recurrence') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model.live="is_recurring" />
                        <flux:text>{{ __('This is a recurring event') }}</flux:text>
                    </div>

                    @if($is_recurring)
                        <flux:select wire:model="recurrence_pattern" :label="__('Repeat')" required>
                            <option value="">{{ __('Select pattern...') }}</option>
                            @foreach($this->recurrencePatterns as $pattern)
                                <option value="{{ $pattern->value }}">{{ $pattern->label() }}</option>
                            @endforeach
                        </flux:select>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="recurrence_ends_at" type="date" :label="__('End Date')" :description="__('Leave empty for no end date')" />
                            <flux:input wire:model="recurrence_count" type="number" min="2" max="52" :label="__('Or after # occurrences')" placeholder="e.g., 12" />
                        </div>

                        <flux:text size="sm" class="text-zinc-500">
                            {{ __('Changes will be applied to all future occurrences.') }}
                        </flux:text>
                    @endif
                </div>
                @else
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <flux:text size="sm" class="text-blue-700 dark:text-blue-300">
                        {{ __('This is an occurrence of a recurring event. To change recurrence settings, edit the parent event.') }}
                    </flux:text>
                </div>
                @endif

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach($this->eventStatuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelEdit" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal wire:model.self="showDeleteModal" name="delete-event" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Event') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete ":name"? This will also delete all registrations. This action cannot be undone.', ['name' => $deletingEvent?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Event') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Toasts --}}
    <x-toast on="event-created" type="success">
        {{ __('Event created successfully.') }}
    </x-toast>

    <x-toast on="event-updated" type="success">
        {{ __('Event updated successfully.') }}
    </x-toast>

    <x-toast on="event-deleted" type="success">
        {{ __('Event deleted successfully.') }}
    </x-toast>
</section>
