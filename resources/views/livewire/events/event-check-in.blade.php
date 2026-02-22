<section class="w-full">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <a href="{{ route('events.show', [$branch, $event]) }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                <flux:icon icon="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl" level="1">{{ __('Event Check-In') }}</flux:heading>
                <flux:subheading>{{ $event->name }}</flux:subheading>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Registered') }}</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total'] }}</div>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">{{ __('Checked In') }}</div>
            <div class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">{{ $this->stats['checked_in'] }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Pending') }}</div>
            <div class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $this->stats['pending'] }}</div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name or ticket number...') }}"
                icon="magnifying-glass"
                autofocus
            />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Registrations') }}</flux:select.option>
                <flux:select.option value="pending">{{ __('Pending Check-In') }}</flux:select.option>
                <flux:select.option value="checked_in">{{ __('Checked In') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Registrations List --}}
    @if($this->registrations->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 py-12 dark:border-zinc-700">
            <flux:icon icon="users" class="size-12 text-zinc-400" />
            <flux:heading size="md" class="mt-4">{{ __('No registrations found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $statusFilter)
                    {{ __('Try adjusting your search or filter.') }}
                @else
                    {{ __('No one has registered for this event yet.') }}
                @endif
            </flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->registrations as $registration)
                <div
                    wire:key="checkin-{{ $registration->id }}"
                    @class([
                        'flex items-center justify-between rounded-xl border p-4 transition-colors',
                        'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' => $registration->status === \App\Enums\RegistrationStatus::Attended,
                        'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' => $registration->status !== \App\Enums\RegistrationStatus::Attended,
                    ])
                >
                    <div class="flex items-center gap-4">
                        {{-- Status Indicator --}}
                        <div @class([
                            'flex size-10 items-center justify-center rounded-full',
                            'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-400' => $registration->status === \App\Enums\RegistrationStatus::Attended,
                            'bg-zinc-100 text-zinc-400 dark:bg-zinc-700 dark:text-zinc-500' => $registration->status !== \App\Enums\RegistrationStatus::Attended,
                        ])>
                            @if($registration->status === \App\Enums\RegistrationStatus::Attended)
                                <flux:icon icon="check" class="size-5" />
                            @else
                                <flux:icon icon="user" class="size-5" />
                            @endif
                        </div>

                        {{-- Attendee Info --}}
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $registration->attendee_name }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-zinc-500">
                                @if($registration->ticket_number)
                                    <span class="font-mono">{{ $registration->ticket_number }}</span>
                                    <span>&bull;</span>
                                @endif
                                <span>{{ ucfirst($registration->attendee_type) }}</span>
                                @if($registration->check_in_time)
                                    <span>&bull;</span>
                                    <span>{{ __('Checked in at :time', ['time' => $registration->check_in_time->format('g:i A')]) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($registration->status === \App\Enums\RegistrationStatus::Registered)
                            <flux:button variant="primary" wire:click="checkIn('{{ $registration->id }}')" icon="check">
                                {{ __('Check In') }}
                            </flux:button>
                        @elseif($registration->status === \App\Enums\RegistrationStatus::Attended)
                            @if(!$registration->is_checked_out)
                                <flux:button variant="ghost" wire:click="checkOut('{{ $registration->id }}')" icon="arrow-left-start-on-rectangle">
                                    {{ __('Check Out') }}
                                </flux:button>
                            @endif
                            <flux:button variant="ghost" wire:click="undoCheckIn('{{ $registration->id }}')" icon="arrow-uturn-left">
                                {{ __('Undo') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Toasts --}}
    <x-toast on="checked-in" type="success">
        {{ __(':name checked in successfully.', ['name' => '$event.detail.name']) }}
    </x-toast>

    <x-toast on="checked-out" type="success">
        {{ __('Attendee checked out successfully.') }}
    </x-toast>

    <x-toast on="check-in-undone" type="info">
        {{ __('Check-in undone.') }}
    </x-toast>
</section>
