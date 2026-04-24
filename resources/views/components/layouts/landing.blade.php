<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')

        {{-- Landing page specific fonts --}}
        <link rel="preload" href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" as="style">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        <title>{{ $title ?? 'Start your free trial' }} - {{ config('app.name') }}</title>

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="landing-page min-h-screen bg-zinc-100 antialiased dark:bg-obsidian-base">
        {{-- Floating Shell Container --}}
        <div class="relative mx-auto my-4 min-h-[calc(100vh-2rem)] max-w-[1600px] overflow-hidden rounded-[2.5rem] bg-white ring-1 ring-black/10 sm:my-6 lg:my-8 dark:bg-obsidian-surface dark:ring-white/10">
            {{-- Grid Pattern Background --}}
            <div class="pointer-events-none absolute inset-0 grid-pattern"></div>

            {{-- Glow Spheres --}}
            <div class="glow-sphere glow-emerald absolute -right-40 -top-40 size-96 opacity-50"></div>
            <div class="glow-sphere glow-lime absolute -bottom-40 -left-40 size-80 opacity-30"></div>

            {{-- Navigation --}}
            @include('landing.partials.navigation')

            {{-- Main Content --}}
            <main class="relative">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            @include('landing.partials.footer')
        </div>

        {{-- Offline overlay --}}
        @include('partials.offline-overlay')

        @fluxScripts
    </body>
</html>
