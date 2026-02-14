<section class="relative isolate overflow-hidden pt-24" x-data="{ videoModalOpen: false }">
    {{-- Background gradient --}}
    <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-purple-200 to-indigo-200 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] dark:from-purple-900 dark:to-indigo-900 dark:opacity-20"></div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8 lg:py-40">
        <div class="mx-auto max-w-3xl text-center">
            {{-- Badge --}}
            <div class="mb-8 flex justify-center">
                <div class="relative flex items-center gap-2 rounded-full bg-purple-50 px-4 py-1.5 text-sm leading-6 text-purple-700 ring-1 ring-purple-200 hover:ring-purple-300 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-800 dark:hover:ring-purple-700">
                    <svg class="size-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.466 7.89l.813-2.846A.75.75 0 019 4.5z" clip-rule="evenodd" />
                    </svg>
                    AI-Powered Church Management
                </div>
            </div>

            {{-- Headline --}}
            <h1 class="text-4xl font-semibold tracking-tight text-neutral-900 sm:text-6xl lg:text-7xl dark:text-white">
                Church Management
                <span class="bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent dark:from-purple-400 dark:to-indigo-400">Powered by AI</span>
            </h1>

            {{-- Subheadline --}}
            <p class="mt-6 text-lg leading-8 text-neutral-600 sm:text-xl dark:text-neutral-400">
                The intelligent platform that helps you understand your congregation better. Manage membership, giving, and attendance while AI predicts trends and surfaces insights.
            </p>

            {{-- CTAs --}}
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-x-6">
                <a href="#pricing" class="w-full rounded-full bg-neutral-900 px-8 py-3.5 text-base font-medium text-white shadow-lg transition hover:bg-neutral-800 sm:w-auto dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                    Get Started
                </a>
                <button
                    type="button"
                    @click="videoModalOpen = true"
                    class="group flex w-full items-center justify-center gap-2 text-base font-medium text-neutral-600 transition hover:text-neutral-900 sm:w-auto dark:text-neutral-400 dark:hover:text-white"
                >
                    <span class="flex size-10 items-center justify-center rounded-full bg-purple-100 text-purple-600 transition group-hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:group-hover:bg-purple-900/50">
                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    See How It Works
                </button>
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

    {{-- Video Modal --}}
    <div
        x-show="videoModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/80 backdrop-blur-sm"
            @click="videoModalOpen = false"
        ></div>

        {{-- Modal Content --}}
        <div
            class="relative w-full max-w-4xl"
            x-show="videoModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @keydown.escape.window="videoModalOpen = false"
        >
            {{-- Close button --}}
            <button
                type="button"
                @click="videoModalOpen = false"
                class="absolute -top-12 right-0 flex items-center gap-2 text-sm font-medium text-white/80 transition hover:text-white"
            >
                Close
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Video Container --}}
            <div class="overflow-hidden rounded-2xl bg-neutral-900 shadow-2xl ring-1 ring-white/10">
                @php
                    $videoUrl = config('app.demo_video_url', '');

                    // Convert YouTube watch URLs to embed URLs
                    if ($videoUrl && str_contains($videoUrl, 'youtube.com/watch')) {
                        preg_match('/[?&]v=([^&]+)/', $videoUrl, $matches);
                        if (!empty($matches[1])) {
                            $videoUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        }
                    }

                    // Convert youtu.be short URLs to embed URLs
                    if ($videoUrl && str_contains($videoUrl, 'youtu.be/')) {
                        $videoId = basename(parse_url($videoUrl, PHP_URL_PATH));
                        $videoUrl = 'https://www.youtube.com/embed/' . $videoId;
                    }
                @endphp

                @if($videoUrl)
                    {{-- YouTube/Vimeo Embed --}}
                    <div class="aspect-video">
                        <template x-if="videoModalOpen">
                            <iframe
                                class="size-full"
                                :src="'{{ $videoUrl }}' + (('{{ $videoUrl }}').includes('?') ? '&' : '?') + 'autoplay=1'"
                                title="Kingdom Vitals Demo Video"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </template>
                    </div>
                @else
                    {{-- Placeholder when no video is configured --}}
                    <div class="flex aspect-video flex-col items-center justify-center bg-gradient-to-br from-neutral-800 to-neutral-900 p-8 text-center">
                        <div class="flex size-20 items-center justify-center rounded-full bg-purple-600/20">
                            <svg class="size-10 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-white">Demo Video Coming Soon</h3>
                        <p class="mt-2 max-w-md text-neutral-400">
                            We're preparing an in-depth walkthrough of Kingdom Vitals. In the meantime, feel free to explore the features below or contact us for a personalized demo.
                        </p>
                        <a
                            href="#contact"
                            @click="videoModalOpen = false"
                            class="mt-6 rounded-full bg-purple-600 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-purple-500"
                        >
                            Request a Demo
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
