<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Clusters') }}</flux:heading>
            <flux:subheading>{{ __('Manage clusters for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        @if ($this->canCreate && $this->canCreateWithinQuota)
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('Add Cluster') }}
            </flux:button>
        @elseif ($this->canCreate && ! $this->canCreateWithinQuota)
            <flux:button variant="ghost" disabled icon="lock-closed" class="cursor-not-allowed">
                {{ __('Cluster Limit Reached') }}
            </flux:button>
        @endif
    </div>

    {{-- Quota Warning Banner --}}
    @if ($this->showQuotaWarning && ! $this->clusterQuota['unlimited'])
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                        {{ __('Approaching Cluster Limit') }}
                    </flux:text>
                    <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('You have :current of :max clusters (:percent% used). Consider upgrading your plan for more capacity.', [
                            'current' => $this->clusterQuota['current'],
                            'max' => $this->clusterQuota['max'],
                            'percent' => $this->clusterQuota['percent'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'clusters']) }}" variant="ghost" size="sm">
                    {{ __('Upgrade') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Quota Exceeded Banner --}}
    @if (! $this->canCreateWithinQuota && ! $this->clusterQuota['unlimited'])
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-center gap-3">
                <flux:icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                <div class="flex-1">
                    <flux:text class="font-medium text-red-800 dark:text-red-200">
                        {{ __('Cluster Limit Reached') }}
                    </flux:text>
                    <flux:text class="text-sm text-red-700 dark:text-red-300">
                        {{ __('You have reached your limit of :max clusters. Upgrade your plan to add more clusters.', [
                            'max' => $this->clusterQuota['max'],
                        ]) }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('upgrade.required', ['module' => 'clusters']) }}" variant="primary" size="sm">
                    {{ __('Upgrade Now') }}
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Search and Filter -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->clusterTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ str_replace('_', ' ', ucwords($type->value, '_')) }}
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

    @if($this->clusters->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="rectangle-group" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No clusters found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search || $typeFilter || $statusFilter)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first cluster.') }}
                @endif
            </flux:text>
            @if(!$search && !$typeFilter && !$statusFilter && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Cluster') }}
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
                            {{ __('Leader') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Members') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Meeting') }}
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
                    @foreach($this->clusters as $cluster)
                        <tr wire:key="cluster-{{ $cluster->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <a href="{{ route('clusters.show', [$branch, $cluster]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                        {{ $cluster->name }}
                                    </a>
                                    <div class="mt-1">
                                        <flux:badge color="zinc" size="sm">
                                            {{ str_replace('_', ' ', ucwords($cluster->cluster_type->value, '_')) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($cluster->leader)
                                    <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $cluster->leader->fullName() }}
                                    </div>
                                    @if($cluster->assistantLeader)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('Asst:') }} {{ $cluster->assistantLeader->fullName() }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-zinc-400">{{ __('Not assigned') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $cluster->members_count }}
                                    @if($cluster->capacity)
                                        <span class="text-zinc-500">/ {{ $cluster->capacity }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($cluster->meeting_day)
                                    {{ $cluster->meeting_day }}
                                    @if($cluster->meeting_time)
                                        {{ __('at') }} {{ $cluster->meeting_time }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="$cluster->is_active ? 'green' : 'zinc'"
                                    size="sm"
                                >
                                    {{ $cluster->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @can('update', $cluster)
                                            <flux:menu.item wire:click="edit('{{ $cluster->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan
                                        @can('delete', $cluster)
                                            <flux:menu.item wire:click="confirmDelete('{{ $cluster->id }}')" icon="trash" variant="danger">
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

        @if($this->clusters->hasPages())
            <div class="mt-4">
                {{ $this->clusters->links() }}
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-cluster" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Cluster') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="name" :label="__('Name')" required />
                    <flux:select wire:model="cluster_type" :label="__('Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->clusterTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ str_replace('_', ' ', ucwords($type->value, '_')) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="leader_id" :label="__('Leader')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->availableLeaders as $leader)
                            <flux:select.option value="{{ $leader->id }}">
                                {{ $leader->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="assistant_leader_id" :label="__('Assistant Leader')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->availableLeaders as $leader)
                            <flux:select.option value="{{ $leader->id }}">
                                {{ $leader->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="meeting_day" :label="__('Meeting Day')" placeholder="{{ __('e.g., Wednesday') }}" />
                    <flux:input wire:model="meeting_time" type="time" :label="__('Meeting Time')" />
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" />
                </div>

                <flux:input wire:model="meeting_location" :label="__('Meeting Location')" />

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Cluster') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-cluster" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Cluster') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="name" :label="__('Name')" required />
                    <flux:select wire:model="cluster_type" :label="__('Type')" required>
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->clusterTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ str_replace('_', ' ', ucwords($type->value, '_')) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="leader_id" :label="__('Leader')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->availableLeaders as $leader)
                            <flux:select.option value="{{ $leader->id }}">
                                {{ $leader->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="assistant_leader_id" :label="__('Assistant Leader')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->availableLeaders as $leader)
                            <flux:select.option value="{{ $leader->id }}">
                                {{ $leader->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="meeting_day" :label="__('Meeting Day')" placeholder="{{ __('e.g., Wednesday') }}" />
                    <flux:input wire:model="meeting_time" type="time" :label="__('Meeting Time')" />
                    <flux:input wire:model="capacity" type="number" min="1" :label="__('Capacity')" />
                </div>

                <flux:input wire:model="meeting_location" :label="__('Meeting Location')" />

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-cluster" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Cluster') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This will remove all member associations. This action cannot be undone.', ['name' => $deletingCluster?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Cluster') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="cluster-created" type="success">
        {{ __('Cluster added successfully.') }}
    </x-toast>

    <x-toast on="cluster-updated" type="success">
        {{ __('Cluster updated successfully.') }}
    </x-toast>

    <x-toast on="cluster-deleted" type="success">
        {{ __('Cluster deleted successfully.') }}
    </x-toast>
</section>
