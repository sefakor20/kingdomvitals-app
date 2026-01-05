<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('reports.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Absent Members Report') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Members absent for :weeks+ weeks', ['weeks' => $weeks]) }}
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

    <!-- Week Filters -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-4">
            <flux:text class="font-medium">{{ __('Absence Threshold:') }}</flux:text>
            <div class="flex gap-2">
                <flux:button
                    :variant="$weeks === 1 ? 'primary' : 'ghost'"
                    wire:click="setWeeks(1)"
                    size="sm"
                >
                    {{ __('1 Week') }}
                </flux:button>
                <flux:button
                    :variant="$weeks === 2 ? 'primary' : 'ghost'"
                    wire:click="setWeeks(2)"
                    size="sm"
                >
                    {{ __('2 Weeks') }}
                </flux:button>
                <flux:button
                    :variant="$weeks === 4 ? 'primary' : 'ghost'"
                    wire:click="setWeeks(4)"
                    size="sm"
                >
                    {{ __('4 Weeks') }}
                </flux:button>
                <flux:button
                    :variant="$weeks === 8 ? 'primary' : 'ghost'"
                    wire:click="setWeeks(8)"
                    size="sm"
                >
                    {{ __('8 Weeks') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Absent Members') }}</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->summaryStats['absent_count']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('need follow-up') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Total Active') }}</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->summaryStats['total_active']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Absence Rate') }}</div>
            <div class="text-2xl font-bold {{ $this->summaryStats['absence_rate'] > 20 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $this->summaryStats['absence_rate'] }}%
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('of active members') }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Never Attended') }}</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($this->summaryStats['never_attended']) }}</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('members') }}</div>
        </div>
    </div>

    <!-- Breakdown by Duration -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Absence Duration Breakdown') }}</flux:heading>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach($this->absenceBreakdown as $label => $count)
                <div class="text-center">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($count) }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Callout -->
    @if($this->summaryStats['absent_count'] > 0)
        <flux:callout variant="warning" class="mb-6">
            <flux:callout.heading>{{ __('Follow-up Recommended') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('These members have not attended any service in the last :weeks weeks. Consider reaching out to check on them.', ['weeks' => $weeks]) }}
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
                            {{ __('Weeks Absent') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('City') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->absentMembers as $member)
                        @php
                            $lastAttendance = $member->last_attendance ? \Carbon\Carbon::parse($member->last_attendance) : null;
                            $weeksAbsent = $lastAttendance ? $lastAttendance->diffInWeeks(now()) : null;
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
                                @if($weeksAbsent !== null)
                                    <flux:badge
                                        :color="match(true) {
                                            $weeksAbsent >= 8 => 'red',
                                            $weeksAbsent >= 4 => 'yellow',
                                            default => 'zinc',
                                        }"
                                        size="sm"
                                    >
                                        {{ $weeksAbsent }} {{ __('weeks') }}
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
                                {{ __('No absent members found. Great attendance!') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->absentMembers->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $this->absentMembers->links() }}
            </div>
        @endif
    </div>
</section>
