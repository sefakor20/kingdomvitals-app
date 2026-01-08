<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl">Connect Integrations</flux:heading>
        <flux:text class="mt-2">
            Set up SMS messaging and payment processing. You can configure these later in settings.
        </flux:text>
    </div>

    <div class="space-y-6">
        <!-- SMS Integration -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-lg bg-blue-100 dark:bg-blue-900/30 p-3">
                    <flux:icon name="chat-bubble-left-ellipsis" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-stone-900 dark:text-white">SMS Messaging (TextTango)</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
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

        <!-- Paystack Integration -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                    <flux:icon name="credit-card" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-stone-900 dark:text-white">Payment Processing (Paystack)</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
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

                    <flux:callout class="mt-4" variant="warning" icon="information-circle">
                        <flux:callout.heading>Test Mode</flux:callout.heading>
                        <flux:callout.text>
                            Use test keys for development. Switch to live keys when you're ready to accept real payments.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost">
            <flux:icon name="arrow-left" variant="micro" class="mr-2" />
            Back
        </flux:button>

        <div class="flex gap-3">
            <flux:button wire:click="skipIntegrationsStep" variant="ghost">
                Skip for now
            </flux:button>
            <flux:button wire:click="completeIntegrationsStep" variant="primary">
                Continue
                <flux:icon name="arrow-right" variant="micro" class="ml-2" />
            </flux:button>
        </div>
    </div>
</div>
