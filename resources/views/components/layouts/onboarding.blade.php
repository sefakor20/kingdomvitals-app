<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh flex flex-col">
            <!-- Header -->
            <header class="border-b bg-white dark:bg-stone-950 dark:border-stone-800">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center gap-3">
                            <x-app-logo-icon class="size-8 fill-current text-black dark:text-white" />
                            <span class="text-lg font-semibold text-stone-900 dark:text-white">
                                {{ config('app.name', 'Kingdom Vitals') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="text-sm text-stone-500 dark:text-stone-400">
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

            <!-- Progress bar -->
            @if(isset($progress))
            <div class="border-b bg-white dark:bg-stone-950 dark:border-stone-800">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-400 mb-2">
                        <span>Setup Progress</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-stone-200 dark:bg-stone-800 rounded-full h-2">
                        <div
                            class="bg-emerald-600 h-2 rounded-full transition-all duration-300"
                            style="width: {{ $progress }}%"
                        ></div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main content -->
            <main class="flex-1 py-8">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-t bg-white dark:bg-stone-950 dark:border-stone-800 py-4">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-stone-500 dark:text-stone-400">
                        Need help? Contact support at <a href="mailto:support@kingdomvitals.com" class="text-emerald-600 hover:underline">support@kingdomvitals.com</a>
                    </p>
                </div>
            </footer>
        </div>
        @fluxScripts
    </body>
</html>
