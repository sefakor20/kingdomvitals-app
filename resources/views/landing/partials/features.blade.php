<section
    id="features"
    class="py-24 transition-all duration-700 ease-out sm:py-32"
    x-data="{ shown: false }"
    x-intersect.once.threshold.10="shown = true"
    :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
>
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{-- Section header --}}
        <div class="mx-auto max-w-2xl text-center">
            <p class="label-mono text-emerald-600 dark:text-emerald-400">Features</p>
            <h2 class="mt-4 text-4xl font-light tracking-tighter text-primary sm:text-5xl">
                Everything you need
            </h2>
            <p class="mt-6 text-lg leading-8 text-secondary">
                From membership to finances, attendance to communication — manage every aspect of your ministry from one powerful platform.
            </p>
        </div>

        {{-- Bento Grid --}}
        <div class="mx-auto mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Large Feature Card (spans 2x2) - Membership --}}
            <div class="glass-card col-span-1 row-span-2 p-8 transition hover:border-emerald-500/40 sm:col-span-2">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-emerald-500/10">
                    <svg class="size-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <h3 class="mt-6 text-2xl font-medium tracking-tight text-primary">Membership Management</h3>
                <p class="mt-4 text-secondary">
                    Keep your member directory organized with detailed profiles, households, and children's ministry tracking. Manage the entire member lifecycle from visitor to active member.
                </p>
                {{-- Visual element: Stats bars --}}
                <div class="mt-8 flex items-end gap-2">
                    <div class="h-16 w-4 rounded-t-lg bg-emerald-500/20"></div>
                    <div class="h-24 w-4 rounded-t-lg bg-emerald-500/30"></div>
                    <div class="h-20 w-4 rounded-t-lg bg-emerald-500/25"></div>
                    <div class="h-32 w-4 rounded-t-lg bg-emerald-500/40"></div>
                    <div class="h-28 w-4 rounded-t-lg bg-emerald-500/35"></div>
                    <div class="h-36 w-4 rounded-t-lg bg-emerald-500"></div>
                </div>
            </div>

            {{-- Standard Card - Attendance --}}
            <div class="glass-card p-6 transition hover:border-emerald-500/40">
                <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/10">
                    <svg class="size-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-primary">Attendance & Check-in</h3>
                <p class="mt-2 text-sm text-secondary">Mobile QR check-in, real-time dashboards, capacity tracking</p>
            </div>

            {{-- AI Feature Card with Badge --}}
            <div class="glass-card relative p-6 transition hover:border-lime-500/40">
                <span class="status-tag absolute right-4 top-4 flex items-center gap-1.5 text-lime-600 dark:text-lime-accent">
                    <span class="size-1.5 rounded-full bg-lime-500 pulse-dot dark:bg-lime-accent"></span>
                    AI
                </span>
                <div class="flex size-12 items-center justify-center rounded-xl bg-lime-500/10">
                    <svg class="size-6 text-lime-600 dark:text-lime-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-primary">Visitor Engagement</h3>
                <p class="mt-2 text-sm text-secondary">AI-powered conversion scoring predicts likely members</p>
            </div>

            {{-- Accent Card - Lime background --}}
            <div class="noise-overlay relative overflow-hidden rounded-[2rem] bg-lime-accent p-6 text-obsidian-base transition hover:scale-[1.02]">
                <div class="relative z-10">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-black/10">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-bold">AI-Powered</h3>
                    <p class="mt-2 text-sm font-medium opacity-80">Smart insights that help you understand and serve your congregation better</p>
                </div>
            </div>

            {{-- Standard Card - Finance with AI badge --}}
            <div class="glass-card relative p-6 transition hover:border-emerald-500/40">
                <span class="status-tag absolute right-4 top-4 flex items-center gap-1.5 text-lime-600 dark:text-lime-accent">
                    <span class="size-1.5 rounded-full bg-lime-500 pulse-dot dark:bg-lime-accent"></span>
                    AI
                </span>
                <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/10">
                    <svg class="size-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-primary">Financial Management</h3>
                <p class="mt-2 text-sm text-secondary">AI-powered giving forecasts and donor insights</p>
            </div>

            {{-- Standard Card - Volunteers --}}
            <div class="glass-card p-6 transition hover:border-emerald-500/40">
                <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/10">
                    <svg class="size-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-primary">Volunteer Coordination</h3>
                <p class="mt-2 text-sm text-secondary">Duty rosters, small groups, service schedules</p>
            </div>

            {{-- Tall Card (spans 1x2) - Reports with AI badge --}}
            <div class="glass-card relative row-span-2 p-6 transition hover:border-lime-500/40">
                <span class="status-tag absolute right-4 top-4 flex items-center gap-1.5 text-lime-600 dark:text-lime-accent">
                    <span class="size-1.5 rounded-full bg-lime-500 pulse-dot dark:bg-lime-accent"></span>
                    AI
                </span>
                <div class="flex size-12 items-center justify-center rounded-xl bg-lime-500/10">
                    <svg class="size-6 text-lime-600 dark:text-lime-accent" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-primary">AI Insights Dashboard</h3>
                <p class="mt-3 text-sm text-secondary">
                    Comprehensive AI-powered dashboard with member lifecycle tracking, engagement scores, and actionable recommendations.
                </p>
                {{-- Color swatches visual --}}
                <div class="mt-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="size-4 rounded-full bg-emerald-500"></div>
                        <span class="text-xs text-muted">Active Members</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-4 rounded-full bg-lime-accent"></div>
                        <span class="text-xs text-muted">High Engagement</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-4 rounded-full bg-amber-500"></div>
                        <span class="text-xs text-muted">At Risk</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-4 rounded-full bg-rose-500"></div>
                        <span class="text-xs text-muted">Inactive</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
