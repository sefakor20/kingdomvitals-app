<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Age Groups') }}</flux:heading>
            <flux:subheading>{{ __('Manage age-based class assignments for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" href="{{ route('children.index', $branch) }}" icon="arrow-left">
                {{ __('Back to Directory') }}
            </flux:button>
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('New Age Group') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Auto-Assign Banner -->
    @if($this->unassignedChildrenCount > 0 && $this->canCreate)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-amber-100 p-2 dark:bg-amber-800">
                        <flux:icon icon="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <flux:text class="font-medium text-amber-800 dark:text-amber-200">
                            {{ __(':count children not assigned to an age group', ['count' => $this->unassignedChildrenCount]) }}
                        </flux:text>
                        <flux:text class="text-sm text-amber-600 dark:text-amber-400">
                            {{ __('Auto-assign children based on their date of birth') }}
                        </flux:text>
                    </div>
                </div>
                <flux:button wire:click="autoAssignAll" wire:confirm="{{ __('This will automatically assign all unassigned children to age groups based on their date of birth. Continue?') }}" variant="filled" class="bg-amber-600 hover:bg-amber-700">
                    {{ __('Auto-Assign All') }}
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Age Groups Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if($this->ageGroups->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <div class="rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
                    <flux:icon icon="user-group" class="size-8 text-zinc-400" />
                </div>
                <flux:heading size="lg" class="mt-4">{{ __('No Age Groups') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Create your first age group to organize children.') }}</flux:text>
                @if($this->canCreate)
                    <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                        {{ __('Create Age Group') }}
                    </flux:button>
                @endif
            </div>
        @else
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Age Range') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Children') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->ageGroups as $ageGroup)
                        <tr wire:key="age-group-{{ $ageGroup->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($ageGroup->color)
                                        <div class="size-3 rounded-full" style="background-color: {{ $ageGroup->color }}"></div>
                                    @endif
                                    <div>
                                        <flux:text class="font-medium">{{ $ageGroup->name }}</flux:text>
                                        @if($ageGroup->description)
                                            <flux:text class="text-sm text-zinc-500">{{ Str::limit($ageGroup->description, 50) }}</flux:text>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge>{{ $ageGroup->min_age }} - {{ $ageGroup->max_age }} {{ __('years') }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $ageGroup->children_count }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($ageGroup->is_active)
                                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="edit('{{ $ageGroup->id }}')" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        @can('delete', $ageGroup)
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="confirmDelete('{{ $ageGroup->id }}')" icon="trash" variant="danger">
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
        @endif
    </div>

    <!-- Create Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Create Age Group') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Name')" placeholder="{{ __('e.g., Nursery, Toddlers, Pre-K') }}" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" placeholder="{{ __('Optional description...') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="minAge" type="number" :label="__('Minimum Age')" min="0" max="17" required />
                    <flux:input wire:model="maxAge" type="number" :label="__('Maximum Age')" min="0" max="17" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="color" type="color" :label="__('Color')" />
                    <flux:input wire:model="sortOrder" type="number" :label="__('Sort Order')" min="0" />
                </div>

                <flux:switch wire:model="isActive" :label="__('Active')" :description="__('Inactive age groups won\'t be shown in selections')" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Age Group') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Age Group') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Name')" placeholder="{{ __('e.g., Nursery, Toddlers, Pre-K') }}" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" placeholder="{{ __('Optional description...') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="minAge" type="number" :label="__('Minimum Age')" min="0" max="17" required />
                    <flux:input wire:model="maxAge" type="number" :label="__('Maximum Age')" min="0" max="17" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="color" type="color" :label="__('Color')" />
                    <flux:input wire:model="sortOrder" type="number" :label="__('Sort Order')" min="0" />
                </div>

                <flux:switch wire:model="isActive" :label="__('Active')" :description="__('Inactive age groups won\'t be shown in selections')" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelEdit">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Update Age Group') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Age Group') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this age group?') }}
                @if($deletingAgeGroup && $deletingAgeGroup->children_count > 0)
                    <span class="mt-2 block font-medium text-amber-600 dark:text-amber-400">
                        {{ __('Warning: :count children are assigned to this age group and will become unassigned.', ['count' => $deletingAgeGroup->children_count]) }}
                    </span>
                @endif
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
