<div>
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Super Admins') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                {{ __('Manage platform administrators and their access levels.') }}
            </flux:text>
        </div>

        @if($canManage)
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
                {{ __('Add Admin') }}
            </flux:button>
        @endif
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by name or email...') }}"
            />
        </div>

        <div class="flex gap-3">
            <flux:select wire:model.live="role" class="w-40">
                <flux:select.option value="">{{ __('All Roles') }}</flux:select.option>
                @foreach($roles as $roleOption)
                    <flux:select.option value="{{ $roleOption->value }}">
                        {{ $roleOption->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" class="w-40">
                <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Admins Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Admin') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Role') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('2FA') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Created') }}
                    </th>
                    @if($canManage)
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Actions') }}
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($admins as $admin)
                    <tr wire:key="admin-{{ $admin->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$admin->name" />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <flux:text class="font-medium text-zinc-900 dark:text-white">
                                            {{ $admin->name }}
                                        </flux:text>
                                        @if($admin->id === $currentUserId)
                                            <flux:badge color="zinc" size="sm">{{ __('You') }}</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text class="text-sm text-zinc-500">{{ $admin->email }}</flux:text>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @php
                                $roleColor = match($admin->role->value) {
                                    'owner' => 'purple',
                                    'admin' => 'blue',
                                    'support' => 'zinc',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge :color="$roleColor">
                                {{ $admin->role->label() }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($admin->is_active)
                                <flux:badge color="green">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="red">{{ __('Inactive') }}</flux:badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($admin->two_factor_confirmed_at)
                                <flux:badge color="green" icon="shield-check">{{ __('Enabled') }}</flux:badge>
                            @else
                                <flux:badge color="zinc">{{ __('Disabled') }}</flux:badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $admin->created_at->format('M d, Y') }}
                        </td>
                        @if($canManage)
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="openEditModal('{{ $admin->id }}')">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="key" wire:click="openResetPasswordModal('{{ $admin->id }}')">
                                            {{ __('Reset Password') }}
                                        </flux:menu.item>
                                        @if($admin->id !== $currentUserId)
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $admin->id }}')">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 6 : 5 }}" class="px-6 py-12 text-center">
                            <flux:icon.users class="mx-auto size-12 text-zinc-400" />
                            <flux:heading size="sm" class="mt-4">{{ __('No admins found') }}</flux:heading>
                            <flux:text class="mt-1 text-zinc-500">
                                {{ __('Try adjusting your search or filter criteria.') }}
                            </flux:text>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($admins->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $admins->links() }}
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Super Admin') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Add a new administrator to the platform.') }}
                </flux:text>
            </div>

            <form wire:submit="createAdmin" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="createName" placeholder="{{ __('Full name') }}" />
                    <flux:error name="createName" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="createEmail" type="email" placeholder="{{ __('admin@example.com') }}" />
                    <flux:error name="createEmail" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input wire:model="createPassword" type="password" />
                    <flux:error name="createPassword" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Confirm Password') }}</flux:label>
                    <flux:input wire:model="createPasswordConfirmation" type="password" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Role') }}</flux:label>
                    <flux:select wire:model="createRole">
                        @foreach($roles as $roleOption)
                            <flux:select.option value="{{ $roleOption->value }}">
                                {{ $roleOption->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="createRole" />
                </flux:field>

                <flux:field>
                    <flux:switch wire:model="createIsActive" label="{{ __('Active') }}" description="{{ __('Allow this admin to log in.') }}" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Create Admin') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Super Admin') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Update administrator details.') }}
                </flux:text>
            </div>

            <form wire:submit="updateAdmin" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="editName" placeholder="{{ __('Full name') }}" />
                    <flux:error name="editName" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="editEmail" type="email" placeholder="{{ __('admin@example.com') }}" />
                    <flux:error name="editEmail" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Role') }}</flux:label>
                    <flux:select wire:model="editRole" :disabled="$editAdminId === $currentUserId">
                        @foreach($roles as $roleOption)
                            <flux:select.option value="{{ $roleOption->value }}">
                                {{ $roleOption->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="editRole" />
                    @if($editAdminId === $currentUserId)
                        <flux:text class="text-xs text-zinc-500">
                            {{ __('You cannot change your own role.') }}
                        </flux:text>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:switch
                        wire:model="editIsActive"
                        label="{{ __('Active') }}"
                        description="{{ __('Allow this admin to log in.') }}"
                        :disabled="$editAdminId === $currentUserId"
                    />
                    <flux:error name="editIsActive" />
                    @if($editAdminId === $currentUserId)
                        <flux:text class="text-xs text-zinc-500">
                            {{ __('You cannot deactivate your own account.') }}
                        </flux:text>
                    @endif
                </flux:field>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showEditModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Reset Password Modal -->
    <flux:modal wire:model="showResetPasswordModal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900/30">
                    <flux:icon.key class="size-8 text-amber-600 dark:text-amber-400" />
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ __('Reset Password') }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ __('Set a new password for this administrator.') }}
                    </flux:text>
                </div>
            </div>

            <form wire:submit="resetPassword" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('New Password') }}</flux:label>
                    <flux:input wire:model="newPassword" type="password" />
                    <flux:error name="newPassword" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Confirm New Password') }}</flux:label>
                    <flux:input wire:model="newPasswordConfirmation" type="password" />
                </flux:field>

                <div class="flex gap-3 pt-4">
                    <flux:button variant="ghost" class="flex-1" wire:click="$set('showResetPasswordModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1">
                        {{ __('Reset Password') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="size-8 text-red-600 dark:text-red-400" />
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ __('Delete Admin') }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ __('Are you sure you want to delete this administrator? This action cannot be undone.') }}
                    </flux:text>
                </div>
            </div>

            @error('delete')
                <flux:callout variant="danger" icon="x-circle" :heading="$message" />
            @enderror

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" wire:click="$set('showDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" class="flex-1" wire:click="deleteAdmin">
                    {{ __('Delete Admin') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Toast Notifications -->
    <x-toast on="admin-created" type="success">
        {{ __('Super admin created successfully.') }}
    </x-toast>
    <x-toast on="admin-updated" type="success">
        {{ __('Super admin updated successfully.') }}
    </x-toast>
    <x-toast on="password-reset" type="success">
        {{ __('Password reset successfully.') }}
    </x-toast>
    <x-toast on="admin-deleted" type="success">
        {{ __('Super admin deleted successfully.') }}
    </x-toast>
</div>
