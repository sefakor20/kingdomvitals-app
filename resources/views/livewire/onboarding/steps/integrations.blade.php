<div class="space-y-6">
    <div class="text-center">
        <span class="label-mono text-emerald-600 dark:text-emerald-400">Step 3 of 5</span>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
            <span class="text-gradient-emerald">Connect Your Services</span>
        </h1>
        <p class="mt-2 text-secondary">
            Set up SMS messaging and payment processing. You can configure these later in settings.
        </p>
    </div>

    <div class="space-y-6">
        {{-- SMS Integration --}}
        <div class="rounded-xl border border-black/10 bg-white/50 p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-start gap-4">
                <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-500/10">
                    <flux:icon name="chat-bubble-left-ellipsis" class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-primary">SMS Messaging (TextTango)</h3>
                    <p class="mt-1 text-sm text-secondary">
                        Send SMS notifications, reminders, and updates to your members and visitors.
                    </p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>API Key</flux:label>
                            <flux:input
                                wire:model="smsApiKey"
                                type="password"
                                placeholder="Your TextTango API key"
                            />
                        </flux:field>

                        <flux:field>
                            <flux:label>Sender ID</flux:label>
                            <flux:input
                                wire:model="smsSenderId"
                                placeholder="e.g., YourChurch"
                                maxlength="11"
                            />
                            <flux:description>Max 11 characters, no spaces</flux:description>
                        </flux:field>
                    </div>
                </div>
            </div>
        </div>

        {{-- Paystack Integration --}}
        <div class="rounded-xl border border-black/10 bg-white/50 p-6 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-start gap-4">
                <div class="flex size-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
                    <flux:icon name="credit-card" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-primary">Payment Processing (Paystack)</h3>
                    <p class="mt-1 text-sm text-secondary">
                        Accept online donations and tithes from your members securely.
                    </p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Secret Key</flux:label>
                            <flux:input
                                wire:model="paystackSecretKey"
                                type="password"
                                placeholder="sk_test_..."
                            />
                        </flux:field>

                        <flux:field>
                            <flux:label>Public Key</flux:label>
                            <flux:input
                                wire:model="paystackPublicKey"
                                placeholder="pk_test_..."
                            />
                        </flux:field>
                    </div>

                    <div class="mt-4 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                        <div class="flex gap-2">
                            <flux:icon name="information-circle" class="size-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                            <div class="text-sm">
                                <p class="font-medium text-amber-700 dark:text-amber-300">Test Mode</p>
                                <p class="text-amber-600/80 dark:text-amber-400/80">
                                    Use test keys for development. Switch to live keys when you're ready to accept real payments.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost" icon="arrow-left">
            Back
        </flux:button>

        <div class="flex gap-3">
            <flux:button wire:click="skipIntegrationsStep" variant="ghost">
                Skip for now
            </flux:button>
            <button wire:click="completeIntegrationsStep" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
                Continue
                <flux:icon name="arrow-right" variant="mini" class="ml-2 inline size-4" />
            </button>
        </div>
    </div>
</div>
