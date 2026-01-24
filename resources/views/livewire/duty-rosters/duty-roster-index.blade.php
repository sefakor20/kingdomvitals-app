<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Duty Roster') }}</flux:heading>
            <flux:subheading>{{ __('Manage service assignments for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if (Route::has('duty-rosters.pools.index'))
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="cog-6-tooth">
                        {{ __('Settings') }}
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
                    {{ __('Auto-Generate') }}
                </flux:button>
            @endif
            @if (Route::has('duty-rosters.print'))
                <flux:button href="{{ route('duty-rosters.print', ['branch' => $branch, 'month' => $monthFilter]) }}" variant="ghost" icon="printer">
                    {{ __('Print') }}
                </flux:button>
            @endif
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Roster') }}
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

        <div class="flex flex-col gap-4 sm:flex-row">
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
        </div>
    </div>

    @if($this->dutyRosters->isEmpty())
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
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-roster" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Duty Roster') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:select wire:model="preacher_id" :label="__('Preacher (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="preacher_name" :label="__('Or External Preacher')" placeholder="{{ __('e.g., Rev. John Doe') }}" class="mt-2" />
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
                        <flux:input wire:model="liturgist_name" :label="__('Or External Liturgist')" placeholder="{{ __('e.g., Cat. Jane Smith') }}" class="mt-2" />
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
                <div class="grid grid-cols-2 gap-4">
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:select wire:model="preacher_id" :label="__('Preacher (Member)')">
                            <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                            @foreach($this->members as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="preacher_name" :label="__('Or External Preacher')" placeholder="{{ __('e.g., Rev. John Doe') }}" class="mt-2" />
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
                        <flux:input wire:model="liturgist_name" :label="__('Or External Liturgist')" placeholder="{{ __('e.g., Cat. Jane Smith') }}" class="mt-2" />
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
</section>
