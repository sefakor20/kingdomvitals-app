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
        <flux:heading size="xl">{{ $event->name }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">
            {{ $event->starts_at->format('l, F j, Y \a\t g:i A') }}
        </flux:text>
        <flux:text class="text-zinc-500">
            {{ $event->location }}
        </flux:text>
    </div>

    {{-- Main Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="p-6 sm:p-8">
            @if ($showThankYou)
                {{-- Thank You State --}}
                <div class="text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <flux:icon.check class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">{{ __('Registration Confirmed!') }}</flux:heading>
                    <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                        {{ __('You are registered for :event', ['event' => $event->name]) }}
                    </flux:text>

                    @if ($registration?->ticket_number)
                        <div class="mb-6 rounded-lg bg-zinc-100 p-4 dark:bg-zinc-700">
                            <flux:text class="text-sm text-zinc-500">{{ __('Your Ticket') }}</flux:text>
                            <div class="my-3 flex justify-center">
                                {!! app(\App\Services\QrCodeService::class)->generateEventTicketQrCode($registration, 150) !!}
                            </div>
                            <flux:heading size="md" class="font-mono">
                                {{ $registration->ticket_number }}
                            </flux:heading>
                            <flux:text class="mt-2 text-xs text-zinc-400">
                                {{ __('Show this QR code at check-in') }}
                            </flux:text>
                        </div>
                    @endif

                    <flux:text class="mb-6 text-sm text-zinc-500">
                        {{ __('A confirmation email has been sent to :email', ['email' => $registration?->guest_email ?? $email]) }}
                    </flux:text>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a href="{{ route('events.public.details', [$branch, $event]) }}" wire:navigate>
                            <flux:button variant="ghost">
                                {{ __('Back to Event') }}
                            </flux:button>
                        </a>
                    </div>
                </div>
            @elseif ($errorMessage && !$this->isPaystackConfigured && $event->is_paid)
                {{-- Not Configured State (Paid Events Only) --}}
                <div class="text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.exclamation-triangle class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">{{ __('Not Available') }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        {{ $errorMessage }}
                    </flux:text>
                </div>
            @else
                {{-- Registration Form --}}
                <form wire:submit="{{ $event->is_paid ? 'initializePayment' : 'register' }}">
                    {{-- Error Message --}}
                    @if ($errorMessage)
                        <flux:callout variant="danger" icon="exclamation-circle" class="mb-6">
                            {{ $errorMessage }}
                        </flux:callout>
                    @endif

                    {{-- Price Display --}}
                    @if ($event->is_paid)
                        <div class="mb-6 rounded-lg bg-amber-50 p-4 text-center dark:bg-amber-900/20">
                            <flux:text class="text-sm text-amber-700 dark:text-amber-400">
                                {{ __('Registration Fee') }}
                            </flux:text>
                            <div class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-300">
                                {{ $event->formatted_price }}
                            </div>
                        </div>
                    @else
                        <div class="mb-6 rounded-lg bg-green-50 p-4 text-center dark:bg-green-900/20">
                            <flux:text class="font-medium text-green-700 dark:text-green-400">
                                {{ __('Free Registration') }}
                            </flux:text>
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
                            <flux:label>{{ __('Phone Number') }} <span class="text-zinc-400">({{ __('Optional') }})</span></flux:label>
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
                        <div class="mt-6 rounded-lg bg-zinc-100 p-3 text-center dark:bg-zinc-700">
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __(':count spots remaining', ['count' => $event->available_spots]) }}
                            </flux:text>
                        </div>
                    @endif

                    {{-- Submit Button --}}
                    <div class="mt-6">
                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
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
                        </flux:button>
                    </div>

                    {{-- Security Note for Paid Events --}}
                    @if ($event->is_paid)
                        <div class="mt-4 flex items-center justify-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <flux:icon.lock-closed class="size-4" />
                            <span>{{ __('Secured by Paystack') }}</span>
                        </div>
                    @endif

                    {{-- Back Link --}}
                    <div class="mt-6 text-center">
                        <a href="{{ route('events.public.details', [$branch, $event]) }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300" wire:navigate>
                            {{ __('Back to event details') }}
                        </a>
                    </div>
                </form>
            @endif
        </div>
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
