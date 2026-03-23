<div>
    <div class="mb-8">
        <flux:heading size="xl">{{ __('My Events') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('View your event registrations.') }}
        </flux:text>
    </div>

    {{-- Upcoming Events --}}
    <flux:card class="mb-6">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Upcoming Events') }}</flux:heading>
        </div>

        @if($this->upcomingRegistrations->isEmpty())
            <div class="p-8 text-center text-zinc-500">
                {{ __('You have no upcoming event registrations.') }}
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($this->upcomingRegistrations as $registration)
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex size-12 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <flux:icon name="calendar" class="size-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <flux:heading>{{ $registration->event->name }}</flux:heading>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $registration->event->starts_at->format('M d, Y') }}
                                    {{ __('at') }} {{ $registration->event->starts_at->format('g:i A') }}
                                </flux:text>
                                @if($registration->event->location)
                                    <flux:text class="text-sm text-zinc-400">
                                        <flux:icon name="map-pin" class="inline size-3" />
                                        {{ $registration->event->location }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:badge color="green" size="sm">{{ __('Registered') }}</flux:badge>
                            @if($registration->ticket_number)
                                <flux:badge size="sm">{{ $registration->ticket_number }}</flux:badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Past Events --}}
    @if($this->pastRegistrations->isNotEmpty())
        <flux:card>
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Past Events') }}</flux:heading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Event') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->pastRegistrations as $registration)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="font-medium">{{ $registration->event->name }}</div>
                                @if($registration->event->location)
                                    <div class="text-sm text-zinc-500">{{ $registration->event->location }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $registration->event->starts_at->format('M d, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($registration->checked_in_at)
                                    <flux:badge color="green" size="sm">{{ __('Attended') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Registered') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
