@props([
    'lines' => 3,
    'avatar' => false,
    'card' => false
])

<div {{ $attributes->class(['animate-pulse']) }}>
    @if($avatar)
        <div class="mb-4 flex items-center gap-4">
            <div class="skeleton size-12 rounded-full"></div>
            <div class="flex-1 space-y-2">
                <div class="skeleton h-4 w-3/4"></div>
                <div class="skeleton h-3 w-1/2"></div>
            </div>
        </div>
    @endif

    @if($card)
        <div class="skeleton mb-4 h-48 w-full"></div>
    @endif

    <div class="space-y-3">
        @for($i = 0; $i < $lines; $i++)
            <div class="skeleton h-4 {{ $i === $lines - 1 ? 'w-2/3' : 'w-full' }}"></div>
        @endfor
    </div>
</div>
