<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')

        {{-- Public page fonts (Space Grotesk) --}}
        <link rel="preload" href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" as="style">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        <script src="https://js.paystack.co/v2/inline.js"></script>
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
                <div class="w-full max-w-lg">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                <div class="mt-8 text-center">
                    <p class="text-xs text-muted">
                        Powered by <a href="{{ url('/') }}" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ config('app.name') }}</a>
                    </p>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
