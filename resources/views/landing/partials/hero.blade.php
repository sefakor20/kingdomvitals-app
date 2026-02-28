<section class="relative isolate overflow-hidden pt-10 lg:pt-20" x-data="{ videoModalOpen: false }">
    {{-- Glow Effects --}}
    <div class="glow-sphere glow-emerald absolute right-0 top-20 size-[600px] opacity-20"></div>
    <div class="glow-sphere glow-lime absolute -left-40 bottom-0 size-[400px] opacity-15"></div>

    <div class="mx-auto max-w-7xl px-6 py-12 sm:py-16 lg:px-8 lg:py-20">
        {{-- 12-col Grid: 6/6 Split --}}
        <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">
            {{-- Left Column (6 cols) - Content --}}
            <div class="lg:col-span-6">
                {{-- Status Tag --}}
                <div class="mb-8 inline-flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
                    <span class="label-mono text-emerald-600 dark:text-emerald-400">AI-Powered Platform</span>
                </div>

                {{-- Giant Headline --}}
                <h1 class="heading-giant text-primary">
                    Church<br>
                    Management<br>
                    <span class="text-gradient-emerald italic pr-3">Powered by AI</span>
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
                <div class="animate-float absolute -left-4 top-8 z-10 hidden rounded-2xl border border-black/10 bg-white/95 p-4 shadow-xl backdrop-blur-sm lg:block dark:border-white/10 dark:bg-obsidian-surface/95">
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
                <div class="animate-float-delayed absolute -right-4 bottom-12 z-10 hidden rounded-2xl border border-black/10 bg-white/95 p-4 shadow-xl backdrop-blur-sm lg:block dark:border-white/10 dark:bg-obsidian-surface/95">
                    <div class="label-mono text-lime-600 dark:text-lime-accent">AI Insights</div>
                    <div class="mt-1 text-xl font-light tracking-tight text-primary">47 Actions</div>
                    <div class="mt-2 flex gap-1">
                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">Engagement</span>
                        <span class="inline-flex items-center rounded-full bg-lime-500/10 px-2 py-0.5 text-xs font-medium text-lime-600 dark:text-lime-accent">Giving</span>
                    </div>
                </div>

                {{-- Floating Glass Card - Live Status (top right) --}}
                <div class="animate-float-slow absolute -right-2 -top-4 z-10 hidden rounded-2xl border border-black/10 bg-white/95 p-3 shadow-xl backdrop-blur-sm lg:block dark:border-white/10 dark:bg-obsidian-surface/95">
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
                            {{-- SVG Dashboard Illustration --}}
                            <svg viewBox="0 0 800 500" class="aspect-[16/10] w-full rounded-b-[2rem]" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    {{-- Gradients --}}
                                    <linearGradient id="bgGradientLight" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#f4f4f5" />
                                        <stop offset="100%" style="stop-color:#e4e4e7" />
                                    </linearGradient>
                                    <linearGradient id="bgGradientDark" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#141414" />
                                        <stop offset="100%" style="stop-color:#0c0c0c" />
                                    </linearGradient>
                                    <linearGradient id="emeraldGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#009866" />
                                        <stop offset="100%" style="stop-color:#34d399" />
                                    </linearGradient>
                                    <linearGradient id="limeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#a3cc00" />
                                        <stop offset="100%" style="stop-color:#ccff00" />
                                    </linearGradient>
                                    <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:#009866;stop-opacity:0.3" />
                                        <stop offset="100%" style="stop-color:#009866;stop-opacity:0" />
                                    </linearGradient>
                                </defs>

                                {{-- Background --}}
                                <rect width="800" height="500" class="fill-zinc-100 dark:fill-obsidian-elevated" rx="0" />

                                {{-- Sidebar --}}
                                <rect x="0" y="0" width="180" height="500" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="179" y="0" width="1" height="500" class="fill-zinc-200 dark:fill-white/10" />

                                {{-- Sidebar Logo --}}
                                <rect x="24" y="24" width="32" height="32" rx="8" fill="#009866" />
                                <rect x="64" y="32" width="80" height="16" rx="4" class="fill-zinc-300 dark:fill-white/20" />

                                {{-- Sidebar Nav Items --}}
                                <rect x="24" y="90" width="132" height="36" rx="8" fill="#009866" fill-opacity="0.1" />
                                <rect x="40" y="100" width="16" height="16" rx="4" fill="#009866" />
                                <rect x="64" y="104" width="60" height="8" rx="2" fill="#009866" />

                                <g class="fill-zinc-400 dark:fill-white/30">
                                    <rect x="40" y="150" width="16" height="16" rx="4" />
                                    <rect x="64" y="154" width="50" height="8" rx="2" />

                                    <rect x="40" y="190" width="16" height="16" rx="4" />
                                    <rect x="64" y="194" width="70" height="8" rx="2" />

                                    <rect x="40" y="230" width="16" height="16" rx="4" />
                                    <rect x="64" y="234" width="55" height="8" rx="2" />

                                    <rect x="40" y="270" width="16" height="16" rx="4" />
                                    <rect x="64" y="274" width="65" height="8" rx="2" />
                                </g>

                                {{-- Sidebar Bottom User --}}
                                <circle cx="48" cy="460" r="16" class="fill-zinc-300 dark:fill-white/20" />
                                <rect x="72" y="452" width="60" height="8" rx="2" class="fill-zinc-400 dark:fill-white/30" />
                                <rect x="72" y="464" width="40" height="6" rx="2" class="fill-zinc-300 dark:fill-white/20" />

                                {{-- Main Content Area --}}
                                {{-- Header --}}
                                <rect x="204" y="24" width="200" height="36" rx="18" class="fill-zinc-200 dark:fill-white/10" />
                                <circle cx="222" cy="42" r="8" class="fill-zinc-400 dark:fill-white/30" />

                                {{-- Header Right Icons --}}
                                <circle cx="720" cy="42" r="12" class="fill-zinc-200 dark:fill-white/10" />
                                <circle cx="756" cy="42" r="16" class="fill-zinc-300 dark:fill-white/20" />

                                {{-- Page Title --}}
                                <rect x="204" y="84" width="120" height="20" rx="4" class="fill-zinc-400 dark:fill-white/40" />

                                {{-- Stat Cards --}}
                                {{-- Card 1 - Members (Emerald) --}}
                                <rect x="204" y="124" width="176" height="100" rx="12" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="220" y="140" width="60" height="8" rx="2" fill="#009866" fill-opacity="0.6" />
                                <rect x="220" y="160" width="80" height="24" rx="4" class="fill-zinc-800 dark:fill-white/80" />
                                <rect x="220" y="196" width="50" height="12" rx="6" fill="#009866" fill-opacity="0.15" />
                                <rect x="228" y="200" width="34" height="4" rx="1" fill="#009866" />

                                {{-- Card 2 - Giving (Lime) --}}
                                <rect x="396" y="124" width="176" height="100" rx="12" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="412" y="140" width="50" height="8" rx="2" fill="#a3cc00" fill-opacity="0.6" />
                                <rect x="412" y="160" width="90" height="24" rx="4" class="fill-zinc-800 dark:fill-white/80" />
                                <rect x="412" y="196" width="50" height="12" rx="6" fill="#ccff00" fill-opacity="0.15" />
                                <rect x="420" y="200" width="34" height="4" rx="1" fill="#a3cc00" />

                                {{-- Card 3 - Attendance --}}
                                <rect x="588" y="124" width="176" height="100" rx="12" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="604" y="140" width="70" height="8" rx="2" class="fill-zinc-400 dark:fill-white/40" />
                                <rect x="604" y="160" width="70" height="24" rx="4" class="fill-zinc-800 dark:fill-white/80" />
                                <rect x="604" y="196" width="50" height="12" rx="6" class="fill-zinc-200 dark:fill-white/10" />
                                <rect x="612" y="200" width="34" height="4" rx="1" class="fill-zinc-400 dark:fill-white/40" />

                                {{-- Chart Area --}}
                                <rect x="204" y="244" width="368" height="232" rx="12" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="220" y="260" width="100" height="12" rx="3" class="fill-zinc-400 dark:fill-white/40" />
                                <rect x="220" y="278" width="60" height="8" rx="2" class="fill-zinc-300 dark:fill-white/20" />

                                {{-- Chart Line --}}
                                <path d="M240 420 L280 400 L320 410 L360 370 L400 380 L440 340 L480 350 L520 310"
                                      stroke="#009866" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                {{-- Chart Area Fill --}}
                                <path d="M240 420 L280 400 L320 410 L360 370 L400 380 L440 340 L480 350 L520 310 L520 440 L240 440 Z"
                                      fill="url(#chartGradient)" />
                                {{-- Chart Dots --}}
                                <circle cx="240" cy="420" r="4" fill="#009866" />
                                <circle cx="280" cy="400" r="4" fill="#009866" />
                                <circle cx="320" cy="410" r="4" fill="#009866" />
                                <circle cx="360" cy="370" r="4" fill="#009866" />
                                <circle cx="400" cy="380" r="4" fill="#009866" />
                                <circle cx="440" cy="340" r="4" fill="#009866" />
                                <circle cx="480" cy="350" r="4" fill="#009866" />
                                <circle cx="520" cy="310" r="6" fill="#009866" stroke="white" stroke-width="2" />

                                {{-- Chart Grid Lines --}}
                                <g class="stroke-zinc-200 dark:stroke-white/10" stroke-width="1">
                                    <line x1="240" y1="440" x2="540" y2="440" />
                                    <line x1="240" y1="400" x2="540" y2="400" stroke-dasharray="4,4" />
                                    <line x1="240" y1="360" x2="540" y2="360" stroke-dasharray="4,4" />
                                    <line x1="240" y1="320" x2="540" y2="320" stroke-dasharray="4,4" />
                                </g>

                                {{-- Right Panel - Recent Activity --}}
                                <rect x="588" y="244" width="176" height="232" rx="12" class="fill-white dark:fill-obsidian-surface" />
                                <rect x="604" y="260" width="80" height="12" rx="3" class="fill-zinc-400 dark:fill-white/40" />

                                {{-- Activity Items --}}
                                <g>
                                    <circle cx="620" cy="300" r="12" fill="#009866" fill-opacity="0.1" />
                                    <rect x="642" y="294" width="80" height="6" rx="2" class="fill-zinc-500 dark:fill-white/50" />
                                    <rect x="642" y="304" width="50" height="4" rx="1" class="fill-zinc-300 dark:fill-white/20" />
                                </g>
                                <g>
                                    <circle cx="620" cy="344" r="12" fill="#ccff00" fill-opacity="0.15" />
                                    <rect x="642" y="338" width="90" height="6" rx="2" class="fill-zinc-500 dark:fill-white/50" />
                                    <rect x="642" y="348" width="40" height="4" rx="1" class="fill-zinc-300 dark:fill-white/20" />
                                </g>
                                <g>
                                    <circle cx="620" cy="388" r="12" fill="#009866" fill-opacity="0.1" />
                                    <rect x="642" y="382" width="70" height="6" rx="2" class="fill-zinc-500 dark:fill-white/50" />
                                    <rect x="642" y="392" width="55" height="4" rx="1" class="fill-zinc-300 dark:fill-white/20" />
                                </g>
                                <g>
                                    <circle cx="620" cy="432" r="12" class="fill-zinc-200 dark:fill-white/10" />
                                    <rect x="642" y="426" width="85" height="6" rx="2" class="fill-zinc-500 dark:fill-white/50" />
                                    <rect x="642" y="436" width="45" height="4" rx="1" class="fill-zinc-300 dark:fill-white/20" />
                                </g>
                            </svg>
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
