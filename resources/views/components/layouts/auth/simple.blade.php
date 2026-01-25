<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                @php
                    $authLogoUrl = null;
                    $authAppName = config('app.name');

                    // Check for tenant logo and name first
                    if (function_exists('tenant') && tenant()) {
                        $authLogoUrl = tenant()->getLogoUrl('medium');
                        $authAppName = tenant()->name ?? $authAppName;
                    }

                    // Fall back to platform logo
                    if (!$authLogoUrl) {
                        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
                        if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['medium'])) {
                            $path = $platformLogoPaths['medium'];
                            $fullPath = base_path('storage/app/public/'.$path);
                            if (file_exists($fullPath)) {
                                $authLogoUrl = url('storage/'.$path);
                            }
                        }
                    }
                @endphp

                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    @if($authLogoUrl)
                        <img src="{{ $authLogoUrl }}" alt="{{ $authAppName }}" class="h-12 w-12 mb-1 rounded-md object-contain" />
                    @else
                        <span class="flex h-12 w-12 mb-1 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>
                    @endif
                    <span class="sr-only">{{ $authAppName }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
