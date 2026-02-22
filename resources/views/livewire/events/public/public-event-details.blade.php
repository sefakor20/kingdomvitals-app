@php
    $logoUrl = null;
    if (function_exists('tenant') && tenant()) {
        $logoUrl = tenant()->getLogoUrl('medium');
    }
    if (!$logoUrl) {
        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
        if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['medium'])) {
            $path = $platformLogoPaths['medium'];
            $fullPath = base_path('storage/app/public/'.$path);
            if (file_exists($fullPath)) {
                $logoUrl = url('storage/'.$path);
            }
        }
    }
@endphp

<div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:p-8">
    {{-- Event Header --}}
    <div class="mb-6 text-center">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $branch->name }}" class="mx-auto mb-4 h-16 w-16 rounded-full object-cover" />
        @else
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                <flux:icon.calendar class="size-8 text-zinc-500 dark:text-zinc-400" />
            </div>
        @endif
        <flux:badge size="sm" :color="$event->event_type->color()" class="mb-3">
            {{ $event->event_type->label() }}
        </flux:badge>
        <flux:heading size="xl" level="1">{{ $event->name }}</flux:heading>
        <flux:text class="mt-2 text-zinc-500">
            {{ $branch->name }}
        </flux:text>
    </div>

    {{-- Status Badge --}}
    @if($event->status === \App\Enums\EventStatus::Completed)
        <div class="mb-6 rounded-lg bg-zinc-100 p-3 text-center dark:bg-zinc-700">
            <flux:text class="font-medium text-zinc-600 dark:text-zinc-400">
                {{ __('This event has ended') }}
            </flux:text>
        </div>
    @elseif($event->status === \App\Enums\EventStatus::Ongoing)
        <div class="mb-6 rounded-lg bg-green-100 p-3 text-center dark:bg-green-900/30">
            <flux:text class="font-medium text-green-700 dark:text-green-400">
                {{ __('Event is happening now!') }}
            </flux:text>
        </div>
    @endif

    {{-- Event Description --}}
    @if($event->description)
        <div class="mb-6">
            <flux:text class="text-zinc-600 dark:text-zinc-400">
                {{ $event->description }}
            </flux:text>
        </div>
    @endif

    {{-- Event Details --}}
    <div class="mb-6 space-y-4">
        {{-- Date & Time --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                <flux:icon icon="calendar" class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $event->starts_at->format('l, F j, Y') }}
                </flux:text>
                <flux:text class="text-sm text-zinc-500">
                    {{ $event->starts_at->format('g:i A') }}
                    @if($event->ends_at)
                        - {{ $event->ends_at->format('g:i A') }}
                    @endif
                </flux:text>
            </div>
        </div>

        {{-- Location --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon icon="map-pin" class="size-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $event->location }}
                </flux:text>
                @if($event->address || $event->city)
                    <flux:text class="text-sm text-zinc-500">
                        {{ collect([$event->address, $event->city])->filter()->join(', ') }}
                    </flux:text>
                @endif
            </div>
        </div>

        {{-- Price --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $event->is_paid ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-green-100 dark:bg-green-900/30' }}">
                <flux:icon icon="ticket" class="size-5 {{ $event->is_paid ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400' }}" />
            </div>
            <div>
                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $event->formatted_price }}
                </flux:text>
                @if($event->is_paid)
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Payment required') }}
                    </flux:text>
                @else
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('No payment required') }}
                    </flux:text>
                @endif
            </div>
        </div>

        {{-- Capacity --}}
        @if($event->capacity)
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                    <flux:icon icon="users" class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    @if($this->spotsRemaining !== null && $this->spotsRemaining > 0)
                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->spotsRemaining }} {{ __('spots remaining') }}
                        </flux:text>
                    @elseif($this->spotsRemaining === 0)
                        <flux:text class="font-medium text-red-600 dark:text-red-400">
                            {{ __('Fully booked') }}
                        </flux:text>
                    @endif
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Capacity: :count', ['count' => $event->capacity]) }}
                    </flux:text>
                </div>
            </div>
        @endif
    </div>

    {{-- Registration Section --}}
    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
        @if($this->canRegister)
            <a href="{{ route('events.public.register', [$branch, $event]) }}" wire:navigate>
                <flux:button variant="primary" class="w-full">
                    @if($event->is_paid)
                        {{ __('Register Now - :price', ['price' => $event->formatted_price]) }}
                    @else
                        {{ __('Register Now - Free') }}
                    @endif
                </flux:button>
            </a>
        @else
            <div class="rounded-lg bg-zinc-100 p-4 text-center dark:bg-zinc-700">
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $this->registrationMessage }}
                </flux:text>
            </div>
        @endif
    </div>

    {{-- Organizer --}}
    @if($event->organizer)
        <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">
                {{ __('Organized by :name', ['name' => $event->organizer->fullName()]) }}
            </flux:text>
        </div>
    @endif
</div>
