@props(['url'])

@php
    // Use passed values if available (from notification viewData)
    $logoUrl = $logoUrl ?? \App\Services\LogoService::getTenantLogoUrl('medium');
    $appName = $appName ?? (function_exists('tenant') && tenant() ? tenant()->name : null) ?? config('app.name');
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $appName }}" style="width: auto; height: 50px; max-height: 50px;">
@else
{{ $appName }}
@endif
</a>
</td>
</tr>
