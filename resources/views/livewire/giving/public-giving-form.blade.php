<div>
    {{-- Branch Header --}}
    <div class="mb-8 text-center">
        @php
            $logoUrl = null;

            // 1. Check for tenant logo first
            if (function_exists('tenant') && tenant()) {
                $logoUrl = tenant()->getLogoUrl('medium');
            }

            // 2. Fall back to platform logo
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

        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $branch->name }}" class="mx-auto mb-4 h-16 w-16 rounded-full object-cover ring-2 ring-emerald-500/20" />
        @else
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 ring-2 ring-emerald-500/20">
                <flux:icon.heart class="size-8 text-emerald-500" />
            </div>
        @endif
        <h1 class="text-2xl font-semibold tracking-tight text-primary">{{ $branch->name }}</h1>
        <p class="label-mono mt-1 text-emerald-600 dark:text-emerald-400">Online Giving</p>
        <p class="mx-auto mt-3 max-w-sm text-sm text-secondary">
            {{ $branch->getSetting('giving_tagline', 'Your generosity makes a difference in our community') }}
        </p>
    </div>

    {{-- Main Card --}}
    <div class="rounded-2xl border border-black/10 bg-white/95 p-6 shadow-xl backdrop-blur-sm sm:p-8 dark:border-white/10 dark:bg-obsidian-surface/95">
        @if ($showThankYou)
            {{-- Thank You State --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon.check class="size-8 text-emerald-500" />
                </div>
                <h2 class="text-xl font-semibold text-primary">Thank You!</h2>
                <p class="mt-2 text-secondary">
                    Your donation of <strong class="text-emerald-600 dark:text-emerald-400">{{ $this->currency->symbol() }}{{ number_format((float) $lastDonation?->amount, 2) }}</strong> has been received.
                    A receipt will be sent to your email.
                </p>

                <button wire:click="giveAgain" class="btn-neon mt-6 rounded-full px-6 py-2.5 text-sm font-semibold">
                    Give Again
                </button>
            </div>
        @elseif ($errorMessage && !$this->isConfigured)
            {{-- Not Configured State --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10">
                    <flux:icon.exclamation-triangle class="size-8 text-amber-500" />
                </div>
                <h2 class="text-xl font-semibold text-primary">Not Available</h2>
                <p class="mt-2 text-secondary">
                    {{ $errorMessage }}
                </p>
            </div>
        @else
            {{-- Donation Form --}}
            <form wire:submit="initializePayment">
                {{-- Error Message --}}
                @if ($errorMessage)
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                    </div>
                @endif

                {{-- Amount Selection --}}
                <div class="mb-6">
                    <label class="label-mono mb-3 block text-emerald-600 dark:text-emerald-400">
                        Donation Amount ({{ $this->currency->code() }})
                    </label>

                    {{-- Preset Amounts --}}
                    <div class="mb-3 grid grid-cols-3 gap-2">
                        @foreach ($this->presetAmountsList as $preset)
                            <button
                                type="button"
                                wire:click="setAmount({{ $preset }})"
                                class="rounded-xl border px-4 py-3 text-center font-medium transition-all {{ $amount == $preset ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-black/10 bg-white hover:border-emerald-500/50 dark:border-white/10 dark:bg-obsidian-elevated dark:hover:border-emerald-500/50' }}"
                            >
                                {{ $preset }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Custom Amount Input --}}
                    <flux:input
                        wire:model="amount"
                        type="number"
                        step="0.01"
                        min="1"
                        placeholder="Or enter custom amount"
                    />
                    <flux:error name="amount" />
                </div>

                {{-- Donation Type --}}
                <div class="mb-6">
                    <flux:select wire:model="donationType" label="Donation Type">
                        @foreach ($this->donationTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ ucwords(str_replace('_', ' ', $type->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Donor Information --}}
                <div class="mb-6 space-y-4">
                    <h3 class="label-mono text-emerald-600 dark:text-emerald-400">Your Information</h3>

                    <flux:field>
                        <flux:input
                            wire:model="donorName"
                            label="Full Name"
                            placeholder="Enter your name"
                            :disabled="$isAnonymous"
                        />
                        <flux:error name="donorName" />
                    </flux:field>

                    <flux:field>
                        <flux:input
                            wire:model="donorEmail"
                            type="email"
                            label="Email Address"
                            placeholder="your@email.com"
                            description="Required for receipt"
                        />
                        <flux:error name="donorEmail" />
                    </flux:field>

                    <flux:field>
                        <flux:input
                            wire:model="donorPhone"
                            type="tel"
                            label="Phone Number (Optional)"
                            placeholder="0XX XXX XXXX"
                        />
                        <flux:error name="donorPhone" />
                    </flux:field>

                    <flux:checkbox
                        wire:model.live="isAnonymous"
                        label="Make this donation anonymous"
                    />
                </div>

                {{-- Recurring Option --}}
                <div class="mb-6 rounded-xl border border-black/10 p-4 dark:border-white/10">
                    <flux:checkbox
                        wire:model.live="isRecurring"
                        label="Make this a recurring donation"
                    />

                    @if ($isRecurring)
                        <div class="mt-4">
                            <flux:select wire:model="recurringInterval" label="Frequency">
                                <flux:select.option value="weekly">Weekly</flux:select.option>
                                <flux:select.option value="monthly">Monthly</flux:select.option>
                                <flux:select.option value="yearly">Yearly</flux:select.option>
                            </flux:select>
                        </div>
                    @endif
                </div>

                {{-- Notes --}}
                <div class="mb-6">
                    <flux:textarea
                        wire:model="notes"
                        label="Notes (Optional)"
                        placeholder="Any special instructions or dedication"
                        rows="2"
                    />
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn-neon w-full rounded-full py-3.5 text-base font-semibold" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="initializePayment">
                        Give {{ $this->currency->symbol() }}{{ $amount ?: '0.00' }}
                    </span>
                    <span wire:loading wire:target="initializePayment">
                        Processing...
                    </span>
                </button>

                {{-- Security Note --}}
                <div class="mt-4 flex items-center justify-center gap-2 text-xs text-muted">
                    <flux:icon.lock-closed class="size-4" />
                    <span>Secured by Paystack</span>
                </div>
            </form>
        @endif
    </div>

    {{-- Paystack Integration Script --}}
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
</div>
