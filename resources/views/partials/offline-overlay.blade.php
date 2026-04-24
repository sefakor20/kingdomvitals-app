{{-- Full-screen "You're offline" overlay. Hidden by default; shown by CSS when
     `body[data-offline="true"]`. Toggled from resources/js/offline-indicator.js. --}}
<div
    id="offline-overlay"
    role="alertdialog"
    aria-live="assertive"
    aria-labelledby="offline-overlay-heading"
    aria-describedby="offline-overlay-description"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-white/90 p-6 backdrop-blur-lg dark:bg-obsidian-base/90"
>
    <div class="glass-card relative w-full max-w-md overflow-hidden p-8 text-center shadow-2xl">
        {{-- Icon with pulse --}}
        <div class="relative mx-auto mb-6 size-20">
            <div class="absolute inset-0 animate-ping rounded-full bg-red-500/20"></div>
            <div class="relative flex size-20 items-center justify-center rounded-full shadow-lg" style="background: linear-gradient(135deg, #ef4444, #f97316);">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm6.28-7.466a8.25 8.25 0 0 0-12.56 0M21 10.5a12 12 0 0 0-18 0M3 3l18 18" />
                </svg>
            </div>
        </div>

        <span class="label-mono text-red-600 dark:text-red-400">Connection Lost</span>

        <h1 id="offline-overlay-heading" class="mt-3 text-2xl font-semibold tracking-tight text-primary sm:text-3xl">
            <span class="text-gradient-emerald">You're offline</span>
        </h1>

        <p id="offline-overlay-description" class="mx-auto mt-4 max-w-sm text-secondary">
            Check your connection and we'll pick up where you left off.
        </p>

        <div class="mt-8">
            <button
                type="button"
                id="offline-overlay-retry"
                class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold"
            >
                <svg class="mr-2 inline size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Try again
            </button>
        </div>

        <p class="mt-6 text-xs text-muted">
            Still stuck? <a href="mailto:support@kingdomvitals.com" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Email support</a>
        </p>
    </div>
</div>
