<section class="w-full">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('events.index', $branch) }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                        <flux:icon icon="arrow-left" class="size-5" />
                    </a>
                    <flux:heading size="xl" level="1">{{ $event->name }}</flux:heading>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <flux:badge size="sm" :color="$event->event_type->color()">{{ $event->event_type->label() }}</flux:badge>
                    <flux:badge size="sm" :color="$event->status->color()">{{ $event->status->label() }}</flux:badge>
                    <flux:badge size="sm" :color="$event->visibility->value === 'public' ? 'green' : 'zinc'">{{ $event->visibility->label() }}</flux:badge>
                    @if($event->is_paid)
                        <flux:badge size="sm" color="amber">{{ $event->formatted_price }}</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">{{ __('Free') }}</flux:badge>
                    @endif
                </div>
            </div>

            @if($this->canManageRegistrations)
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" wire:click="openAddRegistration" icon="user-plus">
                        {{ __('Add Registration') }}
                    </flux:button>
                    <a href="{{ route('events.check-in', [$branch, $event]) }}" wire:navigate>
                        <flux:button variant="ghost" icon="qr-code">
                            {{ __('Check-In') }}
                        </flux:button>
                    </a>
                    @if($event->is_public && $event->allow_registration)
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" icon="share">
                                {{ __('Share') }}
                            </flux:button>
                            <flux:menu>
                                <div class="p-3 min-w-[300px]">
                                    <flux:text class="text-sm font-medium mb-2">{{ __('Public Registration Link') }}</flux:text>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 rounded bg-zinc-100 px-2 py-1 text-xs truncate dark:bg-zinc-700">{{ $this->publicRegistrationUrl }}</code>
                                        <flux:button variant="ghost" size="sm" icon="clipboard" onclick="navigator.clipboard.writeText('{{ $this->publicRegistrationUrl }}')">
                                            {{ __('Copy') }}
                                        </flux:button>
                                    </div>
                                    <flux:text class="text-xs text-zinc-500 mt-2">
                                        {{ __('Share this link to allow people to register for this event.') }}
                                    </flux:text>
                                </div>
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Event Details --}}
    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Event Details') }}</flux:heading>

                @if($event->description)
                    <p class="mb-4 text-zinc-600 dark:text-zinc-400">{{ $event->description }}</p>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3">
                        <flux:icon icon="calendar" class="mt-0.5 size-5 text-zinc-400" />
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->starts_at->format('l, F j, Y') }}</div>
                            <div class="text-sm text-zinc-500">
                                {{ $event->starts_at->format('g:i A') }}
                                @if($event->ends_at)
                                    - {{ $event->ends_at->format('g:i A') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <flux:icon icon="map-pin" class="mt-0.5 size-5 text-zinc-400" />
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->location }}</div>
                            @if($event->address || $event->city)
                                <div class="text-sm text-zinc-500">
                                    {{ collect([$event->address, $event->city])->filter()->join(', ') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($event->capacity)
                        <div class="flex items-start gap-3">
                            <flux:icon icon="users" class="mt-0.5 size-5 text-zinc-400" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->registered_count }} / {{ $event->capacity }}</div>
                                <div class="text-sm text-zinc-500">{{ __('Registered') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($event->organizer)
                        <div class="flex items-start gap-3">
                            <flux:icon icon="user" class="mt-0.5 size-5 text-zinc-400" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->organizer->full_name }}</div>
                                <div class="text-sm text-zinc-500">{{ __('Organizer') }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats Sidebar --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-4">{{ __('Registration Stats') }}</flux:heading>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Total Registered') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Attended') }}</span>
                        <span class="font-semibold text-green-600">{{ $this->stats['attended'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('No Show') }}</span>
                        <span class="font-semibold text-amber-600">{{ $this->stats['no_show'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Cancelled') }}</span>
                        <span class="font-semibold text-red-600">{{ $this->stats['cancelled'] }}</span>
                    </div>
                </div>

                @if($event->capacity)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Capacity') }}</span>
                            <span class="text-sm font-medium">{{ round(($this->stats['total'] / $event->capacity) * 100) }}%</span>
                        </div>
                        <div class="h-2 bg-zinc-200 rounded-full dark:bg-zinc-700">
                            <div class="h-2 bg-blue-600 rounded-full" style="width: {{ min(100, ($this->stats['total'] / $event->capacity) * 100) }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if($event->is_registration_open)
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <flux:icon icon="check-circle" class="size-5" />
                        <span class="font-medium">{{ __('Registration Open') }}</span>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <flux:icon icon="x-circle" class="size-5" />
                        <span class="font-medium">{{ __('Registration Closed') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Registrations List --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Registrations') }}</flux:heading>
        </div>

        @if($this->registrations->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="users" class="size-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">{{ __('No registrations yet') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Registrations will appear here once people sign up.') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Attendee') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Payment') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Ticket') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Registered') }}</th>
                            <th class="relative px-6 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach($this->registrations as $registration)
                            <tr wire:key="reg-{{ $registration->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $registration->attendee_name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $registration->attendee_email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge size="sm" color="zinc">{{ ucfirst($registration->attendee_type) }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge size="sm" :color="$registration->status->color()">
                                        {{ $registration->status->label() }}
                                    </flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($registration->requires_payment && !$registration->is_paid)
                                        <flux:badge size="sm" color="amber">{{ __('Pending') }}</flux:badge>
                                    @elseif($registration->is_paid)
                                        <flux:badge size="sm" color="green">{{ __('Paid') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">{{ __('Free') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500">
                                    {{ $registration->ticket_number ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500">
                                    {{ $registration->registered_at->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    @if($this->canManageRegistrations)
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                            <flux:menu>
                                                @if($registration->status === \App\Enums\RegistrationStatus::Registered)
                                                    <flux:menu.item wire:click="checkIn('{{ $registration->id }}')" icon="check">
                                                        {{ __('Check In') }}
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="markAsNoShow('{{ $registration->id }}')" icon="user-minus">
                                                        {{ __('Mark No Show') }}
                                                    </flux:menu.item>
                                                @endif
                                                @if($registration->is_checked_in && !$registration->is_checked_out)
                                                    <flux:menu.item wire:click="checkOut('{{ $registration->id }}')" icon="arrow-left-start-on-rectangle">
                                                        {{ __('Check Out') }}
                                                    </flux:menu.item>
                                                @endif
                                                @if($registration->status !== \App\Enums\RegistrationStatus::Cancelled)
                                                    <flux:menu.separator />
                                                    <flux:menu.item wire:click="confirmCancel('{{ $registration->id }}')" icon="x-mark" variant="danger">
                                                        {{ __('Cancel Registration') }}
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Add Registration Modal --}}
    <flux:modal wire:model.self="showAddRegistrationModal" name="add-registration" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Registration') }}</flux:heading>

            <form wire:submit="addRegistration" class="space-y-4">
                <div>
                    <flux:label class="mb-2">{{ __('Attendee Type') }}</flux:label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model.live="registrationType" value="member" class="text-blue-600">
                            <span>{{ __('Member') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model.live="registrationType" value="visitor" class="text-blue-600">
                            <span>{{ __('Visitor') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model.live="registrationType" value="guest" class="text-blue-600">
                            <span>{{ __('Guest') }}</span>
                        </label>
                    </div>
                </div>

                @if($registrationType === 'member')
                    <flux:select wire:model="member_id" :label="__('Select Member')" required>
                        <flux:select.option value="">{{ __('Choose a member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->last_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @elseif($registrationType === 'visitor')
                    <flux:select wire:model="visitor_id" :label="__('Select Visitor')" required>
                        <flux:select.option value="">{{ __('Choose a visitor...') }}</flux:select.option>
                        @foreach($this->visitors as $visitor)
                            <flux:select.option value="{{ $visitor->id }}">{{ $visitor->first_name }} {{ $visitor->last_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:input wire:model="guest_name" :label="__('Full Name')" required />
                    <flux:input wire:model="guest_email" type="email" :label="__('Email')" required />
                    <flux:input wire:model="guest_phone" :label="__('Phone')" />
                @endif

                @if($event->is_paid)
                    <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                        <p class="text-sm text-amber-700 dark:text-amber-400">
                            {{ __('This is a paid event. The registration will be marked as requiring payment.') }}
                        </p>
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelAddModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Registration') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Cancel Registration Modal --}}
    <flux:modal wire:model.self="showCancelRegistrationModal" name="cancel-registration" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Cancel Registration') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to cancel the registration for :name?', ['name' => $cancellingRegistration?->attendee_name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelCancelModal">
                    {{ __('Keep Registration') }}
                </flux:button>
                <flux:button variant="danger" wire:click="cancelRegistration">
                    {{ __('Cancel Registration') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Toasts --}}
    <x-toast on="registration-added" type="success">
        {{ __('Registration added successfully.') }}
    </x-toast>

    <x-toast on="attendee-checked-in" type="success">
        {{ __('Attendee checked in successfully.') }}
    </x-toast>

    <x-toast on="attendee-checked-out" type="success">
        {{ __('Attendee checked out successfully.') }}
    </x-toast>

    <x-toast on="registration-updated" type="success">
        {{ __('Registration updated successfully.') }}
    </x-toast>

    <x-toast on="registration-cancelled" type="success">
        {{ __('Registration cancelled successfully.') }}
    </x-toast>
</section>
