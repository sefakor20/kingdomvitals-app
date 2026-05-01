<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <x-billing-banner />
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
