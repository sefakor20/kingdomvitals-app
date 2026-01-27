@props(['followUp', 'branch', 'urgency' => 'upcoming'])

@php
    $borderColor = match($urgency) {
        'overdue' => 'border-red-300 dark:border-red-800',
        'today' => 'border-yellow-300 dark:border-yellow-800',
        default => 'border-zinc-200 dark:border-zinc-700',
    };

    $bgColor = match($urgency) {
        'overdue' => 'bg-red-50 dark:bg-red-900/20',
        'today' => 'bg-yellow-50 dark:bg-yellow-900/20',
        default => 'bg-white dark:bg-zinc-900',
    };

    $typeIcon = match($followUp->type->value) {
        'call' => 'phone',
        'sms' => 'chat-bubble-left',
        'email' => 'envelope',
        'visit' => 'map-pin',
        'whatsapp' => 'chat-bubble-oval-left-ellipsis',
        default => 'clipboard-document',
    };

    $typeColor = match($followUp->type->value) {
        'call' => 'text-green-600 dark:text-green-400',
        'sms' => 'text-blue-600 dark:text-blue-400',
        'email' => 'text-purple-600 dark:text-purple-400',
        'visit' => 'text-orange-600 dark:text-orange-400',
        'whatsapp' => 'text-emerald-600 dark:text-emerald-400',
        default => 'text-zinc-600 dark:text-zinc-400',
    };
@endphp

<div class="rounded-lg border {{ $borderColor }} {{ $bgColor }} p-4" wire:key="follow-up-{{ $followUp->id }}">
    <!-- Header with visitor name and type badge -->
    <div class="mb-3 flex items-start justify-between">
        <div class="flex items-center gap-2">
            <flux:avatar size="sm" name="{{ $followUp->visitor->fullName() }}" />
            <div>
                <a
                    href="{{ route('visitors.show', [$branch, $followUp->visitor]) }}"
                    class="font-medium text-zinc-900 hover:text-blue-600 hover:underline dark:text-zinc-100 dark:hover:text-blue-400"
                    wire:navigate
                >
                    {{ $followUp->visitor->fullName() }}
                </a>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Visited') }} {{ $followUp->visitor->visit_date?->format('M d, Y') }}
                </div>
            </div>
        </div>
        <div class="flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">
            <flux:icon :icon="$typeIcon" class="size-3 {{ $typeColor }}" />
            <span class="text-xs font-medium {{ $typeColor }}">{{ ucfirst($followUp->type->value) }}</span>
        </div>
    </div>

    <!-- Contact info -->
    <div class="mb-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
        @if($followUp->visitor->phone)
            <div class="flex items-center gap-2">
                <flux:icon icon="phone" class="size-3" />
                <a href="tel:{{ $followUp->visitor->phone }}" class="hover:text-blue-600 hover:underline dark:hover:text-blue-400">
                    {{ $followUp->visitor->phone }}
                </a>
            </div>
        @endif
        @if($followUp->visitor->email)
            <div class="flex items-center gap-2">
                <flux:icon icon="envelope" class="size-3" />
                <a href="mailto:{{ $followUp->visitor->email }}" class="hover:text-blue-600 hover:underline dark:hover:text-blue-400">
                    {{ $followUp->visitor->email }}
                </a>
            </div>
        @endif
    </div>

    <!-- Scheduled time -->
    <div class="mb-3 flex items-center gap-2 text-sm">
        <flux:icon icon="clock" class="size-4 text-zinc-400" />
        <span class="{{ $urgency === 'overdue' ? 'font-medium text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">
            @if($urgency === 'overdue')
                {{ __('Overdue by') }} {{ $followUp->scheduled_at?->diffForHumans() }}
            @elseif($urgency === 'today')
                {{ $followUp->scheduled_at?->format('g:i A') }}
            @else
                {{ $followUp->scheduled_at?->format('M d, Y g:i A') }}
            @endif
        </span>
    </div>

    <!-- Assigned to -->
    @if($followUp->performedBy)
        <div class="mb-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <flux:icon icon="user" class="size-4 text-zinc-400" />
            <span>{{ __('Assigned to') }} {{ $followUp->performedBy->fullName() }}</span>
        </div>
    @endif

    <!-- Notes preview -->
    @if($followUp->notes)
        <div class="mb-3 rounded bg-zinc-100 p-2 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
            {{ Str::limit($followUp->notes, 100) }}
        </div>
    @endif

    <!-- Actions -->
    <div class="flex items-center gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <flux:button
            variant="primary"
            size="sm"
            icon="check"
            wire:click="openCompleteModal('{{ $followUp->id }}')"
        >
            {{ __('Complete') }}
        </flux:button>
        <flux:button
            variant="ghost"
            size="sm"
            icon="clock"
            wire:click="openRescheduleModal('{{ $followUp->id }}')"
        >
            {{ __('Reschedule') }}
        </flux:button>
        <flux:button
            variant="ghost"
            size="sm"
            icon="eye"
            href="{{ route('visitors.show', [$branch, $followUp->visitor]) }}"
            wire:navigate
        >
            {{ __('View') }}
        </flux:button>
    </div>
</div>
