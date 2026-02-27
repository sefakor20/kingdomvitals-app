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

<div class="rounded-2xl border border-black/10 bg-white/95 p-6 shadow-xl backdrop-blur-sm sm:p-8 dark:border-white/10 dark:bg-obsidian-surface/95">
    {{-- Event Header --}}
    <div class="mb-6 text-center">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $branch->name }}" class="mx-auto mb-4 h-16 w-16 rounded-full object-cover ring-2 ring-emerald-500/20" />
        @else
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 ring-2 ring-emerald-500/20">
                <flux:icon.calendar class="size-8 text-emerald-500" />
            </div>
        @endif
        <span class="label-mono inline-block rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-600 dark:text-emerald-400">
            {{ $event->event_type->label() }}
        </span>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-primary">{{ $event->name }}</h1>
        <p class="mt-1 text-sm text-muted">
            {{ $branch->name }}
        </p>
    </div>

    {{-- Status Badge --}}
    @if($event->status === \App\Enums\EventStatus::Completed)
        <div class="mb-6 rounded-xl bg-zinc-100 p-3 text-center dark:bg-obsidian-elevated">
            <p class="font-medium text-secondary">
                {{ __('This event has ended') }}
            </p>
        </div>
    @elseif($event->status === \App\Enums\EventStatus::Ongoing)
        <div class="mb-6 rounded-xl bg-emerald-500/10 p-3 text-center">
            <p class="font-medium text-emerald-700 dark:text-emerald-400">
                {{ __('Event is happening now!') }}
            </p>
        </div>
    @endif

    {{-- Event Description --}}
    @if($event->description)
        <div class="mb-6">
            <p class="text-secondary">
                {{ $event->description }}
            </p>
        </div>
    @endif

    {{-- Event Details --}}
    <div class="mb-6 space-y-4">
        {{-- Date & Time --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
                <flux:icon icon="calendar" class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <p class="font-medium text-primary">
                    {{ $event->starts_at->format('l, F j, Y') }}
                </p>
                <p class="text-sm text-muted">
                    {{ $event->starts_at->format('g:i A') }}
                    @if($event->ends_at)
                        - {{ $event->ends_at->format('g:i A') }}
                    @endif
                </p>
            </div>
        </div>

        {{-- Location --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
                <flux:icon icon="map-pin" class="size-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <p class="font-medium text-primary">
                    {{ $event->location }}
                </p>
                @if($event->address || $event->city)
                    <p class="text-sm text-muted">
                        {{ collect([$event->address, $event->city])->filter()->join(', ') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Price --}}
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $event->is_paid ? 'bg-lime-500/10' : 'bg-emerald-500/10' }}">
                <flux:icon icon="ticket" class="size-5 {{ $event->is_paid ? 'text-lime-600 dark:text-lime-accent' : 'text-emerald-600 dark:text-emerald-400' }}" />
            </div>
            <div>
                <p class="font-medium text-primary">
                    {{ $event->formatted_price }}
                </p>
                @if($event->is_paid)
                    <p class="text-sm text-muted">
                        {{ __('Payment required') }}
                    </p>
                @else
                    <p class="text-sm text-muted">
                        {{ __('No payment required') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Capacity --}}
        @if($event->capacity)
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-obsidian-elevated">
                    <flux:icon icon="users" class="size-5 text-secondary" />
                </div>
                <div>
                    @if($this->spotsRemaining !== null && $this->spotsRemaining > 0)
                        <p class="font-medium text-primary">
                            {{ $this->spotsRemaining }} {{ __('spots remaining') }}
                        </p>
                    @elseif($this->spotsRemaining === 0)
                        <p class="font-medium text-red-600 dark:text-red-400">
                            {{ __('Fully booked') }}
                        </p>
                    @endif
                    <p class="text-sm text-muted">
                        {{ __('Capacity: :count', ['count' => $event->capacity]) }}
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- Registration Section --}}
    <div class="border-t border-black/10 pt-6 dark:border-white/10">
        @if($this->canRegister)
            <a href="{{ route('events.public.register', [$branch, $event]) }}" wire:navigate>
                <button class="btn-neon w-full rounded-full py-3.5 text-base font-semibold">
                    @if($event->is_paid)
                        {{ __('Register Now - :price', ['price' => $event->formatted_price]) }}
                    @else
                        {{ __('Register Now - Free') }}
                    @endif
                </button>
            </a>
        @else
            <div class="rounded-xl bg-zinc-100 p-4 text-center dark:bg-obsidian-elevated">
                <p class="text-secondary">
                    {{ $this->registrationMessage }}
                </p>
            </div>
        @endif
    </div>

    {{-- Organizer --}}
    @if($event->organizer)
        <div class="mt-6 border-t border-black/10 pt-6 dark:border-white/10">
            <p class="text-sm text-muted">
                {{ __('Organized by :name', ['name' => $event->organizer->fullName()]) }}
            </p>
        </div>
    @endif
</div>
