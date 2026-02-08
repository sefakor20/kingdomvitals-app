<div
    x-data="{
        shown: false,
        init() {
            this.shown = !localStorage.getItem('cookie-consent');
        },
        accept() {
            localStorage.setItem('cookie-consent', 'accepted');
            this.shown = false;
        },
        decline() {
            localStorage.setItem('cookie-consent', 'declined');
            this.shown = false;
        }
    }"
    x-show="shown"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    x-cloak
    class="fixed inset-x-0 bottom-0 z-50 p-4"
>
    <div class="mx-auto max-w-4xl rounded-xl bg-white p-6 shadow-2xl ring-1 ring-neutral-900/10 dark:bg-neutral-800 dark:ring-white/10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                        <svg class="size-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">Cookie Notice</h3>
                        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            We use cookies to enhance your experience and analyze site traffic. By clicking "Accept", you consent to our use of cookies.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 gap-3 sm:ml-4">
                <button
                    @click="decline()"
                    type="button"
                    class="rounded-lg px-4 py-2.5 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-700"
                >
                    Decline
                </button>
                <button
                    @click="accept()"
                    type="button"
                    class="rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-purple-700"
                >
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>
