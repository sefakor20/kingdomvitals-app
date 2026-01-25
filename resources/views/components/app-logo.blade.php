@php
    $logoUrl = null;
    $appName = config('app.name'); // Default to platform name

    // Check for tenant logo and name first
    if (function_exists('tenant') && tenant()) {
        $logoUrl = tenant()->getLogoUrl('small');
        $appName = tenant()->name ?? $appName;
    }

    // Fall back to platform logo if no tenant logo
    if (!$logoUrl) {
        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
        if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['small'])) {
            $path = $platformLogoPaths['small'];
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        }
    }
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="size-8 rounded-md object-contain" />
@else
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
    </div>
@endif
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ $appName }}</span>
</div>
