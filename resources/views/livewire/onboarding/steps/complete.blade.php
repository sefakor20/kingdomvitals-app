<div class="space-y-8">
    <div class="text-center">
        {{-- Success icon with pulse animation --}}
        <div class="relative mx-auto flex size-20 items-center justify-center">
            <div class="absolute inset-0 animate-ping rounded-full bg-emerald-500/20"></div>
            <div class="relative flex size-20 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-lime-accent shadow-lg shadow-emerald-500/30">
                <flux:icon name="check" class="size-10 text-white" />
            </div>
        </div>

        <span class="mt-6 inline-block label-mono text-emerald-600 dark:text-emerald-400">Setup Complete</span>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
            <span class="text-gradient-emerald">You're All Set!</span>
        </h1>
        <p class="mt-2 text-secondary">
            Your organization has been set up successfully. Here's a summary of what we've configured.
        </p>
    </div>

    {{-- Summary cards with floating animation --}}
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Organization --}}
        <div class="animate-float rounded-xl border border-black/10 bg-white/80 p-4 shadow-lg backdrop-blur-sm dark:border-white/10 dark:bg-white/5" style="animation-delay: 0s;">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-blue-500/10">
                    <flux:icon name="building-office" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="font-medium text-primary">Organization</h3>
                    <p class="text-sm text-muted">{{ $this->onboardingBranch?->name ?? 'Main Branch' }}</p>
                </div>
            </div>
        </div>

        {{-- Team --}}
        <div class="animate-float rounded-xl border border-black/10 bg-white/80 p-4 shadow-lg backdrop-blur-sm dark:border-white/10 dark:bg-white/5" style="animation-delay: 0.1s;">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-purple-500/10">
                    <flux:icon name="users" class="size-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h3 class="font-medium text-primary">Team Members</h3>
                    <p class="text-sm text-muted">
                        @if(count($teamMembers) > 0)
                            {{ count($teamMembers) }} invited
                        @else
                            None added
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Integrations --}}
        <div class="animate-float rounded-xl border border-black/10 bg-white/80 p-4 shadow-lg backdrop-blur-sm dark:border-white/10 dark:bg-white/5" style="animation-delay: 0.2s;">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-orange-500/10">
                    <flux:icon name="puzzle-piece" class="size-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <h3 class="font-medium text-primary">Integrations</h3>
                    <p class="text-sm text-muted">
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

        {{-- Services --}}
        <div class="animate-float rounded-xl border border-black/10 bg-white/80 p-4 shadow-lg backdrop-blur-sm dark:border-white/10 dark:bg-white/5" style="animation-delay: 0.3s;">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10">
                    <flux:icon name="calendar-days" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="font-medium text-primary">Worship Services</h3>
                    <p class="text-sm text-muted">{{ count($services) }} service{{ count($services) !== 1 ? 's' : '' }} added</p>
                </div>
            </div>
        </div>
    </div>

    {{-- What's next --}}
    <div class="rounded-xl border border-black/5 bg-gradient-to-br from-emerald-500/5 to-lime-500/5 p-6 dark:border-white/5">
        <h3 class="font-semibold text-primary">What's Next?</h3>
        <ul class="mt-4 space-y-3">
            <li class="flex items-center gap-3 text-sm text-secondary">
                <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon name="check" variant="micro" class="size-3 text-emerald-600 dark:text-emerald-400" />
                </span>
                Add your church members to start tracking attendance
            </li>
            <li class="flex items-center gap-3 text-sm text-secondary">
                <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon name="check" variant="micro" class="size-3 text-emerald-600 dark:text-emerald-400" />
                </span>
                Record visitor information when guests arrive
            </li>
            <li class="flex items-center gap-3 text-sm text-secondary">
                <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon name="check" variant="micro" class="size-3 text-emerald-600 dark:text-emerald-400" />
                </span>
                Set up donation categories and start tracking giving
            </li>
            <li class="flex items-center gap-3 text-sm text-secondary">
                <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/10">
                    <flux:icon name="check" variant="micro" class="size-3 text-emerald-600 dark:text-emerald-400" />
                </span>
                Explore reports and analytics on your dashboard
            </li>
        </ul>
    </div>

    <div class="flex justify-between pt-4">
        <flux:button wire:click="goBack" variant="ghost" icon="arrow-left">
            Back
        </flux:button>

        <button wire:click="completeOnboarding" class="btn-neon rounded-full px-8 py-3 text-sm font-semibold">
            Go to Dashboard
            <flux:icon name="arrow-right" variant="mini" class="ml-2 inline size-4" />
        </button>
    </div>
</div>
