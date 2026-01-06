<div>
    <div class="mb-8">
        <flux:heading size="xl">Subscription Plans</flux:heading>
        <flux:text class="mt-2 text-slate-600 dark:text-slate-400">
            Manage subscription plans and pricing
        </flux:text>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($plans as $plan)
            <div wire:key="plan-{{ $plan->id }}" class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                        @if(!$plan->is_active)
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </div>

                    <div class="mb-4">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">${{ number_format($plan->monthly_price, 2) }}</span>
                        <span class="text-slate-500">/month</span>
                    </div>

                    @if($plan->description)
                        <flux:text class="text-slate-600 dark:text-slate-400 mb-4">
                            {{ $plan->description }}
                        </flux:text>
                    @endif

                    <div class="space-y-2 mb-4">
                        @if($plan->max_members)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.users class="size-4 text-slate-400" />
                                <span>{{ number_format($plan->max_members) }} members</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.users class="size-4 text-green-500" />
                                <span>Unlimited members</span>
                            </div>
                        @endif

                        @if($plan->max_branches)
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.building-office class="size-4 text-slate-400" />
                                <span>{{ number_format($plan->max_branches) }} branches</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-sm">
                                <flux:icon.building-office class="size-4 text-green-500" />
                                <span>Unlimited branches</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.lifebuoy class="size-4 text-slate-400" />
                            <span>{{ $plan->support_level?->label() ?? 'Community' }} support</span>
                        </div>
                    </div>

                    @if($plan->features && count($plan->features) > 0)
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mt-4">
                            <flux:text class="text-sm font-medium mb-2">Features:</flux:text>
                            <ul class="space-y-1">
                                @foreach($plan->features as $feature)
                                    <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                        <flux:icon.check class="size-4 text-green-500" />
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 p-12 text-center">
                <flux:icon.credit-card class="mx-auto size-12 text-slate-400" />
                <flux:heading size="lg" class="mt-4">No plans configured</flux:heading>
                <flux:text class="mt-2 text-slate-500">
                    Subscription plans will appear here once configured
                </flux:text>
            </div>
        @endforelse
    </div>
</div>
