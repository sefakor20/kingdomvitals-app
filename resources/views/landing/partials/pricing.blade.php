<section id="pricing" class="py-24 sm:py-32">
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

        {{-- Pricing toggle --}}
        <div class="mt-10 flex justify-center" x-data="{ annual: true }">
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
                    <span class="ml-1 text-xs text-green-600 dark:text-green-400">Save 20%</span>
                </button>
            </div>
        </div>

        {{-- Pricing cards --}}
        <div class="mx-auto mt-12 grid max-w-5xl gap-8 lg:grid-cols-3" x-data="{ annual: true }">
            {{-- Starter Plan --}}
            <div class="relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Starter</h3>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">Perfect for small churches just getting started.</p>

                <div class="mt-6">
                    <span class="text-4xl font-semibold text-neutral-900 dark:text-white" x-text="annual ? '$23' : '$29'">$23</span>
                    <span class="text-neutral-600 dark:text-neutral-400">/month</span>
                </div>

                <ul class="mt-8 space-y-3">
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Up to 100 members</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">1 branch</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Membership management</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Attendance tracking</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Online giving</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Community support</span>
                    </li>
                </ul>

                <a href="#contact" class="mt-8 block w-full rounded-full bg-neutral-100 px-4 py-3 text-center text-sm font-medium text-neutral-900 transition hover:bg-neutral-200 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700">
                    Contact Us
                </a>
            </div>

            {{-- Growth Plan (Popular) --}}
            <div class="relative rounded-2xl bg-neutral-900 p-8 shadow-xl ring-1 ring-neutral-900 dark:bg-white dark:ring-white">
                {{-- Popular badge --}}
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <div class="rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-1 text-xs font-medium text-white">
                        Most Popular
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-white dark:text-neutral-900">Growth</h3>
                <p class="mt-2 text-sm text-neutral-400 dark:text-neutral-600">For growing churches with expanding ministries.</p>

                <div class="mt-6">
                    <span class="text-4xl font-semibold text-white dark:text-neutral-900" x-text="annual ? '$63' : '$79'">$63</span>
                    <span class="text-neutral-400 dark:text-neutral-600">/month</span>
                </div>

                <ul class="mt-8 space-y-3">
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">Up to 500 members</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">Up to 3 branches</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">Everything in Starter</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">Visitor follow-up</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">SMS messaging</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-300 dark:text-neutral-700">Email support</span>
                    </li>
                </ul>

                <a href="#contact" class="mt-8 block w-full rounded-full bg-white px-4 py-3 text-center text-sm font-medium text-neutral-900 transition hover:bg-neutral-100 dark:bg-neutral-900 dark:text-white dark:hover:bg-neutral-800">
                    Contact Us
                </a>
            </div>

            {{-- Enterprise Plan --}}
            <div class="relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Enterprise</h3>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">For large churches and multi-site networks.</p>

                <div class="mt-6">
                    <span class="text-4xl font-semibold text-neutral-900 dark:text-white" x-text="annual ? '$159' : '$199'">$159</span>
                    <span class="text-neutral-600 dark:text-neutral-400">/month</span>
                </div>

                <ul class="mt-8 space-y-3">
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Unlimited members</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Unlimited branches</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Everything in Growth</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Advanced analytics</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Custom integrations</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-neutral-600 dark:text-neutral-400">Priority support</span>
                    </li>
                </ul>

                <a href="#contact" class="mt-8 block w-full rounded-full bg-neutral-100 px-4 py-3 text-center text-sm font-medium text-neutral-900 transition hover:bg-neutral-200 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700">
                    Contact Sales
                </a>
            </div>
        </div>
    </div>
</section>
