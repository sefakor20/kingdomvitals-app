<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2">
                <a
                    href="{{ route('households.index', ['branch' => $branch]) }}"
                    class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300"
                >
                    <flux:icon name="arrow-left" class="h-5 w-5" />
                </a>
                <flux:heading size="xl">{{ $household->name }}</flux:heading>
            </div>
            @if ($household->address)
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $household->address }}
                </flux:text>
            @endif
        </div>
        @if ($this->canManageMembers)
            <flux:button wire:click="openAddMemberModal" variant="primary" icon="user-plus">
                {{ __('Add Member') }}
            </flux:button>
        @endif
    </div>

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Members') }}</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $this->members->count() }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Adults') }}</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $this->members->filter(fn($m) => !$m->isChild())->count() }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Children') }}</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                {{ $this->members->filter(fn($m) => $m->isChild())->count() }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Head') }}</flux:text>
            <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">
                {{ $household->head?->fullName() ?? '-' }}
            </div>
        </div>
    </div>

    {{-- Members List --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Household Members') }}</flux:heading>
        </div>

        @if ($this->members->isEmpty())
            <div class="p-12 text-center">
                <flux:icon name="users" class="mx-auto mb-4 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('No members in this household yet.') }}
                </flux:text>
                @if ($this->canManageMembers)
                    <flux:button wire:click="openAddMemberModal" variant="primary" class="mt-4">
                        {{ __('Add Member') }}
                    </flux:button>
                @endif
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->members as $member)
                    <div
                        wire:key="member-{{ $member->id }}"
                        class="flex items-center justify-between px-6 py-4"
                    >
                        <div class="flex items-center gap-4">
                            @if ($member->photo_url)
                                <img
                                    src="{{ $member->photo_url }}"
                                    alt="{{ $member->fullName() }}"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                    <flux:icon name="user" class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
                                </div>
                            @endif
                            <div>
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-medium text-zinc-900 dark:text-white">
                                        {{ $member->fullName() }}
                                    </flux:text>
                                    @if ($member->household_role)
                                        <flux:badge
                                            :color="$member->household_role->value === 'head' ? 'blue' : ($member->household_role->value === 'spouse' ? 'purple' : ($member->household_role->value === 'child' ? 'green' : 'zinc'))"
                                            size="sm"
                                        >
                                            {{ ucfirst($member->household_role->value) }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($member->date_of_birth)
                                        {{ $member->date_of_birth->age }} {{ __('years old') }}
                                    @endif
                                    @if ($member->phone)
                                        {{ $member->date_of_birth ? ' - ' : '' }}{{ $member->phone }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>

                        @if ($this->canManageMembers)
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item wire:click="openEditRoleModal({{ $member->id }})" icon="pencil">
                                        {{ __('Change Role') }}
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="confirmRemoveMember({{ $member->id }})" icon="user-minus" variant="danger">
                                        {{ __('Remove from Household') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Add Member Modal --}}
    <flux:modal wire:model="showAddMemberModal" class="max-w-md">
        <flux:heading size="lg" class="mb-4">{{ __('Add Member to Household') }}</flux:heading>

        <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Search Members') }}</flux:label>
                <flux:input
                    wire:model.live.debounce.200ms="memberSearch"
                    placeholder="{{ __('Type to search...') }}"
                />
            </flux:field>

            @if ($memberSearch && $this->availableMembers->isEmpty())
                <flux:text class="text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('No unassigned members found.') }}
                </flux:text>
            @elseif ($this->availableMembers->isNotEmpty())
                <div class="max-h-48 space-y-2 overflow-y-auto">
                    @foreach ($this->availableMembers as $member)
                        <button
                            type="button"
                            wire:key="available-{{ $member->id }}"
                            wire:click="selectMember('{{ $member->id }}')"
                            class="flex w-full items-center gap-3 rounded-lg border p-3 text-left transition {{ $selectedMemberId === $member->id ? 'border-blue-500 bg-blue-50 dark:border-blue-500 dark:bg-blue-900/20' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-700/50' }}"
                        >
                            @if ($member->photo_url)
                                <img
                                    src="{{ $member->photo_url }}"
                                    alt="{{ $member->fullName() }}"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-600">
                                    <flux:icon name="user" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                                </div>
                            @endif
                            <div>
                                <flux:text class="font-medium text-zinc-900 dark:text-white">
                                    {{ $member->fullName() }}
                                </flux:text>
                                @if ($member->date_of_birth)
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $member->date_of_birth->age }} {{ __('years old') }}
                                    </flux:text>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($selectedMemberId)
                <flux:field>
                    <flux:label>{{ __('Role in Household') }}</flux:label>
                    <flux:select wire:model="selectedRole">
                        @foreach ($this->roles as $role)
                            <flux:select.option value="{{ $role->value }}">
                                {{ ucfirst($role->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button type="button" wire:click="cancelAddMember" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button
                wire:click="addMember"
                variant="primary"
                :disabled="! $selectedMemberId"
            >
                {{ __('Add Member') }}
            </flux:button>
        </div>
    </flux:modal>

    {{-- Edit Role Modal --}}
    <flux:modal wire:model="showEditRoleModal" class="max-w-sm">
        @if ($editingMember)
            <flux:heading size="lg" class="mb-4">{{ __('Change Role') }}</flux:heading>
            <flux:text class="mb-4 text-zinc-500 dark:text-zinc-400">
                {{ __('Update role for :name', ['name' => $editingMember->fullName()]) }}
            </flux:text>

            <flux:field>
                <flux:label>{{ __('Household Role') }}</flux:label>
                <flux:select wire:model="editingRole">
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ ucfirst($role->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button type="button" wire:click="cancelEditRole" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="updateRole" variant="primary">
                    {{ __('Update') }}
                </flux:button>
            </div>
        @endif
    </flux:modal>

    {{-- Remove Member Modal --}}
    <flux:modal wire:model="showRemoveMemberModal" class="max-w-sm">
        @if ($removingMember)
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="user-minus" class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ __('Remove Member?') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Remove :name from this household? They will not be deleted.', ['name' => $removingMember->fullName()]) }}
                </flux:text>

                <div class="mt-6 flex justify-center gap-3">
                    <flux:button wire:click="cancelRemoveMember" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="removeMember" variant="danger">
                        {{ __('Remove') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Success Toasts --}}
    <x-toast on="member-added" type="success">
        {{ __('Member added to household successfully.') }}
    </x-toast>

    <x-toast on="role-updated" type="success">
        {{ __('Member role updated successfully.') }}
    </x-toast>

    <x-toast on="member-removed" type="success">
        {{ __('Member removed from household successfully.') }}
    </x-toast>
</div>
