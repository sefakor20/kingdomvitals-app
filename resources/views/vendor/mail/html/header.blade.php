@props(['url'])

@php
    $logoUrl = null;
    $appName = config('app.name');

    // Check for tenant logo and name first
    if (function_exists('tenant') && tenant()) {
        $logoUrl = tenant()->getLogoUrl('medium');
        $appName = tenant()->name ?? $appName;
    }

    // Fall back to platform logo
    if (!$logoUrl) {
        $platformLogoPaths = \App\Models\SystemSetting::get('platform_logo');
        if ($platformLogoPaths && is_array($platformLogoPaths) && isset($platformLogoPaths['medium'])) {
            $path = $platformLogoPaths['medium'];
            $fullPath = base_path('storage/app/public/'.$path);
            if (file_exists($fullPath)) {
                $logoUrl = url('storage/'.$path);
            }
        }
    }
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $appName }}" height="50"tyle="max-width: 100%; height: 50px;">
@else
{{ $appName }}
@endif
</a>
</td>
</tr>
