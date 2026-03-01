<section id="pricing" class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center scroll-reveal">
            <p class="label-mono text-emerald-600 dark:text-emerald-400">Pricing</p>
            <h2 class="mt-4 text-4xl font-light tracking-tighter text-primary sm:text-5xl">
                Plans for every church size
            </h2>
            <p class="mt-6 text-lg leading-8 text-secondary">
                Choose the perfect plan for your ministry. All plans include a 14-day free trial.
            </p>
        </div>

        @if($plans->isNotEmpty())
            @php
                $maxSavings = $plans->max(fn($p) => $p->getAnnualSavingsPercent());
                $baseCurrency = \App\Enums\Currency::fromString(\App\Models\SystemSetting::get('base_currency', 'GHS'));
            @endphp

            <div x-data="{ annual: true }">
            {{-- Billing cycle toggle --}}
            <div class="mt-10 flex justify-center">
                <div class="glass-card flex items-center gap-1 p-1">
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="!annual ? 'bg-emerald-500 text-white shadow' : 'text-secondary'"
                        @click="annual = false"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="annual ? 'bg-emerald-500 text-white shadow' : 'text-secondary'"
                        @click="annual = true"
                    >
                        Annual
                        @if($maxSavings > 0)
                            <span class="ml-1 text-xs text-lime-300">Save up to {{ number_format($maxSavings, 0) }}%</span>
                        @endif
                    </button>
                </div>
            </div>


            {{-- Pricing cards --}}
            @php
                $popularIndex = floor($plans->count() / 2);
                $gridCols = match($plans->count()) {
                    1 => 'max-w-md mx-auto',
                    2 => 'max-w-3xl lg:grid-cols-2',
                    default => 'max-w-5xl lg:grid-cols-3',
                };
            @endphp
            <div class="mx-auto mt-12 grid gap-6 {{ $gridCols }}">
                @foreach($plans as $index => $plan)
                    @php
                        $isPopular = $plan->is_default || ($plans->count() >= 3 && $index === $popularIndex);
                    @endphp
                    <div class="relative {{ $isPopular ? 'glass-card border-emerald-500/50 bg-emerald-500/5 dark:bg-emerald-500/10' : 'glass-card' }} card-lift p-8 hover:border-emerald-500/40 scroll-reveal reveal-delay-{{ $index + 1 }}">
                        @if($isPopular)
                            {{-- Popular badge --}}
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <div class="rounded-full bg-emerald-500 px-4 py-1 text-xs font-semibold text-white shadow-lg shadow-emerald-500/25">
                                    Most Popular
                                </div>
                            </div>
                        @endif

                        <h3 class="text-lg font-semibold text-primary">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm text-secondary">{{ $plan->description }}</p>

                        <div class="mt-6">
                            @if($plan->price_monthly > 0)
                                <span
                                    class="text-4xl font-light tracking-tight text-primary"
                                    x-text="`GHS ${annual ? '{{ number_format($plan->price_annual / 12, 0) }}' : '{{ number_format($plan->price_monthly, 0) }}'}`"
                                >GHS {{ number_format($plan->price_annual / 12, 0) }}</span>
                                <span class="text-secondary">/month</span>
                            @else
                                <span class="text-4xl font-light tracking-tight text-primary">Free</span>
                            @endif
                        </div>

                        <ul class="mt-8 space-y-3">
                            {{-- Member limit --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-secondary">
                                    {{ $plan->hasUnlimitedMembers() ? 'Unlimited members' : 'Up to ' . number_format($plan->max_members) . ' members' }}
                                </span>
                            </li>

                            {{-- Branch limit --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-secondary">
                                    {{ $plan->hasUnlimitedBranches() ? 'Unlimited branches' : ($plan->max_branches == 1 ? '1 branch' : $plan->max_branches . ' branches') }}
                                </span>
                            </li>

                            {{-- Storage --}}
                            @if($plan->storage_quota_gb)
                                <li class="flex items-start gap-3">
                                    <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-secondary">
                                        {{ $plan->hasUnlimitedStorage() ? 'Unlimited storage' : $plan->storage_quota_gb . 'GB storage' }}
                                    </span>
                                </li>
                            @endif

                            {{-- SMS Credits --}}
                            @if($plan->sms_credits_monthly)
                                <li class="flex items-start gap-3">
                                    <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-secondary">
                                        {{ $plan->hasUnlimitedSms() ? 'Unlimited SMS' : number_format($plan->sms_credits_monthly) . ' SMS credits/month' }}
                                    </span>
                                </li>
                            @endif

                            {{-- Custom features from JSON --}}
                            @if($plan->features && is_array($plan->features))
                                @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-secondary">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            @endif

                            {{-- Support level --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-secondary">{{ $plan->support_level->label() }}</span>
                            </li>
                        </ul>

                        <a
                            href="#contact"
                            class="btn-neon mt-8 block w-full rounded-full px-4 py-3 text-center text-sm font-semibold"
                        >
                            Contact Us
                        </a>
                    </div>
                @endforeach
            </div>
            </div>
        @else
            {{-- No plans available --}}
            <div class="mx-auto mt-12 max-w-md text-center">
                <p class="text-secondary">
                    Contact us for custom pricing tailored to your church's needs.
                </p>
                <a href="#contact" class="btn-neon mt-6 inline-block rounded-full px-6 py-3 text-sm font-semibold">
                    Get in Touch
                </a>
            </div>
        @endif
    </div>
</section>
