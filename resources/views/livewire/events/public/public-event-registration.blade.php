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

<div>
    {{-- Event Header --}}
    <div class="mb-8 text-center">
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
            {{ $event->starts_at->format('l, F j, Y \a\t g:i A') }}
        </p>
        <p class="text-sm text-muted">
            {{ $event->location }}
        </p>
    </div>

    {{-- Main Card --}}
    <div class="rounded-2xl border border-black/10 bg-white/95 p-6 shadow-xl backdrop-blur-sm sm:p-8 dark:border-white/10 dark:bg-obsidian-surface/95">
        @if ($showThankYou)
            {{-- Thank You State --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon.check class="size-8 text-emerald-500" />
                </div>
                <h2 class="text-xl font-semibold text-primary">{{ __('Registration Confirmed!') }}</h2>
                <p class="mt-2 text-secondary">
                    {{ __('You are registered for :event', ['event' => $event->name]) }}
                </p>

                @if ($registration?->ticket_number)
                    <div class="my-6 rounded-xl border border-black/10 bg-zinc-50 p-4 dark:border-white/10 dark:bg-obsidian-elevated">
                        <p class="label-mono text-muted">{{ __('Your Ticket') }}</p>
                        <div class="my-3 flex justify-center">
                            {!! app(\App\Services\QrCodeService::class)->generateEventTicketQrCode($registration, 150) !!}
                        </div>
                        <p class="font-mono text-lg font-semibold text-primary">
                            {{ $registration->ticket_number }}
                        </p>
                        <p class="mt-2 text-xs text-muted">
                            {{ __('Show this QR code at check-in') }}
                        </p>
                    </div>
                @endif

                <p class="mb-6 text-sm text-muted">
                    {{ __('A confirmation email has been sent to :email', ['email' => $registration?->guest_email ?? $email]) }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    @if ($registration?->ticket_number)
                        <a href="{{ URL::signedRoute('events.public.ticket.download', [$branch, $event, $registration]) }}">
                            <button class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
                                <flux:icon.arrow-down-tray class="-ml-1 mr-2 inline size-4" />
                                {{ __('Download Ticket') }}
                            </button>
                        </a>
                    @endif
                    <a href="{{ route('events.public.details', [$branch, $event]) }}" wire:navigate>
                        <button class="rounded-full border border-black/10 bg-white px-6 py-2.5 text-sm font-medium text-secondary transition hover:border-emerald-500/50 dark:border-white/10 dark:bg-obsidian-elevated">
                            {{ __('Back to Event') }}
                        </button>
                    </a>
                </div>
            </div>
        @elseif ($errorMessage && !$this->isPaystackConfigured && $event->is_paid)
            {{-- Not Configured State (Paid Events Only) --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-amber-500/10">
                    <flux:icon.exclamation-triangle class="size-8 text-amber-500" />
                </div>
                <h2 class="text-xl font-semibold text-primary">{{ __('Not Available') }}</h2>
                <p class="mt-2 text-secondary">
                    {{ $errorMessage }}
                </p>
            </div>
        @else
            {{-- Registration Form --}}
            <form wire:submit="{{ $event->is_paid ? 'initializePayment' : 'register' }}">
                {{-- Error Message --}}
                @if ($errorMessage)
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                    </div>
                @endif

                {{-- Price Display --}}
                @if ($event->is_paid)
                    <div class="mb-6 rounded-xl bg-lime-500/10 p-4 text-center">
                        <p class="label-mono text-lime-700 dark:text-lime-accent">
                            {{ __('Registration Fee') }}
                        </p>
                        <div class="mt-1 text-2xl font-bold text-lime-800 dark:text-lime-accent">
                            {{ $event->formatted_price }}
                        </div>
                    </div>
                @else
                    <div class="mb-6 rounded-xl bg-emerald-500/10 p-4 text-center">
                        <p class="font-medium text-emerald-700 dark:text-emerald-400">
                            {{ __('Free Registration') }}
                        </p>
                    </div>
                @endif

                {{-- Form Fields --}}
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>{{ __('Full Name') }}</flux:label>
                        <flux:input
                            wire:model="name"
                            type="text"
                            placeholder="{{ __('Enter your full name') }}"
                            autofocus
                        />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Email Address') }}</flux:label>
                        <flux:input
                            wire:model="email"
                            type="email"
                            placeholder="{{ __('your@email.com') }}"
                        />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Phone Number') }} <span class="text-muted">({{ __('Optional') }})</span></flux:label>
                        <flux:input
                            wire:model="phone"
                            type="tel"
                            placeholder="{{ __('0XX XXX XXXX') }}"
                        />
                        <flux:error name="phone" />
                    </flux:field>
                </div>

                {{-- Capacity Info --}}
                @if ($event->capacity && $event->available_spots !== null)
                    <div class="mt-6 rounded-xl bg-zinc-100 p-3 text-center dark:bg-obsidian-elevated">
                        <p class="text-sm text-secondary">
                            {{ __(':count spots remaining', ['count' => $event->available_spots]) }}
                        </p>
                    </div>
                @endif

                {{-- Submit Button --}}
                <div class="mt-6">
                    <button type="submit" class="btn-neon w-full rounded-full py-3.5 text-base font-semibold" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="initializePayment,register">
                            @if ($event->is_paid)
                                {{ __('Pay & Register - :price', ['price' => $event->formatted_price]) }}
                            @else
                                {{ __('Complete Registration') }}
                            @endif
                        </span>
                        <span wire:loading wire:target="initializePayment,register">
                            {{ __('Processing...') }}
                        </span>
                    </button>
                </div>

                {{-- Security Note for Paid Events --}}
                @if ($event->is_paid)
                    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-muted">
                        <flux:icon.lock-closed class="size-4" />
                        <span>{{ __('Secured by Paystack') }}</span>
                    </div>
                @endif

                {{-- Back Link --}}
                <div class="mt-6 text-center">
                    <a href="{{ route('events.public.details', [$branch, $event]) }}" class="text-sm text-muted transition hover:text-emerald-600 dark:hover:text-emerald-400" wire:navigate>
                        {{ __('Back to event details') }}
                    </a>
                </div>
            </form>
        @endif
    </div>

    {{-- Paystack Integration Script --}}
    @if ($event->is_paid)
        @script
        <script>
            $wire.on('open-paystack', (data) => {
                const config = data[0];

                const handler = PaystackPop.setup({
                    key: config.key,
                    email: config.email,
                    amount: config.amount,
                    currency: config.currency,
                    ref: config.reference,
                    metadata: config.metadata,
                    onClose: function() {
                        $wire.handlePaymentClosed();
                    },
                    callback: function(response) {
                        $wire.handlePaymentSuccess(response.reference);
                    }
                });

                handler.openIframe();
            });
        </script>
        @endscript
    @endif
</div>
