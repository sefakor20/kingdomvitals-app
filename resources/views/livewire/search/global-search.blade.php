<div
    x-data="{ flatIndex: 0 }"
    x-on:keydown.cmd.k.window.prevent="$wire.openModal()"
    x-on:keydown.ctrl.k.window.prevent="$wire.openModal()"
    x-on:keydown.arrow-down.prevent="$wire.selectNext()"
    x-on:keydown.arrow-up.prevent="$wire.selectPrevious()"
    x-on:keydown.enter.prevent="$wire.selectCurrent()"
    x-effect="if ($wire.showModal) $nextTick(() => $refs.searchInput?.focus())"
>
    {{-- Search Modal (triggered via Cmd+K / Ctrl+K) --}}
    <flux:modal
        wire:model="showModal"
        class="w-full max-w-xl"
        :dismissable="true"
    >
        <div class="flex flex-col">
            {{-- Search Input --}}
            <div class="relative">
                {{-- Loading Spinner / Search Icon --}}
                <div wire:loading wire:target="search" class="absolute left-4 top-1/2 -translate-y-1/2">
                    <svg class="size-5 animate-spin text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <flux:icon wire:loading.remove wire:target="search" icon="magnifying-glass" class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
                <input
                    type="text"
                    x-ref="searchInput"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search members, visitors, services...') }}"
                    class="w-full border-0 border-b border-zinc-200 bg-transparent py-4 pl-12 pr-4 text-base text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-zinc-600"
                />
                @if($search)
                    <button
                        type="button"
                        wire:click="resetSearch"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    >
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                @endif
            </div>

            {{-- Results --}}
            <div class="max-h-96 overflow-y-auto">
                @if(strlen($search) >= 2)
                    @if(count($this->results) > 0)
                        @php $globalIndex = 0; @endphp
                        <div class="py-2">
                            @foreach($this->results as $type => $items)
                                <div class="px-2">
                                    {{-- Type Header --}}
                                    <div class="px-2 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        {{ $this->getTypeLabel($type) }}
                                    </div>

                                    {{-- Items --}}
                                    <div class="space-y-0.5">
                                        @foreach($items as $item)
                                            <button
                                                type="button"
                                                wire:click="selectResult('{{ $type }}', '{{ $item['id'] }}')"
                                                @class([
                                                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition focus:outline-none',
                                                    'bg-zinc-100 dark:bg-zinc-800' => $selectedIndex === $globalIndex,
                                                    'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $selectedIndex !== $globalIndex,
                                                ])
                                            >
                                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                                    <flux:icon :icon="$item['icon']" class="size-4" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                                        {!! $this->highlightMatch($item['title'], $search) !!}
                                                    </div>
                                                    @if($item['subtitle'])
                                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                            {!! $this->highlightMatch($item['subtitle'], $search) !!}
                                                        </div>
                                                    @endif
                                                </div>
                                                <flux:icon icon="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                                            </button>
                                            @php $globalIndex++; @endphp
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- No Results --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon icon="magnifying-glass" class="size-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No results found') }}</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search terms') }}</p>
                        </div>
                    @endif
                @else
                    {{-- Empty State: Recent Searches or Hint --}}
                    <div class="py-6">
                        @if(count($recentSearches) > 0)
                            <div class="px-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        {{ __('Recent Searches') }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="clearRecentSearches"
                                        class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                    >
                                        {{ __('Clear') }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($recentSearches as $term)
                                        <button
                                            type="button"
                                            wire:click="useRecentSearch('{{ e($term) }}')"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-sm text-zinc-700 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                        >
                                            <flux:icon icon="clock" class="size-3.5" />
                                            {{ $term }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <flux:icon icon="command" class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Type at least 2 characters to search') }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-zinc-300 px-1.5 py-0.5 font-mono text-[10px] dark:border-zinc-600">&uarr;</kbd>
                        <kbd class="rounded border border-zinc-300 px-1.5 py-0.5 font-mono text-[10px] dark:border-zinc-600">&darr;</kbd>
                        {{ __('to navigate') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-zinc-300 px-1.5 py-0.5 font-mono text-[10px] dark:border-zinc-600">enter</kbd>
                        {{ __('to select') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-zinc-300 px-1.5 py-0.5 font-mono text-[10px] dark:border-zinc-600">esc</kbd>
                        {{ __('to close') }}
                    </span>
                </div>
                @if($this->currentBranch)
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Searching in') }} <span class="font-medium">{{ $this->currentBranch->name }}</span>
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
