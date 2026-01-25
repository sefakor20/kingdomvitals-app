<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-zinc-900 antialiased">
        @php
            $platformLogoUrl = null;
            $platformName = config('app.name');

            $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
            if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['medium'])) {
                $path = $platformLogoPaths['medium'];
                $fullPath = base_path('storage/app/public/'.$path);
                if (file_exists($fullPath)) {
                    $platformLogoUrl = url('storage/'.$path);
                }
            }
        @endphp

        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <div class="flex flex-col items-center gap-2">
                    @if($platformLogoUrl)
                        <img src="{{ $platformLogoUrl }}" alt="{{ $platformName }}" class="h-12 w-12 rounded-lg object-contain" />
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600">
                            <flux:icon.shield-check class="size-7 text-white" />
                        </div>
                    @endif
                    <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $platformName }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Platform Administration</span>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 shadow-lg">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
