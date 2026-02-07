<section
    id="pricing"
    class="py-24 sm:py-32 transition-all duration-700 ease-out"
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="shown = true"
    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
>
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-medium uppercase tracking-widest text-purple-600 dark:text-purple-400">Pricing</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                Plans for every church size
            </h2>
            <p class="mt-6 text-lg leading-8 text-neutral-600 dark:text-neutral-400">
                Choose the perfect plan for your ministry. All plans include a 14-day free trial.
            </p>
        </div>

        @if($plans->isNotEmpty())
            @php
                $maxSavings = $plans->max(fn($p) => $p->getAnnualSavingsPercent());
            @endphp

            <div x-data="{ annual: true }">
            {{-- Pricing toggle --}}
            <div class="mt-10 flex justify-center">
                <div class="flex items-center gap-4 rounded-full bg-neutral-100 p-1 dark:bg-neutral-800">
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="!annual ? 'bg-white text-neutral-900 shadow dark:bg-neutral-700 dark:text-white' : 'text-neutral-600 dark:text-neutral-400'"
                        @click="annual = false"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="annual ? 'bg-white text-neutral-900 shadow dark:bg-neutral-700 dark:text-white' : 'text-neutral-600 dark:text-neutral-400'"
                        @click="annual = true"
                    >
                        Annual
                        @if($maxSavings > 0)
                            <span class="ml-1 text-xs text-green-600 dark:text-green-400">Save up to {{ number_format($maxSavings, 0) }}%</span>
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
            <div class="mx-auto mt-12 grid gap-8 {{ $gridCols }}">
                @foreach($plans as $index => $plan)
                    @php
                        $isPopular = $plan->is_default || ($plans->count() >= 3 && $index === $popularIndex);
                    @endphp
                    <div class="relative rounded-2xl p-8 shadow-sm ring-1 {{ $isPopular ? 'bg-neutral-900 ring-neutral-900 shadow-xl dark:bg-white dark:ring-white' : 'bg-white ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800' }}">
                        @if($isPopular)
                            {{-- Popular badge --}}
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <div class="rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-1 text-xs font-medium text-white">
                                    Most Popular
                                </div>
                            </div>
                        @endif

                        <h3 class="text-lg font-semibold {{ $isPopular ? 'text-white dark:text-neutral-900' : 'text-neutral-900 dark:text-white' }}">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm {{ $isPopular ? 'text-neutral-400 dark:text-neutral-600' : 'text-neutral-600 dark:text-neutral-400' }}">{{ $plan->description }}</p>

                        <div class="mt-6">
                            @if($plan->price_monthly > 0)
                                <span
                                    class="text-4xl font-semibold {{ $isPopular ? 'text-white dark:text-neutral-900' : 'text-neutral-900 dark:text-white' }}"
                                    x-text="`GHS ${annual ? {{ number_format($plan->price_annual / 12, 0) }} : {{ number_format($plan->price_monthly, 0) }}}`"
                                >GHS {{ number_format($plan->price_annual / 12, 0) }}</span>
                                <span class="{{ $isPopular ? 'text-neutral-400 dark:text-neutral-600' : 'text-neutral-600 dark:text-neutral-400' }}">/month</span>
                            @else
                                <span class="text-4xl font-semibold {{ $isPopular ? 'text-white dark:text-neutral-900' : 'text-neutral-900 dark:text-white' }}">Free</span>
                            @endif
                        </div>

                        <ul class="mt-8 space-y-3">
                            {{-- Member limit --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">
                                    {{ $plan->hasUnlimitedMembers() ? 'Unlimited members' : 'Up to ' . number_format($plan->max_members) . ' members' }}
                                </span>
                            </li>

                            {{-- Branch limit --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">
                                    {{ $plan->hasUnlimitedBranches() ? 'Unlimited branches' : ($plan->max_branches == 1 ? '1 branch' : $plan->max_branches . ' branches') }}
                                </span>
                            </li>

                            {{-- Storage --}}
                            @if($plan->storage_quota_gb)
                                <li class="flex items-start gap-3">
                                    <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">
                                        {{ $plan->hasUnlimitedStorage() ? 'Unlimited storage' : $plan->storage_quota_gb . 'GB storage' }}
                                    </span>
                                </li>
                            @endif

                            {{-- SMS Credits --}}
                            @if($plan->sms_credits_monthly)
                                <li class="flex items-start gap-3">
                                    <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">
                                        {{ $plan->hasUnlimitedSms() ? 'Unlimited SMS' : number_format($plan->sms_credits_monthly) . ' SMS credits/month' }}
                                    </span>
                                </li>
                            @endif

                            {{-- Custom features from JSON --}}
                            @if($plan->features && is_array($plan->features))
                                @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            @endif

                            {{-- Support level --}}
                            <li class="flex items-start gap-3">
                                <svg class="size-5 shrink-0 {{ $isPopular ? 'text-purple-400' : 'text-green-500' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                <span class="{{ $isPopular ? 'text-neutral-300 dark:text-neutral-700' : 'text-neutral-600 dark:text-neutral-400' }}">{{ $plan->support_level->label() }}</span>
                            </li>
                        </ul>

                        <a
                            href="#contact"
                            class="mt-8 block w-full rounded-full px-4 py-3 text-center text-sm font-medium transition {{ $isPopular ? 'bg-white text-neutral-900 hover:bg-neutral-100 dark:bg-neutral-900 dark:text-white dark:hover:bg-neutral-800' : 'bg-neutral-100 text-neutral-900 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700' }}"
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
                <p class="text-neutral-600 dark:text-neutral-400">
                    Contact us for custom pricing tailored to your church's needs.
                </p>
                <a href="#contact" class="mt-6 inline-block rounded-full bg-neutral-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                    Get in Touch
                </a>
            </div>
        @endif
    </div>
</section>
