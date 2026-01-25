<section class="w-full">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Duty Roster') }}</flux:heading>
            <flux:subheading>{{ __('Manage service assignments for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if (Route::has('duty-rosters.pools.index'))
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="cog-6-tooth">
                        <span class="hidden sm:inline">{{ __('Settings') }}</span>
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item href="{{ route('duty-rosters.pools.index', $branch) }}" icon="user-group" wire:navigate>
                            {{ __('Personnel Pools') }}
                        </flux:menu.item>
                        @if (Route::has('duty-rosters.availability.index'))
                            <flux:menu.item href="{{ route('duty-rosters.availability.index', $branch) }}" icon="calendar" wire:navigate>
                                {{ __('Member Availability') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif
            @if (Route::has('duty-rosters.generate'))
                <flux:button href="{{ route('duty-rosters.generate', $branch) }}" variant="ghost" icon="sparkles" wire:navigate>
                    <span class="hidden sm:inline">{{ __('Auto-Generate') }}</span>
                </flux:button>
            @endif
            @if (Route::has('duty-rosters.print'))
                <flux:button wire:click="openPrintModal" variant="ghost" icon="printer">
                    <span class="hidden sm:inline">{{ __('Print') }}</span>
                </flux:button>
            @endif
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    <span class="hidden sm:inline">{{ __('Add Roster') }}</span>
                    <span class="sm:hidden">{{ __('Add') }}</span>
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Month Navigation and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" icon="chevron-left" wire:click="previousMonth" />
            <flux:heading size="lg">
                {{ \Carbon\Carbon::parse($monthFilter . '-01')->format('F Y') }}
            </flux:heading>
            <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="nextMonth" />
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="w-full sm:w-64">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by theme, preacher...') }}" icon="magnifying-glass" />
            </div>
            <div class="w-full sm:w-40">
                <flux:select wire:model.live="statusFilter">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    @foreach($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:button
                    variant="{{ $viewMode === 'table' ? 'primary' : 'ghost' }}"
                    size="sm"
                    icon="list-bullet"
                    wire:click="setViewMode('table')"
                    class="rounded-r-none"
                />
                <flux:button
                    variant="{{ $viewMode === 'calendar' ? 'primary' : 'ghost' }}"
                    size="sm"
                    icon="calendar-days"
                    wire:click="setViewMode('calendar')"
                    class="rounded-l-none"
                />
            </div>
        </div>
    </div>

    @if($this->dutyRosters->isEmpty() && $viewMode === 'table')
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="calendar-days" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No duty rosters found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $statusFilter)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first duty roster for this month.') }}
                @endif
            </flux:text>
            @if(!$search && !$statusFilter && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Roster') }}
                </flux:button>
            @endif
        </div>
    @else
        @if($viewMode === 'table')
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Date') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Theme') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Preacher') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Liturgist') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Status') }}
                            </th>
                            <th scope="col" class="relative px-4 py-3">
                                <span class="sr-only">{{ __('Actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($this->dutyRosters as $roster)
                            <tr wire:key="roster-{{ $roster->id }}">
                                <td class="whitespace-nowrap px-4 py-4">
                                    <div>
                                        @if (Route::has('duty-rosters.show'))
                                            <a href="{{ route('duty-rosters.show', [$branch, $roster]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                                {{ $roster->service_date->format('D, M j, Y') }}
                                            </a>
                                        @else
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $roster->service_date->format('D, M j, Y') }}
                                            </span>
                                        @endif
                                        @if($roster->service)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $roster->service->name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="max-w-xs truncate text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $roster->theme ?? '-' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $roster->preacher_display_name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $roster->liturgist_display_name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <flux:badge
                                        :color="$roster->status->color()"
                                        size="sm"
                                    >
                                        {{ $roster->status->label() }}
                                    </flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                        <flux:menu>
                                            @if (Route::has('duty-rosters.show'))
                                                <flux:menu.item href="{{ route('duty-rosters.show', [$branch, $roster]) }}" icon="eye" wire:navigate>
                                                    {{ __('View Details') }}
                                                </flux:menu.item>
                                            @endif
                                            @can('update', $roster)
                                                <flux:menu.item wire:click="edit('{{ $roster->id }}')" icon="pencil">
                                                    {{ __('Edit') }}
                                                </flux:menu.item>
                                            @endcan
                                            @can('publish', $roster)
                                                <flux:menu.item wire:click="togglePublish('{{ $roster->id }}')" icon="{{ $roster->is_published ? 'eye-slash' : 'check-circle' }}">
                                                    {{ $roster->is_published ? __('Unpublish') : __('Publish') }}
                                                </flux:menu.item>
                                            @endcan
                                            @can('delete', $roster)
                                                <flux:menu.item wire:click="confirmDelete('{{ $roster->id }}')" icon="trash" variant="danger">
                                                    {{ __('Delete') }}
                                                </flux:menu.item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Calendar View -->
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <!-- Day headers -->
                <div class="grid grid-cols-7 bg-zinc-50 dark:bg-zinc-800">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="border-b border-zinc-200 px-2 py-2 text-center text-xs font-medium uppercase text-zinc-500 dark:border-zinc-700">
                            {{ __($day) }}
                        </div>
                    @endforeach
                </div>

                <!-- Calendar grid -->
                <div class="bg-white dark:bg-zinc-900">
                    @foreach($this->calendarData as $week)
                        <div class="grid grid-cols-7">
                            @foreach($week as $day)
                                <div
                                    wire:key="day-{{ $day['date']->format('Y-m-d') }}"
                                    class="min-h-20 border-b border-r border-zinc-200 p-1 sm:min-h-28 dark:border-zinc-700 {{ !$day['isCurrentMonth'] ? 'bg-zinc-50 dark:bg-zinc-800/50' : '' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="{{ $day['isToday'] ? 'flex size-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white' : 'text-sm ' . ($day['isCurrentMonth'] ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400') }}">
                                            {{ $day['date']->day }}
                                        </span>
                                        @if($day['roster'])
                                            @can('update', $day['roster'])
                                                <flux:button variant="ghost" size="xs" icon="pencil" wire:click="edit('{{ $day['roster']->id }}')" class="size-5" />
                                            @endcan
                                        @elseif($day['isCurrentMonth'] && $this->canCreate)
                                            <flux:button variant="ghost" size="xs" icon="plus" wire:click="createForDate('{{ $day['date']->format('Y-m-d') }}')" class="size-5 opacity-0 transition-opacity group-hover:opacity-100 hover:!opacity-100" />
                                        @endif
                                    </div>

                                    @if($day['roster'])
                                        <div class="mt-1 space-y-1">
                                            <flux:badge :color="$day['roster']->status->color()" size="sm" class="w-full justify-center text-xs">
                                                {{ $day['roster']->status->label() }}
                                            </flux:badge>
                                            @if($day['roster']->preacher_display_name)
                                                <div class="hidden truncate text-xs text-zinc-600 sm:block dark:text-zinc-400" title="{{ $day['roster']->preacher_display_name }}">
                                                    {{ $day['roster']->preacher_display_name }}
                                                </div>
                                            @endif
                                            @if($day['roster']->theme)
                                                <div class="hidden truncate text-xs text-zinc-500 sm:block" title="{{ $day['roster']->theme }}">
                                                    {{ Str::limit($day['roster']->theme, 18) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-roster" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Duty Roster') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="service_date" type="date" :label="__('Service Date')" required />
                    <flux:select wire:model="service_id" :label="__('Service Type')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->services as $service)
                            <flux:select.option value="{{ $service->id }}">
                                {{ $service->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="theme" :label="__('Theme')" placeholder="{{ __('e.g., REJOICE, THE LORD DELIVERS') }}" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model="preacher_id" :label="__('Preacher (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="preacher_name" :label="__('Or External Preacher')" placeholder="{{ __('e.g., Rev. John Doe') }}" class="mt-4" />
                    </div>
                    <div>
                        <flux:select wire:model="liturgist_id" :label="__('Liturgist (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="liturgist_name" :label="__('Or External Liturgist')" placeholder="{{ __('e.g., Cat. Jane Smith') }}" class="mt-4" />
                    </div>
                </div>

                <!-- Hymn Numbers -->
                <div>
                    <flux:text class="mb-2 text-sm font-medium">{{ __('Hymn Numbers') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($hymn_numbers as $index => $hymn)
                            <div class="flex items-center gap-1" wire:key="hymn-{{ $index }}">
                                <flux:input wire:model="hymn_numbers.{{ $index }}" type="number" min="1" class="w-20" />
                                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeHymn({{ $index }})" />
                            </div>
                        @endforeach
                        <flux:button variant="ghost" size="sm" icon="plus" wire:click="addHymn">
                            {{ __('Add Hymn') }}
                        </flux:button>
                    </div>
                </div>

                <flux:textarea wire:model="remarks" :label="__('Remarks')" rows="2" placeholder="{{ __('e.g., Communion Sunday for Intercessory Prayers') }}" />

                <flux:select wire:model="status" :label="__('Status')">
                    @foreach($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Roster') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-roster" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Duty Roster') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="service_date" type="date" :label="__('Service Date')" required />
                    <flux:select wire:model="service_id" :label="__('Service Type')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->services as $service)
                            <flux:select.option value="{{ $service->id }}">
                                {{ $service->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="theme" :label="__('Theme')" placeholder="{{ __('e.g., REJOICE, THE LORD DELIVERS') }}" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model="preacher_id" :label="__('Preacher (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="preacher_name" :label="__('Or External Preacher')" placeholder="{{ __('e.g., Rev. John Doe') }}" class="mt-4" />
                    </div>
                    <div>
                        <flux:select wire:model="liturgist_id" :label="__('Liturgist (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="liturgist_name" :label="__('Or External Liturgist')" placeholder="{{ __('e.g., Cat. Jane Smith') }}" class="mt-4" />
                    </div>
                </div>

                <!-- Hymn Numbers -->
                <div>
                    <flux:text class="mb-2 text-sm font-medium">{{ __('Hymn Numbers') }}</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach($hymn_numbers as $index => $hymn)
                            <div class="flex items-center gap-1" wire:key="edit-hymn-{{ $index }}">
                                <flux:input wire:model="hymn_numbers.{{ $index }}" type="number" min="1" class="w-20" />
                                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeHymn({{ $index }})" />
                            </div>
                        @endforeach
                        <flux:button variant="ghost" size="sm" icon="plus" wire:click="addHymn">
                            {{ __('Add Hymn') }}
                        </flux:button>
                    </div>
                </div>

                <flux:textarea wire:model="remarks" :label="__('Remarks')" rows="2" placeholder="{{ __('e.g., Communion Sunday for Intercessory Prayers') }}" />

                <flux:select wire:model="status" :label="__('Status')">
                    @foreach($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelEdit" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-roster" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Duty Roster') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete the duty roster for :date? This will also delete all associated scriptures and group assignments. This action cannot be undone.', ['date' => $deletingRoster?->service_date?->format('M j, Y') ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Roster') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="roster-created" type="success">
        {{ __('Duty roster added successfully.') }}
    </x-toast>

    <x-toast on="roster-updated" type="success">
        {{ __('Duty roster updated successfully.') }}
    </x-toast>

    <x-toast on="roster-deleted" type="success">
        {{ __('Duty roster deleted successfully.') }}
    </x-toast>

    <x-toast on="roster-published" type="success">
        {{ __('Duty roster published successfully.') }}
    </x-toast>

    <x-toast on="roster-unpublished" type="success">
        {{ __('Duty roster unpublished successfully.') }}
    </x-toast>

    <!-- Print Date Range Modal -->
    @if (Route::has('duty-rosters.print'))
        <flux:modal wire:model.self="showPrintModal" name="print-roster" class="w-full max-w-md">
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('Print Duty Roster') }}</flux:heading>

                <flux:text class="text-zinc-500">
                    {{ __('Select the date range for the duty roster you want to print.') }}
                </flux:text>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model="printStartDate"
                        type="date"
                        :label="__('Start Date')"
                    />
                    <flux:input
                        wire:model="printEndDate"
                        type="date"
                        :label="__('End Date')"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="closePrintModal">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button
                        variant="primary"
                        icon="printer"
                        x-on:click="window.open('{{ route('duty-rosters.print', ['branch' => $branch]) }}' + '?start=' + $wire.printStartDate + '&end=' + $wire.printEndDate, '_blank')"
                    >
                        {{ __('Print') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</section>
