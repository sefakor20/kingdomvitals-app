<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Branch Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage user access to :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="openInviteModal" icon="plus">
            {{ __('Add User') }}
        </flux:button>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search users by name or email...') }}" icon="magnifying-glass" />
    </div>

    @if($this->users->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="users" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No users found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($search)
                    {{ __('Try adjusting your search criteria.') }}
                @else
                    {{ __('Add users to give them access to this branch.') }}
                @endif
            </flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('User') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Role') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Primary') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->users as $access)
                        <tr wire:key="access-{{ $access->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $access->user->name }}" />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $access->user->name }}
                                        </div>
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $access->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($access->role->value) {
                                        'admin' => 'red',
                                        'manager' => 'amber',
                                        'staff' => 'blue',
                                        'volunteer' => 'green',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($access->role->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($access->is_primary)
                                    <flux:badge color="blue" size="sm">{{ __('Primary') }}</flux:badge>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                @if($access->user_id !== auth()->id())
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button variant="ghost" size="sm" wire:click="edit('{{ $access->id }}')" icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="confirmRevoke('{{ $access->id }}')" icon="trash" class="text-red-600 hover:text-red-700">
                                            {{ __('Revoke') }}
                                        </flux:button>
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">{{ __('(You)') }}</flux:text>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Invite Modal -->
    <flux:modal wire:model.self="showInviteModal" name="invite-user" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add User to Branch') }}</flux:heading>

            <form wire:submit="invite" class="space-y-4">
                <flux:input
                    wire:model="inviteEmail"
                    type="email"
                    :label="__('Email Address')"
                    placeholder="user@example.com"
                    required
                />

                <flux:select wire:model="inviteRole" :label="__('Role')">
                    @foreach($this->roles as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ ucfirst($role->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelInvite" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add User') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-access" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit User Access') }}</flux:heading>

            @if($editingAccess)
                <div class="flex items-center gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <flux:avatar size="sm" name="{{ $editingAccess->user->name }}" />
                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $editingAccess->user->name }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $editingAccess->user->email }}
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="updateAccess" class="space-y-4">
                <flux:select wire:model="editRole" :label="__('Role')">
                    @foreach($this->roles as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ ucfirst($role->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:checkbox wire:model="editIsPrimary" :label="__('Set as primary branch')" />

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

    <!-- Revoke Confirmation Modal -->
    <flux:modal wire:model.self="showRevokeModal" name="revoke-access" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Revoke Access') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to revoke access for :name? They will no longer be able to access this branch.', ['name' => $revokingAccess?->user?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelRevoke">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="revoke">
                    {{ __('Revoke Access') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="user-invited" type="success">
        {{ __('User added successfully.') }}
    </x-toast>

    <x-toast on="user-updated" type="success">
        {{ __('User access updated successfully.') }}
    </x-toast>

    <x-toast on="user-revoked" type="success">
        {{ __('User access revoked successfully.') }}
    </x-toast>
</section>
