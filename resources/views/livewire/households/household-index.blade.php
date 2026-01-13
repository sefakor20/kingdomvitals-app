<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Households') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Manage family households for :branch', ['branch' => $branch->name]) }}
            </flux:text>
        </div>
        @if ($this->canCreate)
            <flux:button wire:click="create" variant="primary" icon="plus">
                {{ __('Create Household') }}
            </flux:button>
        @endif
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.200ms="search"
            placeholder="{{ __('Search households or head of household...') }}"
            class="max-w-md"
        >
            <x-slot:iconLeading>
                <flux:icon name="magnifying-glass" class="h-5 w-5 text-zinc-400" />
            </x-slot:iconLeading>
        </flux:input>
    </div>

    {{-- Households Grid --}}
    @if ($this->households->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon name="home" class="mx-auto mb-4 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No Households') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                @if ($search)
                    {{ __('No households match your search.') }}
                @else
                    {{ __('Get started by creating a household.') }}
                @endif
            </flux:text>
            @if ($this->canCreate && ! $search)
                <flux:button wire:click="create" variant="primary" class="mt-4">
                    {{ __('Create Household') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->households as $household)
                <div
                    wire:key="household-{{ $household->id }}"
                    class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <a
                                href="{{ route('households.show', ['branch' => $branch, 'household' => $household]) }}"
                                class="font-semibold text-zinc-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400"
                            >
                                {{ $household->name }}
                            </a>
                            @if ($household->head)
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Head: :name', ['name' => $household->head->fullName()]) }}
                                </flux:text>
                            @endif
                        </div>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item
                                    href="{{ route('households.show', ['branch' => $branch, 'household' => $household]) }}"
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:menu.item>
                                @can('update', $household)
                                    <flux:menu.item wire:click="edit('{{ $household->id }}')" icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                @endcan
                                @can('delete', $household)
                                    <flux:menu.item wire:click="confirmDelete('{{ $household->id }}')" icon="trash" variant="danger">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <flux:icon name="users" class="h-4 w-4 text-zinc-400" />
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $household->members_count }} {{ __('members') }}
                            </flux:text>
                        </div>
                    </div>

                    @if ($household->address)
                        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ Str::limit($household->address, 50) }}
                        </flux:text>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Create Modal --}}
    <flux:modal wire:model="showCreateModal" class="max-w-md">
        <flux:heading size="lg" class="mb-4">{{ __('Create Household') }}</flux:heading>

        <form wire:submit="store">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Household Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="{{ __('e.g., The Smith Family') }}" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Head of Household') }}</flux:label>
                    <flux:select wire:model="head_id">
                        <flux:select.option value="">{{ __('Select head...') }}</flux:select.option>
                        @foreach ($this->availableHeads as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="head_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Address') }}</flux:label>
                    <flux:textarea wire:model="address" rows="2" placeholder="{{ __('Household address...') }}" />
                    <flux:error name="address" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button type="button" wire:click="cancelCreate" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modal --}}
    <flux:modal wire:model="showEditModal" class="max-w-md">
        <flux:heading size="lg" class="mb-4">{{ __('Edit Household') }}</flux:heading>

        <form wire:submit="update">
            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Household Name') }}</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Head of Household') }}</flux:label>
                    <flux:select wire:model="head_id">
                        <flux:select.option value="">{{ __('Select head...') }}</flux:select.option>
                        @foreach ($this->availableHeads as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="head_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Address') }}</flux:label>
                    <flux:textarea wire:model="address" rows="2" />
                    <flux:error name="address" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button type="button" wire:click="cancelEdit" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Update') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg" class="mb-2">{{ __('Delete Household?') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('This will remove all members from this household. Members will not be deleted.') }}
            </flux:text>

            <div class="mt-6 flex justify-center gap-3">
                <flux:button wire:click="cancelDelete" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button wire:click="delete" variant="danger">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Success Toasts --}}
    <x-toast on="household-created" type="success">
        {{ __('Household created successfully.') }}
    </x-toast>

    <x-toast on="household-updated" type="success">
        {{ __('Household updated successfully.') }}
    </x-toast>

    <x-toast on="household-deleted" type="success">
        {{ __('Household deleted successfully.') }}
    </x-toast>
</div>
