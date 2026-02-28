<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>@yield('title') - {{ config('app.name', 'Kingdom Vitals') }}</title>

        {{-- Favicon --}}
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

        {{-- Space Grotesk Font --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        {{-- Styles --}}
        @vite(['resources/css/app.css'])

        {{-- Dark mode script --}}
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="landing-page min-h-screen bg-zinc-100 antialiased dark:bg-obsidian-base">
        {{-- Floating Shell Container --}}
        <div class="relative mx-auto my-4 min-h-[calc(100vh-2rem)] max-w-[600px] overflow-hidden rounded-[2rem] bg-white ring-1 ring-black/10 sm:my-6 lg:my-8 dark:bg-obsidian-surface dark:ring-white/10">
            {{-- Grid Pattern Background --}}
            <div class="pointer-events-none absolute inset-0 grid-pattern"></div>

            {{-- Glow Spheres --}}
            <div class="glow-sphere glow-emerald absolute -right-32 -top-32 size-80 opacity-30"></div>
            <div class="glow-sphere glow-lime absolute -bottom-32 -left-32 size-64 opacity-20"></div>

            {{-- Content --}}
            <div class="relative flex min-h-[calc(100vh-2rem)] flex-col items-center justify-center p-6 sm:min-h-[calc(100vh-3rem)] sm:p-8 lg:min-h-[calc(100vh-4rem)] lg:p-10">
                <div class="w-full max-w-md text-center">
                    {{-- Icon with pulse animation --}}
                    <div class="relative mx-auto mb-6" style="width: 5rem; height: 5rem;">
                        {{-- Background Status Code Watermark --}}
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 8rem; font-weight: 700; opacity: 0.05; pointer-events: none; user-select: none; line-height: 1; white-space: nowrap;">
                            @yield('code')
                        </div>
                        <div class="absolute inset-0 animate-ping rounded-full" style="background-color: @yield('icon-ping-color', 'rgba(239, 68, 68, 0.2)');"></div>
                        <div class="relative flex items-center justify-center rounded-full shadow-lg" style="width: 5rem; height: 5rem; background: linear-gradient(135deg, @yield('icon-color-from', '#ef4444'), @yield('icon-color-to', '#f97316'));">
                            @yield('icon')
                        </div>
                    </div>

                    {{-- Error Code Badge --}}
                    <span class="label-mono @yield('label-color', 'text-red-600 dark:text-red-400')">Error @yield('code')</span>

                    {{-- Title --}}
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">
                        <span class="@yield('title-gradient', 'text-gradient-emerald')">@yield('title')</span>
                    </h1>

                    {{-- Message --}}
                    <p class="mx-auto mt-4 max-w-sm text-secondary">
                        @yield('message')
                    </p>

                    {{-- Additional Description --}}
                    @hasSection('description')
                        <div class="mt-6 rounded-xl border border-black/5 bg-black/[0.02] p-4 text-left text-sm text-secondary dark:border-white/5 dark:bg-white/[0.02]">
                            @yield('description')
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        @yield('actions')
                    </div>

                    {{-- Helpful Links --}}
                    @hasSection('links')
                        <div class="mt-8 border-t border-black/5 pt-6 dark:border-white/5">
                            <p class="mb-3 text-xs font-medium uppercase tracking-wider text-muted">Helpful Links</p>
                            <div class="flex flex-wrap justify-center gap-4 text-sm">
                                @yield('links')
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="mt-auto pt-8 text-center">
                    <p class="text-xs text-muted">
                        <a href="{{ url('/') }}" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">{{ config('app.name', 'Kingdom Vitals') }}</a>
                        &bull; Need help? <a href="mailto:support@kingdomvitals.com" class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Contact Support</a>
                    </p>
                </div>
            </div>
        </div>

        @vite(['resources/js/app.js'])
    </body>
</html>
