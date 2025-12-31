@props([
    'on',
    'type' => 'success',
])

@php
$types = [
    'success' => 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/50 dark:text-green-300 dark:border-green-800',
    'error' => 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/50 dark:text-red-300 dark:border-red-800',
    'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/50 dark:text-yellow-300 dark:border-yellow-800',
    'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/50 dark:text-blue-300 dark:border-blue-800',
];
$icons = [
    'success' => 'check-circle',
    'error' => 'x-circle',
    'warning' => 'exclamation-triangle',
    'info' => 'information-circle',
];
@endphp

<div
    x-data="{ shown: false, timeout: null }"
    x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 3000); })"
    x-show="shown"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    style="display: none"
    {{ $attributes->merge(['class' => 'fixed top-4 right-4 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg ' . $types[$type]]) }}
>
    <flux:icon :icon="$icons[$type]" class="size-5" />
    <span class="text-sm font-medium">{{ $slot }}</span>
    <button type="button" x-on:click="shown = false" class="ml-2 opacity-70 hover:opacity-100">
        <flux:icon icon="x-mark" class="size-4" />
    </button>
</div>
