@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-semibold tracking-tight text-primary">{{ $title }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $description }}</p>
</div>
