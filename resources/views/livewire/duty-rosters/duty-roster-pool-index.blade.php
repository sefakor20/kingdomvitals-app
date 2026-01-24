<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Personnel Pools') }}</flux:heading>
            <flux:subheading>{{ __('Manage pools of members for duty roster assignments') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('duty-rosters.index', $branch) }}" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Back to Rosters') }}
            </flux:button>
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Create Pool') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex items-center gap-4">
        <div class="w-48">
            <flux:select wire:model.live="roleTypeFilter">
                <flux:select.option value="">{{ __('All Role Types') }}</flux:select.option>
                @foreach($this->roleTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ $type->pluralLabel() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($this->pools->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="user-group" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No pools found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                {{ __('Create pools to manage personnel for automatic roster generation.') }}
            </flux:text>
            @if ($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Create Pool') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($this->pools as $pool)
                <div wire:key="pool-{{ $pool->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:heading size="lg">{{ $pool->name }}</flux:heading>
                            <flux:badge :color="match($pool->role_type->value) {
                                'preacher' => 'blue',
                                'liturgist' => 'green',
                                'reader' => 'purple',
                                default => 'zinc',
                            }" size="sm" class="mt-1">
                                {{ $pool->role_type->label() }}
                            </flux:badge>
                        </div>
                        @if(!$pool->is_active)
                            <flux:badge color="red" size="sm">{{ __('Inactive') }}</flux:badge>
                        @endif
                    </div>

                    @if($pool->description)
                        <flux:text class="mt-2 text-sm text-zinc-500">{{ $pool->description }}</flux:text>
                    @endif

                    <div class="mt-4 flex items-center gap-4 text-sm text-zinc-500">
                        <span class="flex items-center gap-1">
                            <flux:icon icon="users" class="size-4" />
                            {{ $pool->members_count }} {{ __('members') }}
                        </span>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <flux:button variant="ghost" size="sm" wire:click="manageMembers('{{ $pool->id }}')" icon="user-plus">
                            {{ __('Members') }}
                        </flux:button>
                        @can('update', $pool)
                            <flux:button variant="ghost" size="sm" wire:click="edit('{{ $pool->id }}')" icon="pencil">
                                {{ __('Edit') }}
                            </flux:button>
                        @endcan
                        @can('delete', $pool)
                            <flux:button variant="ghost" size="sm" wire:click="confirmDelete('{{ $pool->id }}')" icon="trash" class="text-red-600 hover:text-red-700">
                                {{ __('Delete') }}
                            </flux:button>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-pool" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Create Pool') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Pool Name')" placeholder="{{ __('e.g., Sunday Preachers') }}" required />

                <flux:select wire:model="role_type" :label="__('Role Type')" required>
                    @foreach($this->roleTypes as $type)
                        <flux:select.option value="{{ $type->value }}">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <flux:checkbox wire:model="is_active" :label="__('Active')" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Pool') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-pool" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Pool') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Pool Name')" required />

                <flux:select wire:model="role_type" :label="__('Role Type')" required>
                    @foreach($this->roleTypes as $type)
                        <flux:select.option value="{{ $type->value }}">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <flux:checkbox wire:model="is_active" :label="__('Active')" />

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

    <!-- Delete Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-pool" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Pool') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete the pool ":name"? This will remove all member assignments from this pool.', ['name' => $deletingPool?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Pool') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Manage Members Modal -->
    <flux:modal wire:model.self="showMembersModal" name="manage-members" class="w-full max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Manage Pool Members') }}</flux:heading>
                @if($managingPool)
                    <flux:text class="mt-1 text-zinc-500">{{ $managingPool->name }}</flux:text>
                @endif
            </div>

            @if($managingPool)
                <!-- Add Member -->
                <div class="flex gap-2">
                    <div class="flex-1">
                        <flux:select wire:model.live="selectedMemberId">
                            <flux:select.option value="">{{ __('Select member to add...') }}</flux:select.option>
                            @foreach($this->availableMembers as $member)
                                <flux:select.option value="{{ $member->id }}">
                                    {{ $member->fullName() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button variant="primary" wire:click="addMember" icon="plus" :disabled="!$selectedMemberId">
                        {{ __('Add') }}
                    </flux:button>
                </div>

                <!-- Member List -->
                @if($managingPool->members->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Member') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Assignments') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Last Assigned') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Status') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                @foreach($managingPool->members as $member)
                                    <tr wire:key="member-{{ $member->id }}">
                                        <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $member->fullName() }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-zinc-500">
                                            {{ $member->pivot->assignment_count }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-zinc-500">
                                            {{ $member->pivot->last_assigned_date?->format('M j, Y') ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <flux:badge :color="$member->pivot->is_active ? 'green' : 'zinc'" size="sm">
                                                {{ $member->pivot->is_active ? __('Active') : __('Inactive') }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <flux:dropdown position="bottom" align="end">
                                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                                <flux:menu>
                                                    <flux:menu.item wire:click="toggleMemberActive('{{ $member->id }}')" icon="{{ $member->pivot->is_active ? 'pause' : 'play' }}">
                                                        {{ $member->pivot->is_active ? __('Deactivate') : __('Activate') }}
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="resetMemberCounters('{{ $member->id }}')" icon="arrow-path">
                                                        {{ __('Reset Counters') }}
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="removeMember('{{ $member->id }}')" icon="trash" variant="danger">
                                                        {{ __('Remove') }}
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between">
                        <flux:button variant="ghost" wire:click="resetAllCounters" icon="arrow-path">
                            {{ __('Reset All Counters') }}
                        </flux:button>
                        <flux:button variant="ghost" wire:click="closeMembersModal">
                            {{ __('Close') }}
                        </flux:button>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <flux:text class="text-zinc-500">{{ __('No members in this pool yet.') }}</flux:text>
                    </div>
                    <div class="flex justify-end">
                        <flux:button variant="ghost" wire:click="closeMembersModal">
                            {{ __('Close') }}
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>
    </flux:modal>

    <!-- Toasts -->
    <x-toast on="pool-created" type="success">{{ __('Pool created successfully.') }}</x-toast>
    <x-toast on="pool-updated" type="success">{{ __('Pool updated successfully.') }}</x-toast>
    <x-toast on="pool-deleted" type="success">{{ __('Pool deleted successfully.') }}</x-toast>
    <x-toast on="member-added" type="success">{{ __('Member added to pool.') }}</x-toast>
    <x-toast on="member-removed" type="success">{{ __('Member removed from pool.') }}</x-toast>
    <x-toast on="counters-reset" type="success">{{ __('Counters reset successfully.') }}</x-toast>
    <x-toast on="all-counters-reset" type="success">{{ __('All counters reset successfully.') }}</x-toast>
</section>
