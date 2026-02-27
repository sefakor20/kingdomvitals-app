<section class="relative isolate overflow-hidden pt-24 lg:pt-28" x-data="{ videoModalOpen: false }">
    {{-- Glow Effects --}}
    <div class="glow-sphere glow-emerald absolute right-0 top-20 size-[600px] opacity-20"></div>
    <div class="glow-sphere glow-lime absolute -left-40 bottom-0 size-[400px] opacity-15"></div>

    <div class="mx-auto max-w-7xl px-6 py-12 sm:py-16 lg:px-8 lg:py-20">
        {{-- 12-col Grid: 6/6 Split --}}
        <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">
            {{-- Left Column (6 cols) - Content --}}
            <div class="overflow-hidden lg:col-span-6">
                {{-- Status Tag --}}
                <div class="mb-8 inline-flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
                    <span class="label-mono text-emerald-600 dark:text-emerald-400">AI-Powered Platform</span>
                </div>

                {{-- Giant Headline --}}
                <h1 class="heading-giant text-primary">
                    Church<br>
                    Management<br>
                    <span class="text-gradient-emerald italic">Powered by AI</span>
                </h1>

                {{-- Subheadline --}}
                <p class="mt-8 max-w-xl text-lg leading-relaxed text-secondary sm:text-xl">
                    The intelligent platform that helps you understand your congregation better. Manage membership, giving, and attendance while AI predicts trends and surfaces insights.
                </p>

                {{-- CTAs --}}
                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="#pricing" class="btn-neon rounded-full px-8 py-4 text-base font-semibold">
                        Get Started
                    </a>
                    <button
                        type="button"
                        @click="videoModalOpen = true"
                        class="group flex items-center gap-3 text-secondary transition hover:text-primary"
                    >
                        <span class="glass-card flex size-12 items-center justify-center rounded-full">
                            <svg class="size-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="font-medium">Watch Demo</span>
                    </button>
                </div>

                {{-- Contact text --}}
                <p class="mt-6 text-sm text-muted">
                    Contact us to get started with your church.
                </p>
            </div>

            {{-- Right Column (6 cols) - Tilted Dashboard with Floating Elements --}}
            <div class="relative mt-12 lg:col-span-6 lg:mt-0">
                {{-- Floating Glass Card - Members (top left) --}}
                <div class="glass-card animate-float absolute -left-4 top-8 z-10 hidden p-4 shadow-lg lg:block">
                    <div class="label-mono text-emerald-600 dark:text-emerald-400">Members Active</div>
                    <div class="mt-1 text-2xl font-light tracking-tight text-primary">12,847</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-muted">
                        <svg class="size-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                        +23% this month
                    </div>
                </div>

                {{-- Floating Glass Card - AI Insights (bottom right) --}}
                <div class="glass-card animate-float-delayed absolute -right-4 bottom-12 z-10 hidden p-4 shadow-lg lg:block">
                    <div class="label-mono text-lime-600 dark:text-lime-accent">AI Insights</div>
                    <div class="mt-1 text-xl font-light tracking-tight text-primary">47 Actions</div>
                    <div class="mt-2 flex gap-1">
                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">Engagement</span>
                        <span class="inline-flex items-center rounded-full bg-lime-500/10 px-2 py-0.5 text-xs font-medium text-lime-600 dark:text-lime-accent">Giving</span>
                    </div>
                </div>

                {{-- Floating Glass Card - Live Status (top right) --}}
                <div class="glass-card animate-float-slow absolute -right-2 -top-4 z-10 hidden p-3 shadow-lg lg:block">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="size-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs text-muted">AI Analysis</div>
                            <div class="text-sm font-medium text-primary">Live</div>
                        </div>
                    </div>
                </div>

                {{-- Tilted Dashboard Frame --}}
                <div class="perspective-container relative">
                    {{-- Glow effect --}}
                    <div class="absolute -inset-4 rounded-[2.5rem] bg-gradient-to-r from-emerald-500/20 to-lime-500/20 blur-2xl"></div>

                    {{-- Browser frame with tilt --}}
                    <div class="dashboard-tilt glass-card-lg relative overflow-hidden p-1 shadow-2xl">
                        {{-- Browser header --}}
                        <div class="flex items-center gap-2 rounded-t-[2rem] bg-black/5 px-4 py-3 dark:bg-white/5">
                            <div class="flex gap-1.5">
                                <div class="size-3 rounded-full bg-red-500/80"></div>
                                <div class="size-3 rounded-full bg-yellow-500/80"></div>
                                <div class="size-3 rounded-full bg-emerald-500/80"></div>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="mx-auto max-w-md rounded-full bg-black/5 px-4 py-1 font-mono text-xs text-muted dark:bg-white/5">
                                    {{ config('app.url') }}/dashboard
                                </div>
                            </div>
                        </div>

                        {{-- Dashboard preview --}}
                        @if(file_exists(public_path('images/dashboard-preview.png')))
                            <img
                                src="{{ asset('images/dashboard-preview.png') }}"
                                alt="Kingdom Vitals Dashboard Preview"
                                class="aspect-[16/10] w-full rounded-b-[2rem] object-cover object-top"
                                loading="lazy"
                            >
                        @else
                            {{-- Placeholder until screenshot is added --}}
                            <div class="aspect-[16/10] rounded-b-[2rem] bg-gradient-to-br from-zinc-100 to-zinc-200 p-6 dark:from-obsidian-elevated dark:to-obsidian-surface">
                                <div class="grid h-full grid-cols-4 gap-3">
                                    <div class="col-span-1 rounded-xl bg-black/5 dark:bg-white/5"></div>
                                    <div class="col-span-3 space-y-3">
                                        <div class="h-6 w-36 rounded-lg bg-black/5 dark:bg-white/5"></div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div class="h-16 rounded-xl bg-emerald-500/10"></div>
                                            <div class="h-16 rounded-xl bg-lime-500/10"></div>
                                            <div class="h-16 rounded-xl bg-black/5 dark:bg-white/5"></div>
                                        </div>
                                        <div class="h-32 rounded-xl bg-black/5 dark:bg-white/5"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
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
            <div class="glass-card-lg overflow-hidden shadow-2xl">
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
                    <div class="flex aspect-video flex-col items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 p-8 text-center dark:from-obsidian-elevated dark:to-obsidian-surface">
                        <div class="flex size-20 items-center justify-center rounded-full bg-emerald-500/10">
                            <svg class="size-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-primary">Demo Video Coming Soon</h3>
                        <p class="mt-2 max-w-md text-secondary">
                            We're preparing an in-depth walkthrough of Kingdom Vitals. In the meantime, feel free to explore the features below or contact us for a personalized demo.
                        </p>
                        <a
                            href="#contact"
                            @click="videoModalOpen = false"
                            class="btn-neon mt-6 rounded-full px-6 py-2.5 text-sm font-semibold"
                        >
                            Request a Demo
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
