<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')

        {{-- Onboarding page fonts (Space Grotesk like auth pages) --}}
        <link rel="preload" href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" as="style">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="landing-page min-h-screen bg-zinc-100 antialiased dark:bg-obsidian-base">
        {{-- Floating Shell Container --}}
        <div class="relative mx-auto my-4 min-h-[calc(100vh-2rem)] max-w-[1000px] overflow-hidden rounded-[2rem] bg-white ring-1 ring-black/10 sm:my-6 lg:my-8 dark:bg-obsidian-surface dark:ring-white/10">
            {{-- Grid Pattern Background --}}
            <div class="pointer-events-none absolute inset-0 grid-pattern"></div>

            {{-- Glow Spheres --}}
            <div class="glow-sphere glow-emerald absolute -right-20 -top-20 size-64 opacity-30"></div>
            <div class="glow-sphere glow-lime absolute -bottom-20 -left-20 size-48 opacity-20"></div>

            {{-- Content --}}
            <div class="relative flex min-h-[calc(100vh-2rem)] flex-col sm:min-h-[calc(100vh-3rem)] lg:min-h-[calc(100vh-4rem)]">
                {{-- Header --}}
                <header class="border-b border-black/5 bg-white/80 backdrop-blur-sm dark:border-white/5 dark:bg-obsidian-surface/80">
                    <div class="mx-auto max-w-3xl px-6 lg:px-8">
                        <div class="flex h-16 items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 ring-2 ring-emerald-500/20">
                                    <x-app-logo-icon class="size-6 fill-current text-emerald-600 dark:text-emerald-400" />
                                </span>
                                <span class="text-lg font-semibold text-primary">
                                    {{ config('app.name', 'Kingdom Vitals') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-4">
                                <span class="hidden text-sm text-muted sm:block">
                                    {{ auth()->user()?->email }}
                                </span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:button type="submit" variant="ghost" size="sm">
                                        Sign out
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Progress bar --}}
                @if(isset($progress))
                <div class="border-b border-black/5 bg-white/60 backdrop-blur-sm dark:border-white/5 dark:bg-obsidian-surface/60">
                    <div class="mx-auto max-w-3xl px-6 py-3 lg:px-8">
                        <div class="flex items-center justify-between text-xs text-muted mb-2">
                            <span class="label-mono text-emerald-600 dark:text-emerald-400">Setup Progress</span>
                            <span class="font-medium">{{ $progress }}%</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-lime-accent transition-all duration-500 ease-out"
                                style="width: {{ $progress }}%"
                            ></div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Main content --}}
                <main class="flex-1 py-8">
                    <div class="mx-auto max-w-3xl px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>

                {{-- Footer --}}
                <footer class="border-t border-black/5 bg-white/60 py-4 backdrop-blur-sm dark:border-white/5 dark:bg-obsidian-surface/60">
                    <div class="mx-auto max-w-3xl px-6 lg:px-8">
                        <p class="text-center text-sm text-muted">
                            Need help? Contact support at
                            <a href="mailto:support@kingdomvitals.com" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">
                                support@kingdomvitals.com
                            </a>
                        </p>
                    </div>
                </footer>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
