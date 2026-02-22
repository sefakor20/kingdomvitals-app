<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="lg" class="mb-4">{{ __('Activity History') }}</flux:heading>

    @if($this->activities->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('No activity recorded yet.') }}
        </p>
    @else
        <div class="space-y-4">
            @foreach($this->activities as $activity)
                <div wire:key="activity-{{ $activity->id }}"
                     class="flex items-start gap-3 border-b border-zinc-100 pb-4 last:border-0 dark:border-zinc-800">
                    <div @class([
                        'flex-shrink-0 rounded-full p-2',
                        'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400' => $activity->event->color() === 'green',
                        'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400' => $activity->event->color() === 'blue',
                        'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400' => $activity->event->color() === 'red',
                        'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400' => $activity->event->color() === 'yellow',
                        'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400' => $activity->event->color() === 'emerald',
                        'bg-slate-100 text-slate-600 dark:bg-slate-900/50 dark:text-slate-400' => $activity->event->color() === 'slate',
                        'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' => $activity->event->color() === 'indigo',
                        'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400' => $activity->event->color() === 'purple',
                    ])>
                        @switch($activity->event->icon())
                            @case('plus')
                                <flux:icon.plus class="size-4" />
                                @break
                            @case('pencil')
                                <flux:icon.pencil class="size-4" />
                                @break
                            @case('trash')
                                <flux:icon.trash class="size-4" />
                                @break
                            @case('arrow-path')
                                <flux:icon.arrow-path class="size-4" />
                                @break
                            @case('arrow-right-end-on-rectangle')
                                <flux:icon.arrow-right-end-on-rectangle class="size-4" />
                                @break
                            @case('arrow-left-start-on-rectangle')
                                <flux:icon.arrow-left-start-on-rectangle class="size-4" />
                                @break
                            @case('exclamation-triangle')
                                <flux:icon.exclamation-triangle class="size-4" />
                                @break
                            @case('arrow-down-tray')
                                <flux:icon.arrow-down-tray class="size-4" />
                                @break
                            @case('arrow-up-tray')
                                <flux:icon.arrow-up-tray class="size-4" />
                                @break
                            @case('pencil-square')
                                <flux:icon.pencil-square class="size-4" />
                                @break
                            @default
                                <flux:icon.document class="size-4" />
                        @endswitch
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $activity->formatted_description }}
                        </p>

                        @if($activity->event === \App\Enums\ActivityEvent::Updated && $activity->changed_fields)
                            <div class="mt-2 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @foreach($activity->changed_fields as $field)
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                        <span class="line-through">{{ $activity->old_values[$field] ?? '-' }}</span>
                                        <flux:icon.arrow-right class="size-3" />
                                        <span>{{ $activity->new_values[$field] ?? '-' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span>{{ $activity->created_at->diffForHumans() }}</span>
                            @if($activity->user)
                                <span>&bull;</span>
                                <span>{{ $activity->user->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($this->hasMore)
            <div class="mt-4 text-center">
                <flux:button variant="ghost" wire:click="loadMore" size="sm">
                    {{ __('Load more') }}
                </flux:button>
            </div>
        @endif
    @endif
</div>
