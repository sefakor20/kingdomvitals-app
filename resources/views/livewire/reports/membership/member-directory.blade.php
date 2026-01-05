<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Member Directory') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __(':count total members', ['count' => number_format($this->totalCount)]) }}
                </flux:text>
            </div>
        </div>

        <!-- Export Dropdown -->
        <flux:dropdown>
            <flux:button variant="primary" icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="exportCsv" icon="document-text">
                    {{ __('Export CSV') }}
                </flux:menu.item>
                <flux:menu.item wire:click="exportExcel" icon="table-cells">
                    {{ __('Export Excel') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    <!-- Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[200px] flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search by name, email, phone...') }}"
                />
            </div>
            <div class="w-40">
                <flux:select wire:model.live="status">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    @foreach($this->statuses as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-36">
                <flux:select wire:model.live="gender">
                    <flux:select.option value="">{{ __('All Genders') }}</flux:select.option>
                    @foreach($this->genders as $genderOption)
                        <flux:select.option value="{{ $genderOption->value }}">{{ ucfirst($genderOption->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-44">
                <flux:select wire:model.live="cluster">
                    <flux:select.option value="">{{ __('All Clusters') }}</flux:select.option>
                    @foreach($this->clusters as $clusterOption)
                        <flux:select.option value="{{ $clusterOption->id }}">{{ $clusterOption->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if($this->hasActiveFilters)
                <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Results Table -->
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th wire:click="sortBy('first_name')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Name') }}
                                @if($sortBy === 'first_name')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Contact') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Gender') }}
                        </th>
                        <th wire:click="sortBy('status')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Status') }}
                                @if($sortBy === 'status')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('joined_at')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Joined') }}
                                @if($sortBy === 'joined_at')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('City') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->members as $member)
                        <tr wire:key="member-{{ $member->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($member->photo_url)
                                        <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-8 rounded-full object-cover" />
                                    @else
                                        <flux:avatar size="sm" name="{{ $member->fullName() }}" />
                                    @endif
                                    <a href="{{ route('members.show', [$branch, $member]) }}" wire:navigate class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400">
                                        {{ $member->fullName() }}
                                    </a>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div>{{ $member->email ?? '-' }}</div>
                                <div>{{ $member->phone ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->gender ? ucfirst($member->gender->value) : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <flux:badge
                                    :color="match($member->status->value) {
                                        'active' => 'green',
                                        'inactive' => 'zinc',
                                        'pending' => 'yellow',
                                        'deceased' => 'red',
                                        'transferred' => 'blue',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($member->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->city ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No members found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->members->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->members->links() }}
            </div>
        @endif
    </div>
</section>
