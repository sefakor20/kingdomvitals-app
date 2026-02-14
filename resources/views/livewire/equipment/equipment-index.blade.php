<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Equipment') }}</flux:heading>
            <flux:subheading>{{ __('Manage equipment for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if ($this->equipment->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if ($this->canCreate && $this->canCreateWithinQuota)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Equipment') }}
                </flux:button>
            @elseif ($this->canCreate && ! $this->canCreateWithinQuota)
                <flux:button variant="ghost" disabled icon="lock-closed" class="cursor-not-allowed">
                    {{ __('Equipment Limit Reached') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Quota Warning Banner --}}
    @if ($this->showQuotaWarning && ! $this->equipmentQuota['unlimited'])
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Approaching Equipment Limit') }}
                    </flux:text>
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('You have :current of :max equipment items (:percent% used). Consider upgrading your plan for more capacity.', [
                            'current' => $this->equipmentQuota['current'],
                            'max' => $this->equipmentQuota['max'],
                            'percent' => $this->equipmentQuota['percent'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'equipment']) }}" variant="ghost" size="sm">
                    {{ __('Upgrade') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Quota Exceeded Banner --}}
    @if (! $this->canCreateWithinQuota && ! $this->equipmentQuota['unlimited'])
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-red-800 dark:text-red-200">
                        {{ __('Equipment Limit Reached') }}
                    </flux:text>
                    <flux:text class="text-sm text-red-700 dark:text-red-300">
                        {{ __('You have reached your limit of :max equipment items. Upgrade your plan to add more equipment.', [
                            'max' => $this->equipmentQuota['max'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'equipment']) }}" variant="primary" size="sm">
                    {{ __('Upgrade Now') }}
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Equipment') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="wrench-screwdriver" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->equipmentStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Available') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->equipmentStats['available']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Checked Out') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="arrow-right-circle" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->equipmentStats['checkedOut']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Value') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="banknotes" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->equipmentStats['totalValue'], 2) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, serial, manufacturer...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                @foreach($this->categories as $category)
                    <flux:select.option value="{{ $category->value }}">
                        {{ ucfirst($category->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="conditionFilter">
                <flux:select.option value="">{{ __('All Conditions') }}</flux:select.option>
                @foreach($this->conditions as $condition)
                    <flux:select.option value="{{ $condition->value }}">
                        {{ ucfirst(str_replace('_', ' ', $condition->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="availabilityFilter">
                <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                <flux:select.option value="available">{{ __('Available') }}</flux:select.option>
                <flux:select.option value="checked_out">{{ __('Checked Out') }}</flux:select.option>
                <flux:select.option value="out_of_service">{{ __('Out of Service') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if($this->hasActiveFilters)
        <div class="mb-4">
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" size="sm">
                {{ __('Clear Filters') }}
            </flux:button>
        </div>
    @endif

    @if($this->equipment->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="wrench-screwdriver" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No equipment found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first equipment.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Equipment') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Equipment') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Category') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Condition') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Location') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->equipment as $item)
                        <tr wire:key="equipment-{{ $item->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon icon="wrench-screwdriver" class="size-5 text-zinc-500" />
                                    </div>
                                    <div>
                                        <a href="{{ route('equipment.show', [$branch, $item]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                            {{ $item->name }}
                                        </a>
                                        @if($item->serial_number)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->serial_number }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="zinc" size="sm">
                                    {{ ucfirst($item->category->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($item->condition->value) {
                                        'excellent' => 'green',
                                        'good' => 'blue',
                                        'fair' => 'yellow',
                                        'poor' => 'orange',
                                        'out_of_service' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $item->condition->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $item->location ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($item->isOutOfService())
                                    <flux:badge color="red" size="sm">{{ __('Out of Service') }}</flux:badge>
                                @elseif($item->isCheckedOut())
                                    <div>
                                        <flux:badge color="yellow" size="sm">{{ __('Checked Out') }}</flux:badge>
                                        @if($item->activeCheckout?->member)
                                            <div class="mt-1 text-xs text-zinc-500">
                                                {{ $item->activeCheckout->member->fullName() }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <flux:badge color="green" size="sm">{{ __('Available') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item :href="route('equipment.show', [$branch, $item])" icon="eye" wire:navigate>
                                            {{ __('View Details') }}
                                        </flux:menu.item>

                                        @if($item->isAvailable())
                                            @can('checkout', $item)
                                                <flux:menu.item wire:click="openCheckoutModal('{{ $item->id }}')" icon="arrow-right-circle">
                                                    {{ __('Check Out') }}
                                                </flux:menu.item>
                                            @endcan
                                        @endif

                                        @if($item->isCheckedOut())
                                            @can('processReturn', $item)
                                                <flux:menu.item wire:click="openReturnModal('{{ $item->id }}')" icon="arrow-left-circle">
                                                    {{ __('Process Return') }}
                                                </flux:menu.item>
                                            @endcan
                                        @endif

                                        @can('update', $item)
                                            <flux:menu.item wire:click="edit('{{ $item->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $item)
                                            <flux:menu.item wire:click="confirmDelete('{{ $item->id }}')" icon="trash" variant="danger">
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

        @if($this->equipment->hasPages())
            <div class="mt-4">
                {{ $this->equipment->links() }}
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-equipment" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Equipment') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="name" :label="__('Name')" required placeholder="{{ __('e.g., Shure SM58 Microphone') }}" />
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="serial_number" :label="__('Serial Number')" />
                    <flux:input wire:model="model_number" :label="__('Model Number')" />
                    <flux:input wire:model="manufacturer" :label="__('Manufacturer')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="purchase_date" type="date" :label="__('Purchase Date')" />
                    <flux:input wire:model="purchase_price" type="number" step="0.01" min="0" :label="__('Purchase Price (:currency)', ['currency' => $this->currency->code()])" />
                    <flux:select wire:model="condition" :label="__('Condition')" required>
                        @foreach($this->conditions as $cond)
                            <flux:select.option value="{{ $cond->value }}">{{ ucfirst(str_replace('_', ' ', $cond->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="source_of_equipment" :label="__('Source of Equipment')" placeholder="{{ __('e.g., Purchased, Donated, Church fund') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="location" :label="__('Location')" placeholder="{{ __('e.g., Main Hall, Storage Room') }}" />
                    <flux:input wire:model="warranty_expiry" type="date" :label="__('Warranty Expiry')" />
                </div>

                <flux:input wire:model="next_maintenance_date" type="date" :label="__('Next Maintenance Date')" />

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Equipment') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-equipment" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Equipment') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="name" :label="__('Name')" required />
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="serial_number" :label="__('Serial Number')" />
                    <flux:input wire:model="model_number" :label="__('Model Number')" />
                    <flux:input wire:model="manufacturer" :label="__('Manufacturer')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="purchase_date" type="date" :label="__('Purchase Date')" />
                    <flux:input wire:model="purchase_price" type="number" step="0.01" min="0" :label="__('Purchase Price (:currency)', ['currency' => $this->currency->code()])" />
                    <flux:select wire:model="condition" :label="__('Condition')" required>
                        @foreach($this->conditions as $cond)
                            <flux:select.option value="{{ $cond->value }}">{{ ucfirst(str_replace('_', ' ', $cond->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="source_of_equipment" :label="__('Source of Equipment')" placeholder="{{ __('e.g., Purchased, Donated, Church fund') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="location" :label="__('Location')" />
                    <flux:input wire:model="warranty_expiry" type="date" :label="__('Warranty Expiry')" />
                </div>

                <flux:input wire:model="next_maintenance_date" type="date" :label="__('Next Maintenance Date')" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-equipment" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Equipment') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete ":name"? This action cannot be undone.', ['name' => $deletingEquipment?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Equipment') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Checkout Modal -->
    <flux:modal wire:model.self="showCheckoutModal" name="checkout-equipment" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Check Out Equipment') }}</flux:heading>

            @if($checkingOutEquipment)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $checkingOutEquipment->name }}</div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ ucfirst($checkingOutEquipment->category->value) }}</div>
                </div>
            @endif

            <form wire:submit="processCheckout" class="space-y-4">
                <flux:select wire:model="checkout_member_id" :label="__('Member')" required>
                    <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="checkout_date" type="datetime-local" :label="__('Checkout Date')" required />
                    <flux:input wire:model="expected_return_date" type="datetime-local" :label="__('Expected Return')" required />
                </div>

                <flux:textarea wire:model="checkout_purpose" :label="__('Purpose')" rows="2" placeholder="{{ __('Why is this equipment being checked out?') }}" />

                <flux:textarea wire:model="checkout_notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCheckout" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Check Out') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Return Modal -->
    <flux:modal wire:model.self="showReturnModal" name="return-equipment" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Process Return') }}</flux:heading>

            @if($returningEquipment)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $returningEquipment->name }}</div>
                    @if($returningEquipment->activeCheckout?->member)
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Checked out to: :name', ['name' => $returningEquipment->activeCheckout->member->fullName()]) }}
                        </div>
                    @endif
                </div>
            @endif

            <form wire:submit="processReturn" class="space-y-4">
                <flux:select wire:model="return_condition" :label="__('Condition on Return')" required>
                    @foreach($this->conditions as $cond)
                        <flux:select.option value="{{ $cond->value }}">{{ ucfirst(str_replace('_', ' ', $cond->value)) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="return_notes" :label="__('Return Notes')" rows="2" placeholder="{{ __('Any issues or observations?') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelReturn" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Process Return') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="equipment-created" type="success">
        {{ __('Equipment added successfully.') }}
    </x-toast>

    <x-toast on="equipment-updated" type="success">
        {{ __('Equipment updated successfully.') }}
    </x-toast>

    <x-toast on="equipment-deleted" type="success">
        {{ __('Equipment deleted successfully.') }}
    </x-toast>

    <x-toast on="equipment-checked-out" type="success">
        {{ __('Equipment checked out successfully.') }}
    </x-toast>

    <x-toast on="equipment-returned" type="success">
        {{ __('Equipment returned successfully.') }}
    </x-toast>
</section>
