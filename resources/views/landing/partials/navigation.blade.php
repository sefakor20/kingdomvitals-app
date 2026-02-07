<div x-data="{ mobileMenuOpen: false }">
    {{-- Header --}}
    <header class="fixed inset-x-0 top-0 z-40 bg-white/80 backdrop-blur-lg dark:bg-neutral-950/80">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            {{-- Logo --}}
            <div class="flex lg:flex-1">
                <a href="{{ route('home') }}" class="-m-1.5 p-1.5">
                    <span class="text-xl font-semibold text-neutral-900 dark:text-white">{{ config('app.name') }}</span>
                </a>
            </div>

            {{-- Mobile menu button --}}
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-neutral-700 dark:text-neutral-300"
                    @click="mobileMenuOpen = true"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            {{-- Desktop navigation --}}
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="#features" class="text-sm font-medium text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Features</a>
                <a href="#how-it-works" class="text-sm font-medium text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">How It Works</a>
                <a href="#pricing" class="text-sm font-medium text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">FAQ</a>
                <a href="#contact" class="text-sm font-medium text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Contact</a>
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:gap-x-4">
                <a href="#contact" class="rounded-full bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                    Get Started
                </a>
            </div>
        </nav>
    </header>

    {{-- Mobile menu (outside header for proper z-index stacking) --}}
    <div
        x-show="mobileMenuOpen"
        x-cloak
        class="relative z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-neutral-900/80"
            x-show="mobileMenuOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
        ></div>

        {{-- Panel --}}
        <div class="fixed inset-0 flex justify-end">
            <div
                class="relative w-full max-w-xs bg-white dark:bg-neutral-900"
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                {{-- Panel content --}}
                <div class="flex h-full flex-col overflow-y-auto px-6 py-6">
                    {{-- Header with logo and close --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('home') }}" class="-m-1.5 p-1.5" @click="mobileMenuOpen = false">
                            <span class="text-xl font-semibold text-neutral-900 dark:text-white">{{ config('app.name') }}</span>
                        </a>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-neutral-700 dark:text-neutral-300"
                            @click="mobileMenuOpen = false"
                        >
                            <span class="sr-only">Close menu</span>
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Navigation links --}}
                    <div class="mt-6 flex-1">
                        <div class="space-y-1">
                            <a
                                href="#features"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-neutral-900 hover:bg-neutral-50 dark:text-white dark:hover:bg-neutral-800"
                                @click="mobileMenuOpen = false"
                            >Features</a>
                            <a
                                href="#how-it-works"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-neutral-900 hover:bg-neutral-50 dark:text-white dark:hover:bg-neutral-800"
                                @click="mobileMenuOpen = false"
                            >How It Works</a>
                            <a
                                href="#pricing"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-neutral-900 hover:bg-neutral-50 dark:text-white dark:hover:bg-neutral-800"
                                @click="mobileMenuOpen = false"
                            >Pricing</a>
                            <a
                                href="#faq"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-neutral-900 hover:bg-neutral-50 dark:text-white dark:hover:bg-neutral-800"
                                @click="mobileMenuOpen = false"
                            >FAQ</a>
                            <a
                                href="#contact"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-neutral-900 hover:bg-neutral-50 dark:text-white dark:hover:bg-neutral-800"
                                @click="mobileMenuOpen = false"
                            >Contact</a>
                        </div>
                    </div>

                    {{-- CTA button --}}
                    <div class="mt-6 border-t border-neutral-200 pt-6 dark:border-neutral-700">
                        <a
                            href="#contact"
                            class="block rounded-full bg-neutral-900 px-3 py-3 text-center text-base font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
                            @click="mobileMenuOpen = false"
                        >Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
