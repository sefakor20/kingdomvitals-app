<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Visitors') }}</flux:heading>
            <flux:subheading>{{ __('Manage visitors for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->visitors->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Visitor') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Visitors') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="users" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->visitorStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('New Visitors') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="user-plus" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->visitorStats['new']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Converted') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="check-circle" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <flux:heading size="xl">{{ number_format($this->visitorStats['converted']) }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">({{ $this->visitorStats['conversionRate'] }}%)</flux:text>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pending Follow-ups') }}</flux:text>
                <div class="rounded-full {{ $this->visitorStats['pendingFollowUps'] > 0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                    <flux:icon icon="clock" class="size-4 {{ $this->visitorStats['pendingFollowUps'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->visitorStats['pendingFollowUps']) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, email, or phone...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->statuses as $statusOption)
                    <flux:select.option value="{{ $statusOption->value }}">
                        {{ str_replace('_', ' ', ucfirst($statusOption->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="convertedFilter">
                <flux:select.option value="">{{ __('All Visitors') }}</flux:select.option>
                <flux:select.option value="no">{{ __('Not Converted') }}</flux:select.option>
                <flux:select.option value="yes">{{ __('Converted') }}</flux:select.option>
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
        <div class="flex-1">
            <flux:select wire:model.live="assignedMemberFilter" :label="__('Assigned To')">
                <flux:select.option :value="null">{{ __('All') }}</flux:select.option>
                <flux:select.option value="unassigned">{{ __('Unassigned') }}</flux:select.option>
                @foreach($this->members as $member)
                    <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:select wire:model.live="sourceFilter" :label="__('Source')">
                <flux:select.option value="">{{ __('All Sources') }}</flux:select.option>
                @foreach($this->howDidYouHearOptions as $option)
                    <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    <!-- Bulk Actions Toolbar -->
    @if($this->hasSelection)
        <div class="mb-4 flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
            <flux:text class="font-medium text-blue-700 dark:text-blue-300">
                {{ trans_choice(':count visitor selected|:count visitors selected', $this->selectedCount, ['count' => $this->selectedCount]) }}
            </flux:text>
            <flux:button variant="ghost" size="sm" wire:click="clearSelection">
                {{ __('Clear') }}
            </flux:button>
            <div class="flex-1"></div>
            @if($this->canBulkUpdate)
                <flux:button variant="ghost" size="sm" icon="user" wire:click="openBulkAssignModal">
                    {{ __('Assign') }}
                </flux:button>
                <flux:button variant="ghost" size="sm" icon="tag" wire:click="openBulkStatusModal">
                    {{ __('Change Status') }}
                </flux:button>
            @endif
            @if($this->canBulkDelete)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    @endif

    @if($this->visitors->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="user-plus" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No visitors found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $statusFilter || $convertedFilter)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first visitor.') }}
                @endif
            </flux:text>
            @if(!$search && !$statusFilter && !$convertedFilter && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Visitor') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="w-12 px-4 py-3">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Contact') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Visit Date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Assigned To') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->visitors as $visitor)
                        <tr wire:key="visitor-{{ $visitor->id }}" class="{{ in_array($visitor->id, $selectedVisitors) ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="w-12 px-4 py-4">
                                <flux:checkbox wire:model.live="selectedVisitors" value="{{ $visitor->id }}" />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $visitor->fullName() }}" />
                                    <div>
                                        <a href="{{ route('visitors.show', [$branch, $visitor]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                            {{ $visitor->fullName() }}
                                        </a>
                                        @if($visitor->how_did_you_hear)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $visitor->how_did_you_hear }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $visitor->email ?? '-' }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $visitor->phone ?? '-' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($visitor->status->value) {
                                        'new' => 'blue',
                                        'followed_up' => 'yellow',
                                        'returning' => 'green',
                                        'converted' => 'purple',
                                        'not_interested' => 'zinc',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ str_replace('_', ' ', ucfirst($visitor->status->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $visitor->visit_date?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($visitor->assignedMember)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" name="{{ $visitor->assignedMember->fullName() }}" />
                                        <span class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $visitor->assignedMember->fullName() }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @if(!$visitor->is_converted)
                                            @can('update', $visitor)
                                                <flux:menu.item wire:click="openConvertModal('{{ $visitor->id }}')" icon="arrow-right-circle">
                                                    {{ __('Convert to Member') }}
                                                </flux:menu.item>
                                            @endcan
                                        @else
                                            @if($visitor->convertedMember)
                                                <flux:menu.item href="{{ route('members.show', [$branch, $visitor->convertedMember]) }}" icon="check-circle" wire:navigate>
                                                    {{ __('View Member') }}
                                                </flux:menu.item>
                                            @endif
                                        @endif

                                        @can('update', $visitor)
                                            <flux:menu.item wire:click="edit('{{ $visitor->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $visitor)
                                            <flux:menu.item wire:click="confirmDelete('{{ $visitor->id }}')" icon="trash" variant="danger">
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
    <flux:modal wire:model.self="showCreateModal" name="create-visitor" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Visitor') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" :label="__('First Name')" required />
                    <flux:input wire:model="last_name" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="visit_date" type="date" :label="__('Visit Date')" required />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ str_replace('_', ' ', ucfirst($statusOption->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="how_did_you_hear" :label="__('How did you hear about us?')">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($this->howDidYouHearOptions as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="assigned_to" :label="__('Assign to Member (for follow-up)')">
                    <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Visitor') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-visitor" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Visitor') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" :label="__('First Name')" required />
                    <flux:input wire:model="last_name" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="visit_date" type="date" :label="__('Visit Date')" required />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ str_replace('_', ' ', ucfirst($statusOption->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="how_did_you_hear" :label="__('How did you hear about us?')">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($this->howDidYouHearOptions as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="assigned_to" :label="__('Assign to Member (for follow-up)')">
                    <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-visitor" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Visitor') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $deletingVisitor?->fullName() ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Visitor') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Convert to Member Modal -->
    <flux:modal wire:model.self="showConvertModal" name="convert-visitor" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Convert to Member') }}</flux:heading>

            <flux:text>
                {{ __('Link :name to an existing member to mark them as converted.', ['name' => $convertingVisitor?->fullName() ?? '']) }}
            </flux:text>

            <form wire:submit="convert" class="space-y-4">
                <flux:select wire:model="convertToMemberId" :label="__('Select Member')" required>
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('convertToMemberId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelConvert" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Convert to Member') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Bulk Delete Confirmation Modal -->
    <flux:modal wire:model.self="showBulkDeleteModal" name="bulk-delete-visitors" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Visitors') }}</flux:heading>

            <flux:text>
                {{ trans_choice('Are you sure you want to delete :count visitor? This action cannot be undone.|Are you sure you want to delete :count visitors? This action cannot be undone.', $this->selectedCount, ['count' => $this->selectedCount]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelBulkDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">
                    {{ __('Delete Visitors') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Bulk Assign Modal -->
    <flux:modal wire:model.self="showBulkAssignModal" name="bulk-assign-visitors" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Assign Visitors') }}</flux:heading>

            <flux:text>
                {{ trans_choice('Assign :count visitor to a member for follow-up.|Assign :count visitors to a member for follow-up.', $this->selectedCount, ['count' => $this->selectedCount]) }}
            </flux:text>

            <form wire:submit="bulkAssign" class="space-y-4">
                <flux:select wire:model="bulkAssignTo" :label="__('Assign To')">
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    <flux:select.option value="unassign">{{ __('Unassign (remove current assignment)') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('bulkAssignTo') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelBulkAssign" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Assign') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Bulk Status Change Modal -->
    <flux:modal wire:model.self="showBulkStatusModal" name="bulk-status-visitors" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Change Status') }}</flux:heading>

            <flux:text>
                {{ trans_choice('Change the status of :count visitor.|Change the status of :count visitors.', $this->selectedCount, ['count' => $this->selectedCount]) }}
            </flux:text>

            <form wire:submit="bulkChangeStatus" class="space-y-4">
                <flux:select wire:model="bulkStatusValue" :label="__('New Status')" required>
                    <flux:select.option value="">{{ __('Select a status...') }}</flux:select.option>
                    @foreach($this->statuses as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">
                            {{ str_replace('_', ' ', ucfirst($statusOption->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('bulkStatusValue') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelBulkStatus" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Change Status') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="visitor-created" type="success">
        {{ __('Visitor added successfully.') }}
    </x-toast>

    <x-toast on="visitor-updated" type="success">
        {{ __('Visitor updated successfully.') }}
    </x-toast>

    <x-toast on="visitor-deleted" type="success">
        {{ __('Visitor deleted successfully.') }}
    </x-toast>

    <x-toast on="visitor-converted" type="success">
        {{ __('Visitor converted to member successfully.') }}
    </x-toast>

    <x-toast on="visitors-bulk-deleted" type="success">
        {{ __('Visitors deleted successfully.') }}
    </x-toast>

    <x-toast on="visitors-bulk-assigned" type="success">
        {{ __('Visitors assigned successfully.') }}
    </x-toast>

    <x-toast on="visitors-bulk-status-changed" type="success">
        {{ __('Visitor statuses updated successfully.') }}
    </x-toast>
</section>
