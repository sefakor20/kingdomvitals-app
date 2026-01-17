<div class="mx-auto max-w-lg px-4 py-8">
    <!-- Back Link -->
    <div class="mb-6">
        <flux:button variant="ghost" size="sm" icon="arrow-left" href="{{ route('plans.index') }}" wire:navigate>
            {{ __('Back to Plans') }}
        </flux:button>
    </div>

    @if($showSuccess)
        <!-- Success State -->
        <div class="rounded-xl border border-green-200 bg-white p-8 text-center dark:border-green-700 dark:bg-zinc-800">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <flux:icon.check class="size-8 text-green-600 dark:text-green-400" />
            </div>
            <flux:heading size="xl" class="mb-2">{{ __('Upgrade Successful!') }}</flux:heading>
            <flux:text class="mb-6 text-zinc-600 dark:text-zinc-400">
                {{ __('Your subscription has been upgraded to') }} <strong>{{ $plan->name }}</strong>.
                {{ __('You now have access to all the features included in your new plan.') }}
            </flux:text>
            <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                {{ __('Go to Dashboard') }}
            </flux:button>
        </div>
    @else
        <!-- Checkout Form -->
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="p-6">
                <!-- Plan Summary -->
                <div class="mb-6 border-b border-zinc-200 pb-6 dark:border-zinc-700">
                    <flux:heading size="lg" class="mb-2">{{ $plan->name }}</flux:heading>
                    @if($plan->description)
                        <flux:text class="text-sm text-zinc-500">{{ $plan->description }}</flux:text>
                    @endif
                </div>

                <!-- Error Message -->
                @if($errorMessage)
                    <flux:callout variant="danger" icon="exclamation-circle" class="mb-6">
                        {{ $errorMessage }}
                    </flux:callout>
                @endif

                <!-- Billing Cycle Selection -->
                <div class="mb-6">
                    <flux:heading size="sm" class="mb-3">{{ __('Billing Cycle') }}</flux:heading>
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors {{ $billingCycle === 'monthly' ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <input
                                type="radio"
                                wire:model.live="billingCycle"
                                value="monthly"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ __('Monthly') }}</div>
                                <div class="text-sm text-zinc-500">{{ Number::currency($plan->price_monthly, in: 'GHS') }}/{{ __('month') }}</div>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors {{ $billingCycle === 'annual' ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-900/20' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <input
                                type="radio"
                                wire:model.live="billingCycle"
                                value="annual"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                            />
                            <div class="flex-1">
                                <div class="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                    {{ __('Annual') }}
                                    @if($this->annualSavings > 0)
                                        <flux:badge color="green" size="sm">{{ __('Save') }} {{ number_format($this->annualSavings, 0) }}%</flux:badge>
                                    @endif
                                </div>
                                <div class="text-sm text-zinc-500">{{ Number::currency($plan->price_annual, in: 'GHS') }}/{{ __('year') }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Price Summary -->
                <div class="mb-6 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Total due today') }}</span>
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ Number::currency($this->selectedPrice, in: 'GHS') }}
                        </span>
                    </div>
                    <div class="mt-1 text-sm text-zinc-500">
                        {{ $billingCycle === 'annual' ? __('Billed annually') : __('Billed monthly') }}
                    </div>
                </div>

                <!-- Pay Button -->
                @if($this->paystackConfigured)
                    <flux:button
                        variant="primary"
                        class="w-full"
                        wire:click="initiatePayment"
                        wire:loading.attr="disabled"
                        :disabled="$isProcessing"
                    >
                        <span wire:loading.remove wire:target="initiatePayment" class="inline-flex items-center">
                            <flux:icon.credit-card class="-ml-1 mr-2 size-5" />
                            {{ __('Pay with Paystack') }}
                        </span>
                        <span wire:loading wire:target="initiatePayment" class="inline-flex items-center">
                            <flux:icon.arrow-path class="-ml-1 mr-2 size-5 animate-spin" />
                            {{ __('Processing...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        {{ __('Payment system is not configured. Please contact support.') }}
                    </flux:callout>
                @endif

                <!-- Security Note -->
                <div class="mt-4 flex items-center justify-center gap-2 text-xs text-zinc-500">
                    <flux:icon.lock-closed class="size-4" />
                    <span>{{ __('Secured by Paystack') }}</span>
                </div>

                <!-- Plan Features -->
                <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-3">{{ __('What\'s included') }}</flux:heading>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:icon.check class="size-4 text-green-500" />
                            {{ $plan->hasUnlimitedMembers() ? __('Unlimited members') : number_format($plan->max_members) . ' ' . __('members') }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:icon.check class="size-4 text-green-500" />
                            {{ $plan->hasUnlimitedBranches() ? __('Unlimited branches') : number_format($plan->max_branches) . ' ' . __('branches') }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:icon.check class="size-4 text-green-500" />
                            {{ $plan->hasUnlimitedStorage() ? __('Unlimited storage') : $plan->storage_quota_gb . ' GB ' . __('storage') }}
                        </li>
                        @if($plan->sms_credits_monthly)
                            <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <flux:icon.check class="size-4 text-green-500" />
                                {{ $plan->hasUnlimitedSms() ? __('Unlimited SMS') : number_format($plan->sms_credits_monthly) . ' ' . __('SMS/month') }}
                            </li>
                        @endif
                        <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:icon.check class="size-4 text-green-500" />
                            {{ $plan->support_level?->label() ?? 'Community' }} {{ __('support') }}
                        </li>
                        @if($plan->features && count($plan->features) > 0)
                            @foreach($plan->features as $feature)
                                <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <flux:icon.check class="size-4 text-green-500" />
                                    {{ ucfirst(str_replace('_', ' ', $feature)) }}
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Load Paystack JS -->
    <script src="https://js.paystack.co/v2/inline.js"></script>

    <!-- Paystack Integration Script -->
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

        $wire.on('upgrade-complete', () => {
            // Optional: Add any additional handling after successful upgrade
        });
    </script>
    @endscript
</div>
