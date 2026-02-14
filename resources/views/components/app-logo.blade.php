@php
    $logoUrl = \App\Services\LogoService::getTenantLogoUrl('small');
    $appName = (function_exists('tenant') && tenant() ? tenant()->name : null) ?? config('app.name');
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
