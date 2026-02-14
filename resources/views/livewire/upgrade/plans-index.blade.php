<div class="mx-auto max-w-6xl px-4 py-8">
    <!-- Header -->
    <div class="mb-8 text-center">
        <flux:heading size="2xl">{{ __('Choose Your Plan') }}</flux:heading>
        <flux:text class="mx-auto mt-2 max-w-xl text-zinc-500">
            {{ __('Select the plan that best fits your church\'s needs. You can upgrade or change plans at any time.') }}
        </flux:text>

        <!-- Billing Toggle -->
        <div class="mt-6 flex items-center justify-center gap-3">
            <flux:button
                variant="{{ $billingCycle === 'monthly' ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setBillingCycle('monthly')"
            >
                {{ __('Monthly') }}
            </flux:button>
            <flux:button
                variant="{{ $billingCycle === 'annual' ? 'primary' : 'ghost' }}"
                size="sm"
                wire:click="setBillingCycle('annual')"
            >
                {{ __('Annual') }}
                <flux:badge variant="solid" color="green" size="sm" class="ml-1">{{ __('Save up to 20%') }}</flux:badge>
            </flux:button>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($this->plans as $plan)
            <div wire:key="plan-{{ $plan->id }}" class="{{ $this->isCurrentPlan($plan->id) ? 'pt-3' : '' }}">
                <div
                    class="relative flex h-full flex-col rounded-xl border {{ $this->isCurrentPlan($plan->id) ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-zinc-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-800"
                >
                    @if($this->isCurrentPlan($plan->id))
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <flux:badge color="indigo" class="shadow-sm">{{ __('Current Plan') }}</flux:badge>
                        </div>
                    @endif
                    <div class="flex flex-1 flex-col p-6">
                    <!-- Plan Name -->
                    <div class="mb-4">
                        <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                        @if($plan->description)
                            <flux:text class="mt-1 text-sm text-zinc-500">{{ $plan->description }}</flux:text>
                        @endif
                    </div>

                    <!-- Pricing -->
                    <div class="mb-6">
                        @if($billingCycle === 'monthly')
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                {{ Number::currency($plan->price_monthly, in: $this->currency->code()) }}
                            </span>
                            <span class="text-zinc-500">/{{ __('month') }}</span>
                        @else
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                {{ Number::currency($plan->price_annual, in: $this->currency->code()) }}
                            </span>
                            <span class="text-zinc-500">/{{ __('year') }}</span>
                            @if($plan->getAnnualSavingsPercent() > 0)
                                <div class="mt-1">
                                    <flux:badge color="green" size="sm">
                                        {{ __('Save') }} {{ number_format($plan->getAnnualSavingsPercent(), 0) }}%
                                    </flux:badge>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Limits -->
                    <div class="mb-6 flex-1 space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.users class="size-4 {{ $plan->hasUnlimitedMembers() ? 'text-green-500' : 'text-zinc-400' }}" />
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $plan->hasUnlimitedMembers() ? __('Unlimited members') : number_format($plan->max_members) . ' ' . __('members') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.building-office class="size-4 {{ $plan->hasUnlimitedBranches() ? 'text-green-500' : 'text-zinc-400' }}" />
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $plan->hasUnlimitedBranches() ? __('Unlimited branches') : number_format($plan->max_branches) . ' ' . __('branches') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.server class="size-4 text-zinc-400" />
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $plan->hasUnlimitedStorage() ? __('Unlimited storage') : $plan->storage_quota_gb . ' GB ' . __('storage') }}
                            </span>
                        </div>

                        @if($plan->sms_credits_monthly)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.chat-bubble-left class="size-4 text-zinc-400" />
                                <span class="text-zinc-700 dark:text-zinc-300">
                                    {{ $plan->hasUnlimitedSms() ? __('Unlimited SMS') : number_format($plan->sms_credits_monthly) . ' ' . __('SMS/month') }}
                                </span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.lifebuoy class="size-4 text-zinc-400" />
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $plan->support_level?->label() ?? 'Community' }} {{ __('support') }}
                            </span>
                        </div>
                    </div>

                    <!-- Features List -->
                    @if($plan->features && count($plan->features) > 0)
                        <div class="mb-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:text class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Features:') }}</flux:text>
                            <ul class="space-y-2">
                                @foreach(array_slice($plan->features, 0, 5) as $feature)
                                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        <flux:icon.check class="size-4 text-green-500" />
                                        {{ ucfirst(str_replace('_', ' ', $feature)) }}
                                    </li>
                                @endforeach
                                @if(count($plan->features) > 5)
                                    <li class="text-sm text-zinc-500">
                                        {{ __('+ :count more features', ['count' => count($plan->features) - 5]) }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <!-- Action Button -->
                    <div class="mt-auto">
                        @if($this->isCurrentPlan($plan->id))
                            <flux:button variant="ghost" class="w-full" disabled>
                                {{ __('Current Plan') }}
                            </flux:button>
                        @else
                            <flux:button
                                variant="primary"
                                class="w-full"
                                wire:click="selectPlan('{{ $plan->id }}')"
                            >
                                {{ __('Select Plan') }}
                            </flux:button>
                        @endif
                    </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.credit-card class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No plans available') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    {{ __('Subscription plans are not currently available. Please contact support.') }}
                </flux:text>
                <flux:button variant="ghost" href="{{ route('dashboard') }}" class="mt-4" wire:navigate>
                    {{ __('Return to Dashboard') }}
                </flux:button>
            </div>
        @endforelse
    </div>

    <!-- Back Link -->
    <div class="mt-8 text-center">
        <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
            {{ __('Return to Dashboard') }}
        </flux:button>
    </div>

    <!-- Help Section -->
    <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-800">
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Need help choosing a plan? Contact our support team for personalized recommendations.') }}
        </flux:text>
    </div>
</div>
