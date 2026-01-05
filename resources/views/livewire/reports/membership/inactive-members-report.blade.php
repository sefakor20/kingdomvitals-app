<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Inactive Members Report') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Members inactive for :days+ days', ['days' => $inactivityThreshold]) }}
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

    <!-- Threshold Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-4">
            <flux:text class="font-medium">{{ __('Inactivity Threshold:') }}</flux:text>
            <div class="flex gap-2">
                <flux:button
                    :variant="$inactivityThreshold === 14 ? 'primary' : 'ghost'"
                    wire:click="setThreshold(14)"
                    size="sm"
                >
                    {{ __('14 Days') }}
                </flux:button>
                <flux:button
                    :variant="$inactivityThreshold === 30 ? 'primary' : 'ghost'"
                    wire:click="setThreshold(30)"
                    size="sm"
                >
                    {{ __('30 Days') }}
                </flux:button>
                <flux:button
                    :variant="$inactivityThreshold === 60 ? 'primary' : 'ghost'"
                    wire:click="setThreshold(60)"
                    size="sm"
                >
                    {{ __('60 Days') }}
                </flux:button>
                <flux:button
                    :variant="$inactivityThreshold === 90 ? 'primary' : 'ghost'"
                    wire:click="setThreshold(90)"
                    size="sm"
                >
                    {{ __('90 Days') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Inactive Members') }}</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->totalInactiveMembers) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members need follow-up') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Active Members') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->totalActiveMembers) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('in this branch') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Inactivity Rate') }}</div>
            <div class="text-2xl font-bold {{ $this->inactivityRate > 20 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $this->inactivityRate }}%
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('of active members') }}</div>
        </div>
    </div>

    <!-- Callout -->
    @if($this->totalInactiveMembers > 0)
        <flux:callout variant="warning" class="mb-6">
            <flux:callout.heading>{{ __('Follow-up Recommended') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('These members have not attended any service in the last :days days. Consider reaching out to check on them.', ['days' => $inactivityThreshold]) }}
            </flux:callout.text>
        </flux:callout>
    @endif

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
                        <th wire:click="sortBy('last_attendance')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                {{ __('Last Attendance') }}
                                @if($sortBy === 'last_attendance')
                                    <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3" />
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Days Inactive') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('City') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->members as $member)
                        @php
                            $lastAttendance = $member->last_attendance ? \Carbon\Carbon::parse($member->last_attendance) : null;
                            $daysInactive = $lastAttendance ? $lastAttendance->diffInDays(now()) : null;
                        @endphp
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
                                @if($lastAttendance)
                                    {{ $lastAttendance->format('M d, Y') }}
                                @else
                                    <span class="text-red-600 dark:text-red-400">{{ __('Never attended') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if($daysInactive !== null)
                                    <flux:badge
                                        :color="match(true) {
                                            $daysInactive > 90 => 'red',
                                            $daysInactive > 60 => 'yellow',
                                            default => 'zinc',
                                        }"
                                        size="sm"
                                    >
                                        {{ $daysInactive }} {{ __('days') }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('N/A') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $member->city ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No inactive members found. Great news!') }}
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
