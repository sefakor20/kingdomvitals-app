<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Live Check-in') }}</flux:heading>
            <flux:subheading>{{ $service->name }} - {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('services.show', [$branch, $service])" icon="arrow-left" wire:navigate>
            {{ __('Back to Service') }}
        </flux:button>
    </div>

    <!-- Stats -->
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="clipboard-document-check" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="user-group" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['members']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="user-plus" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->todayStats['visitors']) }}</flux:heading>
        </div>
    </div>

    <!-- Search Input -->
    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.200ms="searchQuery"
            placeholder="{{ __('Search member or visitor name...') }}"
            icon="magnifying-glass"
            class="!py-4 !text-lg"
        />
        @if(strlen($searchQuery) > 0 && strlen($searchQuery) < 2)
            <flux:text class="mt-2 text-sm text-zinc-500">{{ __('Type at least 2 characters to search...') }}</flux:text>
        @endif
    </div>

    <!-- Search Results Grid -->
    @if($this->searchResults->isNotEmpty())
        <div class="mb-8">
            <flux:text class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ __('Select to check in:') }}
            </flux:text>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach($this->searchResults as $result)
                    <button
                        wire:key="result-{{ $result['type'] }}-{{ $result['id'] }}"
                        wire:click="checkIn('{{ $result['id'] }}', '{{ $result['type'] }}')"
                        @if($result['already_checked_in']) disabled @endif
                        class="flex flex-col items-center rounded-xl border-2 p-4 text-center transition-all
                            {{ $result['already_checked_in']
                                ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-60 dark:border-zinc-700 dark:bg-zinc-800'
                                : ($result['type'] === 'member'
                                    ? 'border-green-200 bg-green-50 hover:border-green-400 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/30 dark:hover:border-green-600'
                                    : 'border-purple-200 bg-purple-50 hover:border-purple-400 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/30 dark:hover:border-purple-600')
                            }}"
                    >
                        <flux:avatar
                            size="lg"
                            name="{{ $result['name'] }}"
                            class="{{ $result['type'] === 'visitor' ? 'ring-2 ring-purple-400' : '' }}"
                        />
                        <span class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $result['name'] }}
                        </span>
                        <span class="text-xs {{ $result['type'] === 'member' ? 'text-green-600 dark:text-green-400' : 'text-purple-600 dark:text-purple-400' }}">
                            {{ $result['type'] === 'member' ? __('Member') : __('Visitor') }}
                        </span>
                        @if($result['already_checked_in'])
                            <flux:badge color="zinc" size="sm" class="mt-1">
                                {{ __('Already checked in') }}
                            </flux:badge>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @elseif(strlen($searchQuery) >= 2)
        <div class="mb-8 flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon icon="magnifying-glass" class="size-8 text-zinc-400" />
            <flux:text class="mt-2 text-zinc-500">{{ __('No members or visitors found matching ":search"', ['search' => $searchQuery]) }}</flux:text>
        </div>
    @endif

    <!-- Empty State / Instructions -->
    @if(strlen($searchQuery) < 2 && $this->recentCheckIns->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 py-12 dark:border-zinc-600">
            <flux:icon icon="hand-raised" class="size-16 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('Ready for Check-ins') }}</flux:heading>
            <flux:text class="mt-2 text-center text-zinc-500">
                {{ __('Start typing a name to find members or visitors to check in.') }}
            </flux:text>
        </div>
    @endif

    <!-- Recent Check-ins -->
    @if($this->recentCheckIns->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Recent Check-ins') }}</flux:heading>
            <div class="space-y-3">
                @foreach($this->recentCheckIns as $checkIn)
                    <div wire:key="recent-{{ $loop->index }}" class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                            <flux:icon icon="check" class="size-4 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="flex-1">
                            <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $checkIn['name'] }}
                            </flux:text>
                        </div>
                        <flux:badge
                            :color="$checkIn['type'] === 'member' ? 'green' : 'purple'"
                            size="sm"
                        >
                            {{ $checkIn['type'] === 'member' ? __('Member') : __('Visitor') }}
                        </flux:badge>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $checkIn['time'] }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Success Toast -->
    <x-toast on="check-in-success" type="success">
        {{ __('Checked in successfully!') }}
    </x-toast>

    <!-- Already Checked In Toast -->
    <x-toast on="already-checked-in" type="warning">
        {{ __('This person is already checked in for today.') }}
    </x-toast>
</section>
