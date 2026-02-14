<div>
    {{-- Branch Header --}}
    <div class="mb-6 text-center">
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
            <img src="{{ $logoUrl }}" alt="{{ $branch->name }}" class="mx-auto mb-4 h-16 w-16 rounded-full object-cover" />
        @else
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-200 dark:bg-stone-700">
                <flux:icon.heart class="size-8 text-stone-500 dark:text-stone-400" />
            </div>
        @endif
        <flux:heading size="xl">{{ $branch->name }}</flux:heading>
        <flux:text class="text-stone-500 dark:text-stone-400">Online Giving</flux:text>
    </div>

    {{-- Main Card --}}
    <div class="rounded-xl border bg-white shadow-xs dark:border-stone-800 dark:bg-stone-950">
        <div class="p-6 sm:p-8">
            @if ($showThankYou)
                {{-- Thank You State --}}
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <flux:icon.check class="size-8 text-green-600 dark:text-green-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">Thank You!</flux:heading>
                    <flux:text class="mb-6 text-stone-600 dark:text-stone-400">
                        Your donation of <strong>{{ $this->currency->symbol() }}{{ number_format((float) $lastDonation?->amount, 2) }}</strong> has been received.
                        A receipt will be sent to your email.
                    </flux:text>

                    <flux:button wire:click="giveAgain" variant="primary">
                        Give Again
                    </flux:button>
                </div>
            @elseif ($errorMessage && !$this->isConfigured)
                {{-- Not Configured State --}}
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon.exclamation-triangle class="size-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <flux:heading size="lg" class="mb-2">Not Available</flux:heading>
                    <flux:text class="text-stone-600 dark:text-stone-400">
                        {{ $errorMessage }}
                    </flux:text>
                </div>
            @else
                {{-- Donation Form --}}
                <form wire:submit="initializePayment">
                    {{-- Error Message --}}
                    @if ($errorMessage)
                        <flux:callout variant="danger" icon="exclamation-circle" class="mb-6">
                            {{ $errorMessage }}
                        </flux:callout>
                    @endif

                    {{-- Amount Selection --}}
                    <div class="mb-6">
                        <flux:field>
                            <flux:label>Donation Amount ({{ $this->currency->code() }})</flux:label>

                            {{-- Preset Amounts --}}
                            <div class="mb-3 grid grid-cols-3 gap-2">
                                @foreach ($this->presetAmountsList as $preset)
                                    <button
                                        type="button"
                                        wire:click="setAmount({{ $preset }})"
                                        class="rounded-lg border px-4 py-3 text-center font-medium transition-colors {{ $amount == $preset ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-primary-900/30 dark:text-primary-300' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:hover:border-stone-600' }}"
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
                        </flux:field>
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
                        <flux:heading size="sm">Your Information</flux:heading>

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
                    <div class="mb-6 rounded-lg border border-stone-200 p-4 dark:border-stone-700">
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
                    <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="initializePayment">
                            Give {{ $this->currency->symbol() }}{{ $amount ?: '0.00' }}
                        </span>
                        <span wire:loading wire:target="initializePayment">
                            Processing...
                        </span>
                    </flux:button>

                    {{-- Security Note --}}
                    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                        <flux:icon.lock-closed class="size-4" />
                        <span>Secured by Paystack</span>
                    </div>
                </form>
            @endif
        </div>
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
