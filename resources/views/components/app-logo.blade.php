@php
    $logoUrl = \App\Services\LogoService::getTenantLogoUrl('small');
    $appName = (function_exists('tenant') && tenant() ? tenant()->name : null) ?? config('app.name');
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-10 w-10 rounded-xl object-contain ring-2 ring-emerald-500/20" />
@else
    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 ring-2 ring-emerald-500/20">
        <x-app-logo-icon class="size-10" />
    </div>
@endif
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ $appName }}</span>
</div>
