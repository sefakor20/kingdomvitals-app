<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-900 antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600">
                        <flux:icon.shield-check class="size-7 text-white" />
                    </div>
                    <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
                    <span class="text-sm text-zinc-400">Platform Administration</span>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-zinc-700 bg-zinc-800 text-zinc-200 shadow-lg">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
