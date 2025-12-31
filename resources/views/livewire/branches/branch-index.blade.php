<section class="w-full ">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Branches') }}</flux:heading>
            <flux:subheading>{{ __('Manage your organization branches and locations') }}</flux:subheading>
        </div>

        @can('create', \App\Models\Tenant\Branch::class)
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('Add Branch') }}
            </flux:button>
        @endcan
    </div>

    {{--  <flux:separator class="mb-6" />  --}}

    @if($this->branches->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="building-office" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No branches yet') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('Get started by creating your first branch.') }}</flux:text>
            @can('create', \App\Models\Tenant\Branch::class)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Branch') }}
                </flux:button>
            @endcan
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->branches as $branch)
                <div wire:key="branch-{{ $branch->id }}" class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-center gap-4">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon icon="building-office" class="size-5 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm">{{ $branch->name }}</flux:heading>
                                @if($branch->is_main)
                                    <flux:badge color="blue" size="sm">{{ __('Main') }}</flux:badge>
                                @endif
                                <flux:badge
                                    :color="match($branch->status->value) {
                                        'active' => 'green',
                                        'inactive' => 'zinc',
                                        'pending' => 'yellow',
                                        'suspended' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($branch->status->value) }}
                                </flux:badge>
                            </div>
                            @if($branch->city || $branch->state)
                                <flux:text size="sm" class="text-zinc-500">
                                    {{ collect([$branch->city, $branch->state])->filter()->implode(', ') }}
                                </flux:text>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @can('update', $branch)
                            <flux:button variant="ghost" size="sm" wire:click="edit('{{ $branch->id }}')" icon="pencil">
                                {{ __('Edit') }}
                            </flux:button>
                        @endcan

                        @can('delete', $branch)
                            @unless($branch->is_main)
                                <flux:button variant="ghost" size="sm" wire:click="confirmDelete('{{ $branch->id }}')" icon="trash" class="text-red-600 hover:text-red-700">
                                    {{ __('Delete') }}
                                </flux:button>
                            @endunless
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-branch" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Create Branch') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model.live="name" :label="__('Name')" placeholder="e.g. West Campus" required />
                <flux:input wire:model="slug" :label="__('Slug')" placeholder="e.g. west-campus" required />

                <flux:input wire:model="address" :label="__('Address')" placeholder="123 Main Street" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="city" :label="__('City')" placeholder="Accra" />
                    <flux:input wire:model="state" :label="__('State/Region')" placeholder="Greater Accra" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="zip" :label="__('Postal Code')" placeholder="00233" />
                    <flux:input wire:model="country" :label="__('Country')" placeholder="Ghana" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" placeholder="branch@example.com" />
                    <flux:input wire:model="phone" :label="__('Phone')" placeholder="+233 XX XXX XXXX" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="capacity" type="number" :label="__('Capacity')" placeholder="500" />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ ucfirst($statusOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Branch') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-branch" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Branch') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model.live="name" :label="__('Name')" required />
                <flux:input wire:model="slug" :label="__('Slug')" required />

                <flux:input wire:model="address" :label="__('Address')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="city" :label="__('City')" />
                    <flux:input wire:model="state" :label="__('State/Region')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="zip" :label="__('Postal Code')" />
                    <flux:input wire:model="country" :label="__('Country')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="capacity" type="number" :label="__('Capacity')" />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ ucfirst($statusOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-branch" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Branch') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $deletingBranch?->name ?? '']) }}
            </flux:text>

            @error('delete')
                <flux:callout variant="danger">
                    {{ $message }}
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Branch') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
