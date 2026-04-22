<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

@php
    $faviconUrl = null;
    $appleTouchUrl = null;

    // Check tenant favicon first
    if (function_exists('tenant') && tenant() && tenant()->hasLogo()) {
        $faviconUrl = tenant()->getLogoUrl('favicon');
        $appleTouchUrl = tenant()->getLogoUrl('apple-touch');
    }

    // Fall back to platform favicon
    if (!$faviconUrl) {
        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
        if ($platformLogoPaths && is_array($platformLogoPaths)) {
            if (isset($platformLogoPaths['favicon'])) {
                $path = $platformLogoPaths['favicon'];
                $fullPath = base_path('storage/app/public/'.$path);
                if (file_exists($fullPath)) {
                    $faviconUrl = url('storage/'.$path);
                }
            }
            if (isset($platformLogoPaths['apple-touch'])) {
                $path = $platformLogoPaths['apple-touch'];
                $fullPath = base_path('storage/app/public/'.$path);
                if (file_exists($fullPath)) {
                    $appleTouchUrl = url('storage/'.$path);
                }
            }
        }
    }
@endphp

<link rel="icon" type="image/svg+xml" href="{{ $faviconUrl ?? '/favicon.svg' }}">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchUrl ?? '/apple-touch-icon.png' }}">

{{-- Theme color --}}
<meta name="theme-color" content="#009866">

{{-- Resource hints for performance --}}
<link rel="dns-prefetch" href="//fonts.bunny.net">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" as="style">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<style>[x-cloak] { display: none !important; }</style>
