<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Attendance') }}</flux:heading>
            <flux:subheading>{{ __('View attendance records for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->attendanceRecords->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Records') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="clipboard-document-check" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->attendanceStats['total']) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('attendance records') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="user-group" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->attendanceStats['members']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="user-plus" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->attendanceStats['visitors']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="calendar" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->attendanceStats['today']) }}</flux:heading>
        </div>
    </div>

    <!-- Quick Filters -->
    <div class="mb-4 flex flex-wrap gap-2">
        <flux:button
            variant="{{ $quickFilter === 'today' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('today')"
        >
            {{ __('Today') }}
        </flux:button>
        <flux:button
            variant="{{ $quickFilter === 'this_week' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('this_week')"
        >
            {{ __('This Week') }}
        </flux:button>
        <flux:button
            variant="{{ $quickFilter === 'this_month' ? 'primary' : 'ghost' }}"
            size="sm"
            wire:click="applyQuickFilter('this_month')"
        >
            {{ __('This Month') }}
        </flux:button>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by member or visitor name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="serviceFilter">
                <flux:select.option :value="null">{{ __('All Services') }}</flux:select.option>
                @foreach($this->services as $service)
                    <flux:select.option value="{{ $service->id }}">
                        {{ $service->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                <flux:select.option value="member">{{ __('Members') }}</flux:select.option>
                <flux:select.option value="visitor">{{ __('Visitors') }}</flux:select.option>
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="methodFilter">
                <flux:select.option value="">{{ __('All Methods') }}</flux:select.option>
                @foreach($this->checkInMethods as $method)
                    <flux:select.option value="{{ $method->value }}">
                        {{ ucfirst($method->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live="dateFrom" type="date" :label="__('From Date')" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live="dateTo" type="date" :label="__('To Date')" />
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    @if($this->attendanceRecords->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="clipboard-document-check" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No attendance records found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Attendance records will appear here when services are held.') }}
                @endif
            </flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Service') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Attendee') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Check-in') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Check-out') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Method') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->attendanceRecords as $attendance)
                        <tr wire:key="attendance-{{ $attendance->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $attendance->date?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($attendance->service)
                                    <a
                                        href="{{ route('services.show', [$branch, $attendance->service]) }}"
                                        class="text-sm text-zinc-900 hover:text-blue-600 hover:underline dark:text-zinc-100 dark:hover:text-blue-400"
                                        wire:navigate
                                    >
                                        {{ $attendance->service->name }}
                                    </a>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($attendance->member)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $attendance->member->fullName() }}" />
                                        <a
                                            href="{{ route('members.show', [$branch, $attendance->member]) }}"
                                            class="text-sm text-zinc-900 hover:text-blue-600 hover:underline dark:text-zinc-100 dark:hover:text-blue-400"
                                            wire:navigate
                                        >
                                            {{ $attendance->member->fullName() }}
                                        </a>
                                    </div>
                                @elseif($attendance->visitor)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $attendance->visitor->fullName() }}" class="ring-2 ring-purple-400" />
                                        <a
                                            href="{{ route('visitors.show', [$branch, $attendance->visitor]) }}"
                                            class="text-sm text-purple-600 hover:text-purple-700 hover:underline dark:text-purple-400 dark:hover:text-purple-300"
                                            wire:navigate
                                        >
                                            {{ $attendance->visitor->fullName() }}
                                        </a>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($attendance->member_id)
                                    <flux:badge color="green" size="sm">{{ __('Member') }}</flux:badge>
                                @elseif($attendance->visitor_id)
                                    <flux:badge color="purple" size="sm">{{ __('Visitor') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $attendance->check_in_time ? substr($attendance->check_in_time, 0, 5) : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $attendance->check_out_time ? substr($attendance->check_out_time, 0, 5) : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($attendance->check_in_method->value) {
                                        'manual' => 'zinc',
                                        'qr' => 'blue',
                                        'kiosk' => 'green',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($attendance->check_in_method->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                @can('delete', $attendance)
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete('{{ $attendance->id }}')" class="text-red-600 hover:text-red-700 dark:text-red-400" />
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-attendance" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Attendance Record') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this attendance record? This action cannot be undone.') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Record') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toast -->
    <x-toast on="attendance-deleted" type="success">
        {{ __('Attendance record deleted successfully.') }}
    </x-toast>
</section>
