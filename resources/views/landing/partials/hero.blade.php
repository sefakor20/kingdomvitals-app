<section class="relative isolate overflow-hidden pt-24">
    {{-- Background gradient --}}
    <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-purple-200 to-indigo-200 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] dark:from-purple-900 dark:to-indigo-900 dark:opacity-20"></div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8 lg:py-40">
        <div class="mx-auto max-w-3xl text-center">
            {{-- Badge --}}
            <div class="mb-8 flex justify-center">
                <div class="relative rounded-full px-4 py-1.5 text-sm leading-6 text-neutral-600 ring-1 ring-neutral-900/10 hover:ring-neutral-900/20 dark:text-neutral-400 dark:ring-white/10 dark:hover:ring-white/20">
                    Trusted by churches worldwide
                </div>
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl font-semibold tracking-tight text-neutral-900 sm:text-6xl lg:text-7xl dark:text-white">
                Church Management
                <span class="bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent dark:from-purple-400 dark:to-indigo-400">Made Simple</span>
            </h1>

            {{-- Subheadline --}}
            <p class="mt-6 text-lg leading-8 text-neutral-600 sm:text-xl dark:text-neutral-400">
                The all-in-one platform to manage your membership, giving, attendance, volunteers, and more — so you can focus on ministry.
            </p>

            {{-- CTAs --}}
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-x-6">
                <a href="#pricing" class="w-full rounded-full bg-neutral-900 px-8 py-3.5 text-base font-medium text-white shadow-lg transition hover:bg-neutral-800 sm:w-auto dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                    Get Started
                </a>
                <a href="#features" class="group flex w-full items-center justify-center gap-2 text-base font-medium text-neutral-600 transition hover:text-neutral-900 sm:w-auto dark:text-neutral-400 dark:hover:text-white">
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    See How It Works
                </a>
            </div>

            {{-- Contact text --}}
            <p class="mt-6 text-sm text-neutral-500 dark:text-neutral-500">
                Contact us to get started with your church.
            </p>
        </div>

        {{-- Dashboard Preview --}}
        <div class="mt-16 sm:mt-24">
            <div class="relative mx-auto max-w-5xl">
                {{-- Glow effect --}}
                <div class="absolute -inset-4 rounded-2xl bg-gradient-to-r from-purple-500/20 to-indigo-500/20 blur-2xl"></div>

                {{-- Browser frame --}}
                <div class="relative overflow-hidden rounded-2xl bg-neutral-900 shadow-2xl ring-1 ring-neutral-900/10 dark:ring-white/10">
                    {{-- Browser header --}}
                    <div class="flex items-center gap-2 border-b border-neutral-800 bg-neutral-800 px-4 py-3">
                        <div class="flex gap-1.5">
                            <div class="size-3 rounded-full bg-red-500"></div>
                            <div class="size-3 rounded-full bg-yellow-500"></div>
                            <div class="size-3 rounded-full bg-green-500"></div>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="mx-auto max-w-md rounded-md bg-neutral-700 px-4 py-1 text-xs text-neutral-400">
                                {{ config('app.url') }}/dashboard
                            </div>
                        </div>
                    </div>

                    {{-- Dashboard preview --}}
                    @if(file_exists(public_path('images/dashboard-preview.png')))
                        <img
                            src="{{ asset('images/dashboard-preview.png') }}"
                            alt="Kingdom Vitals Dashboard Preview"
                            class="aspect-[16/9] w-full object-cover object-top"
                            loading="lazy"
                        >
                    @else
                        {{-- Placeholder until screenshot is added --}}
                        <div class="aspect-[16/9] bg-gradient-to-br from-neutral-800 to-neutral-900 p-8">
                            <div class="grid h-full grid-cols-4 gap-4">
                                <div class="col-span-1 rounded-lg bg-neutral-800/50"></div>
                                <div class="col-span-3 space-y-4">
                                    <div class="h-8 w-48 rounded bg-neutral-700/50"></div>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="h-24 rounded-lg bg-neutral-700/30"></div>
                                        <div class="h-24 rounded-lg bg-neutral-700/30"></div>
                                        <div class="h-24 rounded-lg bg-neutral-700/30"></div>
                                    </div>
                                    <div class="h-48 rounded-lg bg-neutral-700/20"></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom gradient --}}
    <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
        <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-indigo-200 to-purple-200 opacity-30 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem] dark:from-indigo-900 dark:to-purple-900 dark:opacity-20"></div>
    </div>
</section>
