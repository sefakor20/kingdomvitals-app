<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Activity Logs') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                {{ __('View all activity and changes within your branch') }}
            </flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
            {{ __('Export CSV') }}
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="mb-6 grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="{{ __('Search...') }}"
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="subjectType">
            <option value="">{{ __('All Entities') }}</option>
            @foreach($this->subjectTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="event">
            <option value="">{{ __('All Events') }}</option>
            @foreach($this->events as $eventOption)
                <option value="{{ $eventOption->value }}">{{ $eventOption->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="userId">
            <option value="">{{ __('All Users') }}</option>
            @foreach($this->users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </flux:select>

        <div class="lg:col-span-2">
            <flux:label class="mb-2">{{ __('Date Range') }}</flux:label>
            <div class="flex gap-2">
                <flux:input wire:model.live="dateFrom" type="date" class="flex-1" />
                <flux:input wire:model.live="dateTo" type="date" class="flex-1" />
            </div>
        </div>
    </div>

    @if($search || $subjectType || $event || $userId || $dateFrom || $dateTo)
        <div class="mb-4">
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                {{ __('Clear Filters') }}
            </flux:button>
        </div>
    @endif

    {{-- Activity Log List --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse($logs as $log)
                <div wire:key="log-{{ $log->id }}"
                     class="flex items-start gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    {{-- Event Icon --}}
                    <div @class([
                        'flex-shrink-0 rounded-full p-2',
                        'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400' => $log->event->color() === 'green',
                        'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400' => $log->event->color() === 'blue',
                        'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-400' => $log->event->color() === 'red',
                        'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400' => $log->event->color() === 'yellow',
                        'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400' => $log->event->color() === 'emerald',
                        'bg-slate-100 text-slate-600 dark:bg-slate-900/50 dark:text-slate-400' => $log->event->color() === 'slate',
                        'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' => $log->event->color() === 'indigo',
                        'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400' => $log->event->color() === 'purple',
                    ])>
                        @switch($log->event->icon())
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

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" color="zinc">{{ $log->subject_type->label() }}</flux:badge>
                            <flux:badge size="sm" :color="$log->event->color()">{{ $log->event->label() }}</flux:badge>
                        </div>

                        <p class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $log->formatted_description }}
                        </p>

                        @if($log->event === \App\Enums\ActivityEvent::Updated && $log->changed_fields)
                            <div class="mt-2 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @foreach($log->changed_fields as $field)
                                    <div class="flex flex-wrap items-center gap-1">
                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                        <span class="line-through">{{ $log->old_values[$field] ?? '-' }}</span>
                                        <flux:icon.arrow-right class="size-3" />
                                        <span>{{ $log->new_values[$field] ?? '-' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-2 flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <span title="{{ $log->created_at->format('M d, Y H:i:s') }}">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                            @if($log->user)
                                <span>&bull;</span>
                                <span>{{ $log->user->name }}</span>
                            @endif
                            @if($log->ip_address)
                                <span>&bull;</span>
                                <span>{{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <flux:icon.clipboard-document-list class="mx-auto size-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('No activity found') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">
                        @if($search || $subjectType || $event || $userId || $dateFrom || $dateTo)
                            {{ __('Try adjusting your filters') }}
                        @else
                            {{ __('Activity will appear here as actions are performed') }}
                        @endif
                    </flux:text>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @endif
</div>
