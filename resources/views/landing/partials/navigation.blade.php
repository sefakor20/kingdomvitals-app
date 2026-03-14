<div
    x-data="{ mobileMenuOpen: false, scrolled: false }"
    x-init="scrolled = window.scrollY > 50"
    @scroll.window="scrolled = window.scrollY > 50"
    wire:ignore.self
>
    {{-- Header --}}
    <header class="fixed inset-x-0 top-4 z-50 sm:top-6 lg:top-8">
        <nav
            class="mx-auto flex max-w-7xl items-center justify-between rounded-2xl px-6 py-3 transition-all duration-300 lg:px-8"
            :class="scrolled
                ? 'border border-black/10 bg-white/70 shadow-lg shadow-black/5 backdrop-blur-xl dark:border-white/10 dark:bg-obsidian-surface/70'
                : 'border border-transparent bg-transparent shadow-none'"
        >
            {{-- Logo --}}
            <div class="flex items-center lg:flex-1">
                <a href="{{ route('home') }}" class="-m-1.5 p-1.5">
                    <img src="{{ asset('images/logo-black.svg') }}" alt="{{ config('app.name') }}" class="h-12 dark:hidden">
                    <img src="{{ asset('images/logo-white.svg') }}" alt="{{ config('app.name') }}" class="hidden h-12 dark:block">
                </a>
            </div>

            {{-- Mobile menu button --}}
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-secondary"
                    @click="mobileMenuOpen = true"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            {{-- Pill-shaped Desktop Navigation --}}
            <div class="hidden lg:flex">
                <div class="glass-card flex items-center gap-1 px-2 py-1.5">
                    <a href="javascript:void(0)" x-on:click="document.querySelector('#features').scrollIntoView({ behavior: 'smooth' })" class="rounded-full px-4 py-2 text-sm font-medium text-secondary transition hover:bg-black/5 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:hover:bg-white/5 dark:hover:text-emerald-400">Features</a>
                    <a href="javascript:void(0)" x-on:click="document.querySelector('#how-it-works').scrollIntoView({ behavior: 'smooth' })" class="rounded-full px-4 py-2 text-sm font-medium text-secondary transition hover:bg-black/5 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:hover:bg-white/5 dark:hover:text-emerald-400">How It Works</a>
                    <a href="javascript:void(0)" x-on:click="document.querySelector('#pricing').scrollIntoView({ behavior: 'smooth' })" class="rounded-full px-4 py-2 text-sm font-medium text-secondary transition hover:bg-black/5 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:hover:bg-white/5 dark:hover:text-emerald-400">Pricing</a>
                    <a href="javascript:void(0)" x-on:click="document.querySelector('#faq').scrollIntoView({ behavior: 'smooth' })" class="rounded-full px-4 py-2 text-sm font-medium text-secondary transition hover:bg-black/5 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:hover:bg-white/5 dark:hover:text-emerald-400">FAQ</a>
                    <a href="javascript:void(0)" x-on:click="document.querySelector('#contact').scrollIntoView({ behavior: 'smooth' })" class="rounded-full px-4 py-2 text-sm font-medium text-secondary transition hover:bg-black/5 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:hover:bg-white/5 dark:hover:text-emerald-400">Contact</a>
                </div>
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:gap-x-4">
                <a href="javascript:void(0)" x-on:click="document.querySelector('#contact').scrollIntoView({ behavior: 'smooth' })" class="btn-neon rounded-full px-6 py-2.5 text-sm font-semibold">
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
            class="fixed inset-0 bg-black/80 backdrop-blur-sm"
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
                class="glass-card relative w-full max-w-xs bg-white/95 dark:bg-obsidian-surface/95"
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
                            <img src="{{ asset('images/logo-black.svg') }}" alt="{{ config('app.name') }}" class="h-10 dark:hidden">
                            <img src="{{ asset('images/logo-white.svg') }}" alt="{{ config('app.name') }}" class="hidden h-10 dark:block">
                        </a>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-secondary"
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
                                href="javascript:void(0)"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-primary transition hover:bg-black/5 hover:text-emerald-600 dark:hover:bg-white/5 dark:hover:text-emerald-400"
                                x-on:click="document.querySelector('#features').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                            >Features</a>
                            <a
                                href="javascript:void(0)"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-primary transition hover:bg-black/5 hover:text-emerald-600 dark:hover:bg-white/5 dark:hover:text-emerald-400"
                                x-on:click="document.querySelector('#how-it-works').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                            >How It Works</a>
                            <a
                                href="javascript:void(0)"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-primary transition hover:bg-black/5 hover:text-emerald-600 dark:hover:bg-white/5 dark:hover:text-emerald-400"
                                x-on:click="document.querySelector('#pricing').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                            >Pricing</a>
                            <a
                                href="javascript:void(0)"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-primary transition hover:bg-black/5 hover:text-emerald-600 dark:hover:bg-white/5 dark:hover:text-emerald-400"
                                x-on:click="document.querySelector('#faq').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                            >FAQ</a>
                            <a
                                href="javascript:void(0)"
                                class="block rounded-lg px-3 py-2.5 text-base font-medium text-primary transition hover:bg-black/5 hover:text-emerald-600 dark:hover:bg-white/5 dark:hover:text-emerald-400"
                                x-on:click="document.querySelector('#contact').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                            >Contact</a>
                        </div>
                    </div>

                    {{-- CTA button --}}
                    <div class="mt-6 border-t border-black/10 pt-6 dark:border-white/10">
                        <a
                            href="javascript:void(0)"
                            class="btn-neon block rounded-full px-3 py-3 text-center text-base font-semibold"
                            x-on:click="document.querySelector('#contact').scrollIntoView({ behavior: 'smooth' }); mobileMenuOpen = false"
                        >Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
