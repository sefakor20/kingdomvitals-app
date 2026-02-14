<section
    id="ai-features"
    class="relative overflow-hidden py-24 sm:py-32"
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="shown = true"
>
    {{-- Background gradient --}}
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-purple-50 via-indigo-50/50 to-white dark:from-purple-950/30 dark:via-indigo-950/20 dark:to-neutral-950"></div>
    <div class="absolute inset-y-0 right-1/2 -z-10 mr-16 w-[200%] origin-bottom-left skew-x-[-30deg] bg-white shadow-xl shadow-purple-600/10 ring-1 ring-purple-50 sm:mr-28 lg:mr-0 xl:mr-16 xl:origin-center dark:bg-neutral-900 dark:ring-neutral-800"></div>

    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div
            class="mx-auto max-w-2xl text-center transition-all duration-700 ease-out"
            :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        >
            <div class="mb-4 flex items-center justify-center gap-2">
                <svg class="size-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5zM18 1.5a.75.75 0 01.728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 010 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 01-1.456 0l-.258-1.036a2.625 2.625 0 00-1.91-1.91l-1.036-.258a.75.75 0 010-1.456l1.036-.258a2.625 2.625 0 001.91-1.91l.258-1.036A.75.75 0 0118 1.5zM16.5 15a.75.75 0 01.712.513l.394 1.183c.15.447.5.799.948.948l1.183.395a.75.75 0 010 1.422l-1.183.395c-.447.15-.799.5-.948.948l-.395 1.183a.75.75 0 01-1.422 0l-.395-1.183a1.5 1.5 0 00-.948-.948l-1.183-.395a.75.75 0 010-1.422l1.183-.395c.447-.15.799-.5.948-.948l.395-1.183A.75.75 0 0116.5 15z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium uppercase tracking-widest text-amber-600 dark:text-amber-400">AI-Powered</p>
            </div>
            <h2 class="text-3xl font-semibold tracking-tight text-neutral-900 sm:text-4xl dark:text-white">
                Intelligent insights for your ministry
            </h2>
            <p class="mt-6 text-lg leading-8 text-neutral-600 dark:text-neutral-400">
                Let AI help you understand your congregation better. Predict trends, identify at-risk members, and receive actionable recommendations — all automatically.
            </p>
        </div>

        {{-- AI Features grid --}}
        <div class="mx-auto mt-16 max-w-5xl sm:mt-20">
            <div
                class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 transition-all duration-700 ease-out delay-200"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
            >
                {{-- AI Feature 1: Predictive Insights --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg shadow-purple-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">Predictive Insights</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        Identify at-risk donors and disengaging members before they leave. Get early warnings with AI-powered churn detection.
                    </p>
                </div>

                {{-- AI Feature 2: Smart Alerts --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">Smart Alerts</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        Real-time notifications when members show signs of disengagement, attendance anomalies, or critical prayer needs.
                    </p>
                </div>

                {{-- AI Feature 3: AI Recommendations --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">AI Recommendations</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        Smart suggestions for group placements, follow-up actions, and personalized engagement strategies for each member.
                    </p>
                </div>

                {{-- AI Feature 4: Financial Forecasting --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">Financial Forecasting</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        Predict giving patterns with confidence scores. See budget projections and identify potential gaps before they happen.
                    </p>
                </div>

                {{-- AI Feature 5: Attendance Analytics --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 text-white shadow-lg shadow-blue-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">Attendance Analytics</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        Forecast future attendance and detect unusual patterns. Understand seasonal trends and plan accordingly.
                    </p>
                </div>

                {{-- AI Feature 6: Prayer Intelligence --}}
                <div class="group relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-neutral-200 transition hover:shadow-lg hover:ring-purple-200 dark:bg-neutral-900 dark:ring-neutral-800 dark:hover:ring-purple-800">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/25">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-neutral-900 dark:text-white">Prayer Intelligence</h3>
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        AI-generated summaries of prayer requests by theme and urgency. Never miss a critical pastoral care opportunity.
                    </p>
                </div>
            </div>
        </div>

        {{-- Bottom CTA --}}
        <div
            class="mt-16 text-center transition-all duration-700 ease-out delay-300"
            :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        >
            <p class="text-sm text-neutral-500 dark:text-neutral-500">
                All AI features work automatically in the background — no configuration required.
            </p>
        </div>
    </div>
</section>
