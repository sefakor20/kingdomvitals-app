<div class="space-y-8">
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
            <flux:icon name="check-circle" class="h-10 w-10 text-emerald-600 dark:text-emerald-400" />
        </div>
        <flux:heading size="xl" class="mt-4">You're All Set!</flux:heading>
        <flux:text class="mt-2">
            Your organization has been set up successfully. Here's a summary of what we've configured.
        </flux:text>
    </div>

    <!-- Summary -->
    <div class="grid gap-4 sm:grid-cols-2">
        <!-- Organization -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-4">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                    <flux:icon name="building-office" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="font-medium text-stone-900 dark:text-white">Organization</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400">{{ $this->onboardingBranch?->name ?? 'Main Branch' }}</p>
                </div>
            </div>
        </div>

        <!-- Team -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-4">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
                    <flux:icon name="users" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h3 class="font-medium text-stone-900 dark:text-white">Team Members</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        @if(count($teamMembers) > 0)
                            {{ count($teamMembers) }} invited
                        @else
                            None added
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Integrations -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-4">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-orange-100 dark:bg-orange-900/30 p-2">
                    <flux:icon name="puzzle-piece" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <h3 class="font-medium text-stone-900 dark:text-white">Integrations</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400">
                        @php
                            $integrations = [];
                            if ($smsApiKey) $integrations[] = 'SMS';
                            if ($paystackSecretKey) $integrations[] = 'Paystack';
                        @endphp
                        @if(count($integrations) > 0)
                            {{ implode(', ', $integrations) }} configured
                        @else
                            None configured
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Services -->
        <div class="rounded-lg border border-stone-200 dark:border-stone-700 p-4">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-100 dark:bg-emerald-900/30 p-2">
                    <flux:icon name="calendar-days" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="font-medium text-stone-900 dark:text-white">Worship Services</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400">{{ count($services) }} service{{ count($services) !== 1 ? 's' : '' }} added</p>
                </div>
            </div>
        </div>
    </div>

    <!-- What's next -->
    <div class="rounded-lg bg-stone-50 dark:bg-stone-900/50 p-6">
        <h3 class="font-semibold text-stone-900 dark:text-white">What's Next?</h3>
        <ul class="mt-3 space-y-2 text-sm text-stone-600 dark:text-stone-400">
            <li class="flex items-center gap-2">
                <flux:icon name="check" variant="micro" class="text-emerald-600" />
                Add your church members to start tracking attendance
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="check" variant="micro" class="text-emerald-600" />
                Record visitor information when guests arrive
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="check" variant="micro" class="text-emerald-600" />
                Set up donation categories and start tracking giving
            </li>
            <li class="flex items-center gap-2">
                <flux:icon name="check" variant="micro" class="text-emerald-600" />
                Explore reports and analytics on your dashboard
            </li>
        </ul>
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost">
            <flux:icon name="arrow-left" variant="micro" class="mr-2" />
            Back
        </flux:button>

        <flux:button wire:click="completeOnboarding" variant="primary">
            Go to Dashboard
            <flux:icon name="arrow-right" variant="micro" class="ml-2" />
        </flux:button>
    </div>
</div>
