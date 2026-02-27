<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')

        {{-- Auth page fonts (Space Grotesk) --}}
        <link rel="preload" href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" as="style">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="landing-page min-h-screen bg-zinc-100 antialiased dark:bg-obsidian-base">
        {{-- Floating Shell Container --}}
        <div class="relative mx-auto my-4 min-h-[calc(100vh-2rem)] max-w-[800px] overflow-hidden rounded-[2rem] bg-white ring-1 ring-black/10 sm:my-6 lg:my-8 dark:bg-obsidian-surface dark:ring-white/10">
            {{-- Grid Pattern Background --}}
            <div class="pointer-events-none absolute inset-0 grid-pattern"></div>

            {{-- Glow Spheres --}}
            <div class="glow-sphere glow-emerald absolute -right-20 -top-20 size-64 opacity-30"></div>
            <div class="glow-sphere glow-lime absolute -bottom-20 -left-20 size-48 opacity-20"></div>

            {{-- Content --}}
            <div class="relative flex min-h-[calc(100vh-2rem)] flex-col items-center justify-center p-6 sm:min-h-[calc(100vh-3rem)] sm:p-8 lg:min-h-[calc(100vh-4rem)] lg:p-10">
                <div class="flex w-full max-w-md flex-col gap-6">
                    @php
                        $authLogoUrl = \App\Services\LogoService::getTenantLogoUrl('medium');
                        $authAppName = (function_exists('tenant') && tenant() ? tenant()->name : null) ?? config('app.name');
                    @endphp

                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 font-medium" wire:navigate>
                        @if($authLogoUrl)
                            <img src="{{ $authLogoUrl }}" alt="{{ $authAppName }}" class="h-14 w-14 rounded-xl object-contain ring-2 ring-emerald-500/20" />
                        @else
                            <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-500/10 ring-2 ring-emerald-500/20">
                                <x-app-logo-icon class="size-8 fill-current text-emerald-600 dark:text-emerald-400" />
                            </span>
                        @endif
                        <span class="text-lg font-semibold text-primary">{{ $authAppName }}</span>
                    </a>

                    {{-- Auth Card --}}
                    <div class="rounded-2xl border border-black/10 bg-white/95 shadow-xl backdrop-blur-sm dark:border-white/10 dark:bg-obsidian-surface/95">
                        <div class="px-8 py-8 sm:px-10">{{ $slot }}</div>
                    </div>

                    {{-- Footer --}}
                    <div class="text-center">
                        <p class="text-xs text-muted">
                            Powered by <a href="{{ url('/') }}" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ config('app.name') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
