<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('equipment.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1">
                            <flux:icon.check class="size-4" />
                            {{ __('Save') }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
            @if($this->canDelete && !$editing)
                <flux:button variant="danger" wire:click="confirmDelete" icon="trash">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Equipment Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-16 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.wrench-screwdriver class="size-8 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="name" placeholder="{{ __('Equipment Name') }}" class="w-64" />
                            @error('name') <div class="w-full text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <flux:heading size="xl">{{ $equipment->name }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($editing)
                            <flux:select wire:model="category" class="w-40">
                                @foreach($this->categories as $cat)
                                    <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <span>{{ ucfirst($equipment->category->value) }}</span>
                            @if($equipment->manufacturer)
                                <span>&bull;</span>
                                <span>{{ $equipment->manufacturer }}</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($editing)
                    <flux:select wire:model="condition" class="w-36">
                        @foreach($this->conditions as $cond)
                            <flux:select.option value="{{ $cond->value }}">{{ ucfirst(str_replace('_', ' ', $cond->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:badge
                        :color="match($equipment->condition->value) {
                            'excellent' => 'green',
                            'good' => 'blue',
                            'fair' => 'yellow',
                            'poor' => 'orange',
                            'out_of_service' => 'red',
                            default => 'zinc',
                        }"
                        size="lg"
                    >
                        {{ ucfirst(str_replace('_', ' ', $equipment->condition->value)) }}
                    </flux:badge>

                    @if($equipment->isCheckedOut())
                        <flux:badge color="purple" size="lg">{{ __('Checked Out') }}</flux:badge>
                    @elseif($equipment->isAvailable())
                        <flux:badge color="green" size="lg">{{ __('Available') }}</flux:badge>
                    @endif
                @endif
            </div>
        </div>

        <!-- Action buttons when not editing -->
        @if(!$editing)
            <div class="mt-4 flex gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if($this->canCheckout)
                    @if($equipment->isAvailable())
                        <flux:button variant="primary" wire:click="openCheckoutModal" icon="arrow-right-start-on-rectangle">
                            {{ __('Check Out') }}
                        </flux:button>
                    @elseif($this->activeCheckout)
                        <flux:button variant="primary" wire:click="openReturnModal" icon="arrow-left-end-on-rectangle">
                            {{ __('Return') }}
                        </flux:button>
                    @endif
                @endif

                @if($this->canManageMaintenance)
                    <flux:button variant="ghost" wire:click="openMaintenanceModal" icon="wrench">
                        {{ __('Schedule Maintenance') }}
                    </flux:button>
                @endif
            </div>
        @endif
    </div>

    <!-- Active Checkout Alert -->
    @if($this->activeCheckout && !$editing)
        <div class="mb-6 rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:icon.user-circle class="size-6 text-purple-600 dark:text-purple-400" />
                    <div>
                        <div class="font-medium text-purple-900 dark:text-purple-100">
                            {{ __('Currently checked out to :name', ['name' => $this->activeCheckout->member->fullName()]) }}
                        </div>
                        <div class="text-sm text-purple-700 dark:text-purple-300">
                            {{ __('Since :date', ['date' => $this->activeCheckout->checkout_date->format('M d, Y')]) }}
                            &bull;
                            {{ __('Expected return: :date', ['date' => $this->activeCheckout->expected_return_date->format('M d, Y')]) }}
                            @if($this->activeCheckout->isOverdue())
                                <flux:badge color="red" size="sm" class="ml-2">{{ __('Overdue') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Equipment Details -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Equipment Details') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Serial Number') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="serial_number" placeholder="{{ __('Serial Number') }}" />
                        @else
                            {{ $equipment->serial_number ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Model Number') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="model_number" placeholder="{{ __('Model Number') }}" />
                        @else
                            {{ $equipment->model_number ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Manufacturer') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="manufacturer" placeholder="{{ __('Manufacturer') }}" />
                        @else
                            {{ $equipment->manufacturer ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="location" placeholder="{{ __('Location') }}" />
                        @else
                            {{ $equipment->location ?? '-' }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Purchase & Warranty -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Purchase & Warranty') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Purchase Date') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="purchase_date" />
                        @else
                            {{ $equipment->purchase_date?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Purchase Price') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <div class="flex gap-2">
                                <flux:input type="number" step="0.01" wire:model="purchase_price" placeholder="0.00" class="flex-1" />
                                <flux:select wire:model="currency" class="w-20">
                                    <flux:select.option value="GHS">GHS</flux:select.option>
                                    <flux:select.option value="USD">USD</flux:select.option>
                                    <flux:select.option value="EUR">EUR</flux:select.option>
                                    <flux:select.option value="GBP">GBP</flux:select.option>
                                </flux:select>
                            </div>
                        @else
                            @if($equipment->purchase_price)
                                {{ $equipment->currency }} {{ number_format($equipment->purchase_price, 2) }}
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Source of Equipment') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="source_of_equipment" placeholder="{{ __('e.g., Purchased, Donated, Church fund') }}" />
                        @else
                            {{ $equipment->source_of_equipment ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Warranty Expiry') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="warranty_expiry" />
                        @else
                            @if($equipment->warranty_expiry)
                                <span class="{{ $equipment->warrantyExpired() ? 'text-red-600 dark:text-red-400' : '' }}">
                                    {{ $equipment->warranty_expiry->format('M d, Y') }}
                                    @if($equipment->warrantyExpired())
                                        <flux:badge color="red" size="sm" class="ml-1">{{ __('Expired') }}</flux:badge>
                                    @endif
                                </span>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Next Maintenance') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="next_maintenance_date" />
                        @else
                            @if($equipment->next_maintenance_date)
                                <span class="{{ $equipment->maintenanceDue() ? 'text-yellow-600 dark:text-yellow-400' : '' }}">
                                    {{ $equipment->next_maintenance_date->format('M d, Y') }}
                                    @if($equipment->maintenanceDue())
                                        <flux:badge color="yellow" size="sm" class="ml-1">{{ __('Due') }}</flux:badge>
                                    @endif
                                </span>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Description -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <flux:heading size="lg" class="mb-4">{{ __('Description & Notes') }}</flux:heading>
            @if($editing)
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</label>
                        <flux:textarea wire:model="description" placeholder="{{ __('Equipment description...') }}" rows="3" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</label>
                        <flux:textarea wire:model="notes" placeholder="{{ __('Additional notes...') }}" rows="3" />
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @if($equipment->description)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $equipment->description }}</dd>
                        </div>
                    @endif
                    @if($equipment->notes)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $equipment->notes }}</dd>
                        </div>
                    @endif
                    @if(!$equipment->description && !$equipment->notes)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No description or notes') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Checkout History -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Checkout History') }}</flux:heading>

        @if($this->checkoutHistory->isEmpty())
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No checkout history') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Checked Out') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Returned') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Purpose') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->checkoutHistory as $checkout)
                            <tr wire:key="checkout-{{ $checkout->id }}">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $checkout->member->fullName() }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $checkout->checkout_date->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $checkout->actual_return_date?->format('M d, Y') ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:badge
                                        :color="match($checkout->status->value) {
                                            'pending' => 'yellow',
                                            'approved' => 'blue',
                                            'returned' => 'green',
                                            'overdue' => 'red',
                                            'cancelled' => 'zinc',
                                            default => 'zinc',
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst($checkout->status->value) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ Str::limit($checkout->purpose, 50) ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Maintenance History -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Maintenance History') }}</flux:heading>

        @if($this->maintenanceHistory->isEmpty())
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No maintenance records') }}</p>
        @else
            <div class="space-y-4">
                @foreach($this->maintenanceHistory as $maintenance)
                    <div wire:key="maintenance-{{ $maintenance->id }}" class="rounded-lg border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ ucfirst($maintenance->type->value) }}
                                    </span>
                                    <flux:badge
                                        :color="match($maintenance->status->value) {
                                            'scheduled' => 'blue',
                                            'in_progress' => 'yellow',
                                            'completed' => 'green',
                                            'cancelled' => 'zinc',
                                            default => 'zinc',
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $maintenance->status->value)) }}
                                    </flux:badge>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $maintenance->description }}</p>
                                <div class="mt-2 flex flex-wrap gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span>{{ __('Scheduled: :date', ['date' => $maintenance->scheduled_date->format('M d, Y')]) }}</span>
                                    @if($maintenance->completed_date)
                                        <span>{{ __('Completed: :date', ['date' => $maintenance->completed_date->format('M d, Y')]) }}</span>
                                    @endif
                                    @if($maintenance->service_provider)
                                        <span>{{ __('Provider: :name', ['name' => $maintenance->service_provider]) }}</span>
                                    @endif
                                    @if($maintenance->cost)
                                        <span>{{ __('Cost: :currency :amount', ['currency' => $maintenance->currency, 'amount' => number_format($maintenance->cost, 2)]) }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($this->canManageMaintenance && $maintenance->isScheduled())
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" wire:click="completeMaintenance('{{ $maintenance->id }}')" icon="check">
                                        {{ __('Complete') }}
                                    </flux:button>
                                    <flux:button variant="ghost" size="sm" wire:click="cancelMaintenance('{{ $maintenance->id }}')" wire:confirm="{{ __('Are you sure you want to cancel this maintenance?') }}" icon="x-mark">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Checkout Modal -->
    <flux:modal wire:model.self="showCheckoutModal" name="checkout-equipment" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Check Out Equipment') }}</flux:heading>

            <form wire:submit="processCheckout" class="space-y-4">
                <div>
                    <flux:select wire:model="checkoutMemberId" :label="__('Member')">
                        <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('checkoutMemberId') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <flux:input type="date" wire:model="checkoutExpectedReturn" :label="__('Expected Return Date')" />
                @error('checkoutExpectedReturn') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                <flux:textarea wire:model="checkoutPurpose" :label="__('Purpose')" rows="2" placeholder="{{ __('Reason for checkout...') }}" />

                <flux:textarea wire:model="checkoutNotes" :label="__('Notes')" rows="2" placeholder="{{ __('Additional notes...') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeCheckoutModal" type="button">
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
    <flux:modal wire:model.self="showReturnModal" name="return-equipment" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Return Equipment') }}</flux:heading>

            <form wire:submit="processReturn" class="space-y-4">
                <div>
                    <flux:select wire:model="returnCondition" :label="__('Condition on Return')">
                        @foreach($this->conditions as $cond)
                            <flux:select.option value="{{ $cond->value }}">{{ ucfirst(str_replace('_', ' ', $cond->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('returnCondition') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <flux:textarea wire:model="returnNotes" :label="__('Return Notes')" rows="3" placeholder="{{ __('Any notes about the return...') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeReturnModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Process Return') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Maintenance Modal -->
    <flux:modal wire:model.self="showMaintenanceModal" name="schedule-maintenance" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Schedule Maintenance') }}</flux:heading>

            <form wire:submit="scheduleMaintenance" class="space-y-4">
                <div>
                    <flux:select wire:model="maintenanceType" :label="__('Type')">
                        <flux:select.option value="">{{ __('Select type...') }}</flux:select.option>
                        @foreach($this->maintenanceTypes as $type)
                            <flux:select.option value="{{ $type->value }}">{{ ucfirst($type->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('maintenanceType') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <flux:input type="date" wire:model="maintenanceScheduledDate" :label="__('Scheduled Date')" />
                @error('maintenanceScheduledDate') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                <flux:textarea wire:model="maintenanceDescription" :label="__('Description')" rows="2" placeholder="{{ __('Describe the maintenance needed...') }}" />
                @error('maintenanceDescription') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                <flux:input wire:model="maintenanceServiceProvider" :label="__('Service Provider')" placeholder="{{ __('Optional') }}" />

                <flux:input type="number" step="0.01" wire:model="maintenanceCost" :label="__('Estimated Cost (GHS)')" placeholder="0.00" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeMaintenanceModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Schedule') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-equipment" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Equipment') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $equipment->name]) }}
            </p>
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

    <!-- Success Toasts -->
    <x-toast on="equipment-updated" type="success">
        {{ __('Equipment updated successfully.') }}
    </x-toast>

    <x-toast on="equipment-checked-out" type="success">
        {{ __('Equipment checked out successfully.') }}
    </x-toast>

    <x-toast on="equipment-returned" type="success">
        {{ __('Equipment returned successfully.') }}
    </x-toast>

    <x-toast on="maintenance-scheduled" type="success">
        {{ __('Maintenance scheduled successfully.') }}
    </x-toast>

    <x-toast on="maintenance-completed" type="success">
        {{ __('Maintenance marked as completed.') }}
    </x-toast>

    <x-toast on="maintenance-cancelled" type="success">
        {{ __('Maintenance cancelled.') }}
    </x-toast>
</section>
