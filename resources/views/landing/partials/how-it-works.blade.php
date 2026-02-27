<section
    id="how-it-works"
    class="relative rounded-t-[3rem] bg-zinc-200 py-24 transition-all duration-700 ease-out sm:py-32 dark:bg-obsidian-elevated"
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="shown = true"
    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
>
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center">
            <p class="label-mono text-emerald-600 dark:text-emerald-400">How It Works</p>
            <h2 class="mt-4 text-4xl font-light tracking-tighter text-zinc-900 sm:text-5xl dark:text-white">
                Get started in minutes
            </h2>
            <p class="mt-6 text-lg leading-8 text-zinc-600 dark:text-zinc-400">
                Setting up your church management system has never been easier. Follow these simple steps to get your ministry organized.
            </p>
        </div>

        {{-- Steps --}}
        <div class="mx-auto mt-16 max-w-5xl sm:mt-20">
            <div class="grid gap-8 md:grid-cols-3">
                {{-- Step 1 --}}
                <div class="relative text-center">
                    {{-- Step number --}}
                    <div class="relative z-10 mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500 font-mono text-2xl font-semibold text-white shadow-lg shadow-emerald-500/25">
                        01
                    </div>
                    {{-- Connector line (hidden on mobile) --}}
                    <div class="absolute left-1/2 top-8 hidden h-0.5 w-full bg-gradient-to-r from-emerald-400 to-lime-400 md:block"></div>

                    <h3 class="mt-6 text-xl font-semibold text-zinc-900 dark:text-white">Sign Up</h3>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                        Create your account and set up your church organization in just a few clicks.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center">
                    <div class="relative z-10 mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500 font-mono text-2xl font-semibold text-white shadow-lg shadow-emerald-500/25">
                        02
                    </div>
                    <div class="absolute left-1/2 top-8 hidden h-0.5 w-full bg-gradient-to-r from-lime-400 to-emerald-400 md:block"></div>

                    <h3 class="mt-6 text-xl font-semibold text-zinc-900 dark:text-white">Add Members</h3>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                        Import your existing member data or add members individually with our easy-to-use interface.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative text-center">
                    <div class="relative z-10 mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500 font-mono text-2xl font-semibold text-white shadow-lg shadow-emerald-500/25">
                        03
                    </div>

                    <h3 class="mt-6 text-xl font-semibold text-zinc-900 dark:text-white">Start Managing</h3>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                        Access real-time dashboards, track attendance, receive donations, and grow your ministry.
                    </p>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="mt-16 text-center">
            <a href="#pricing" class="btn-neon inline-flex items-center gap-2 rounded-full px-8 py-3.5 text-base font-semibold">
                Get Started
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </div>
</section>
