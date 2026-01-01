<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Services') }}</flux:heading>
            <flux:subheading>{{ __('Manage services for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        @if($this->canCreate)
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('Add Service') }}
            </flux:button>
        @endif
    </div>

    <!-- Search and Filter -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->serviceTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ ucfirst($type->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if($this->services->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="calendar" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No services found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $typeFilter || $statusFilter)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first service.') }}
                @endif
            </flux:text>
            @if(!$search && !$typeFilter && !$statusFilter && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Service') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Day') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Time') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Capacity') }}
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
                    @foreach($this->services as $service)
                        <tr wire:key="service-{{ $service->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <a href="{{ route('services.show', [$branch, $service]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                        {{ $service->name }}
                                    </a>
                                    <div class="mt-1">
                                        <flux:badge color="zinc" size="sm">
                                            {{ ucfirst($service->service_type->value) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->getDayName($service->day_of_week) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $service->time }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $service->capacity ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="$service->is_active ? 'green' : 'zinc'"
                                    size="sm"
                                >
                                    {{ $service->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @can('update', $service)
                                            <flux:menu.item wire:click="edit('{{ $service->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan
                                        @can('delete', $service)
                                            <flux:menu.item wire:click="confirmDelete('{{ $service->id }}')" icon="trash" variant="danger">
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
    <flux:modal wire:model.self="showCreateModal" name="create-service" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Service') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Name')" placeholder="{{ __('e.g., Sunday Morning Service') }}" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="day_of_week" :label="__('Day of Week')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                        <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                        <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                        <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                        <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                        <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                        <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="time" type="time" :label="__('Time')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="service_type" :label="__('Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->serviceTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ ucfirst($type->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" placeholder="{{ __('Optional') }}" />
                </div>

                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Service') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-service" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Service') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Name')" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="day_of_week" :label="__('Day of Week')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                        <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                        <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                        <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                        <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                        <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                        <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="time" type="time" :label="__('Time')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="service_type" :label="__('Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->serviceTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ ucfirst($type->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" />
                </div>

                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-service" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Service') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $deletingService?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Service') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="service-created" type="success">
        {{ __('Service added successfully.') }}
    </x-toast>

    <x-toast on="service-updated" type="success">
        {{ __('Service updated successfully.') }}
    </x-toast>

    <x-toast on="service-deleted" type="success">
        {{ __('Service deleted successfully.') }}
    </x-toast>
</section>
