<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Members') }}</flux:heading>
            <flux:subheading>{{ __('Manage members for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        @if($this->canCreate)
            <flux:button variant="primary" wire:click="create" icon="plus">
                {{ __('Add Member') }}
            </flux:button>
        @endif
    </div>

    <!-- View Filter Tabs -->
    @if($this->canRestore)
        <div class="mb-4 flex gap-2">
            <flux:button
                :variant="$viewFilter === 'active' ? 'primary' : 'ghost'"
                wire:click="$set('viewFilter', 'active')"
                size="sm"
            >
                {{ __('Active') }}
            </flux:button>
            <flux:button
                :variant="$viewFilter === 'deleted' ? 'primary' : 'ghost'"
                wire:click="$set('viewFilter', 'deleted')"
                size="sm"
            >
                {{ __('Deleted') }}
            </flux:button>
        </div>
    @endif

    <!-- Search and Filter -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, email, or phone...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->statuses as $statusOption)
                    <flux:select.option value="{{ $statusOption->value }}">
                        {{ ucfirst($statusOption->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($this->members->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="users" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">
                @if($viewFilter === 'deleted')
                    {{ __('No deleted members') }}
                @else
                    {{ __('No members found') }}
                @endif
            </flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($viewFilter === 'deleted')
                    {{ __('There are no deleted members to restore.') }}
                @elseif($search || $statusFilter)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first member.') }}
                @endif
            </flux:text>
            @if($viewFilter === 'active' && !$search && !$statusFilter && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Member') }}
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
                            {{ __('Contact') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Joined') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->members as $member)
                        <tr wire:key="member-{{ $member->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($member->photo_url)
                                        <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-8 rounded-full object-cover" />
                                    @else
                                        <flux:avatar size="sm" name="{{ $member->fullName() }}" />
                                    @endif
                                    <div>
                                        <a href="{{ route('members.show', [$branch, $member]) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                            {{ $member->fullName() }}
                                        </a>
                                        @if($member->gender)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ ucfirst($member->gender->value) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $member->email ?? '-' }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $member->phone ?? '-' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($member->status->value) {
                                        'active' => 'green',
                                        'inactive' => 'zinc',
                                        'pending' => 'yellow',
                                        'deceased' => 'red',
                                        'transferred' => 'blue',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($member->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    @if($viewFilter === 'deleted')
                                        @can('restore', $member)
                                            <flux:button variant="primary" size="sm" wire:click="restore('{{ $member->id }}')" icon="arrow-uturn-left">
                                                {{ __('Restore') }}
                                            </flux:button>
                                        @endcan
                                        @can('forceDelete', $member)
                                            <flux:button variant="danger" size="sm" wire:click="confirmForceDelete('{{ $member->id }}')" icon="trash">
                                                {{ __('Delete Permanently') }}
                                            </flux:button>
                                        @endcan
                                    @else
                                        @can('update', $member)
                                            <flux:button variant="ghost" size="sm" wire:click="edit('{{ $member->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:button>
                                        @endcan

                                        @can('delete', $member)
                                            <flux:button variant="ghost" size="sm" wire:click="confirmDelete('{{ $member->id }}')" icon="trash" class="text-red-600 hover:text-red-700">
                                                {{ __('Delete') }}
                                            </flux:button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-member" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Member') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="first_name" :label="__('First Name')" required />
                    <flux:input wire:model="middle_name" :label="__('Middle Name')" />
                    <flux:input wire:model="last_name" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="date_of_birth" type="date" :label="__('Date of Birth')" />
                    <flux:select wire:model="gender" :label="__('Gender')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->genders as $genderOption)
                            <flux:select.option value="{{ $genderOption->value }}">
                                {{ ucfirst($genderOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="marital_status" :label="__('Marital Status')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->maritalStatuses as $maritalOption)
                            <flux:select.option value="{{ $maritalOption->value }}">
                                {{ ucfirst($maritalOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="address" :label="__('Address')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="city" :label="__('City')" />
                    <flux:input wire:model="state" :label="__('State/Region')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="zip" :label="__('Postal Code')" />
                    <flux:input wire:model="country" :label="__('Country')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="joined_at" type="date" :label="__('Joined Date')" />
                    <flux:input wire:model="baptized_at" type="date" :label="__('Baptized Date')" />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ ucfirst($statusOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <!-- Photo Upload -->
                <div>
                    <flux:field>
                        <flux:label>{{ __('Photo') }}</flux:label>
                        <div class="flex items-center gap-4">
                            @if($photo && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="size-16 rounded-full object-cover" />
                            @else
                                <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon icon="user" class="size-8 text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-300" />
                                <p class="mt-1 text-xs text-zinc-500">{{ __('JPG, PNG, GIF up to 2MB') }}</p>
                            </div>
                        </div>
                        @error('photo') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Member') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-member" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Member') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="first_name" :label="__('First Name')" required />
                    <flux:input wire:model="middle_name" :label="__('Middle Name')" />
                    <flux:input wire:model="last_name" :label="__('Last Name')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="email" type="email" :label="__('Email')" />
                    <flux:input wire:model="phone" :label="__('Phone')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="date_of_birth" type="date" :label="__('Date of Birth')" />
                    <flux:select wire:model="gender" :label="__('Gender')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->genders as $genderOption)
                            <flux:select.option value="{{ $genderOption->value }}">
                                {{ ucfirst($genderOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="marital_status" :label="__('Marital Status')">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach($this->maritalStatuses as $maritalOption)
                            <flux:select.option value="{{ $maritalOption->value }}">
                                {{ ucfirst($maritalOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="address" :label="__('Address')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="city" :label="__('City')" />
                    <flux:input wire:model="state" :label="__('State/Region')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="zip" :label="__('Postal Code')" />
                    <flux:input wire:model="country" :label="__('Country')" />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="joined_at" type="date" :label="__('Joined Date')" />
                    <flux:input wire:model="baptized_at" type="date" :label="__('Baptized Date')" />
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach($this->statuses as $statusOption)
                            <flux:select.option value="{{ $statusOption->value }}">
                                {{ ucfirst($statusOption->value) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <!-- Photo Upload -->
                <div>
                    <flux:field>
                        <flux:label>{{ __('Photo') }}</flux:label>
                        <div class="flex items-center gap-4">
                            @if($photo && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="size-16 rounded-full object-cover" />
                            @elseif($existingPhotoUrl)
                                <img src="{{ $existingPhotoUrl }}" alt="Current photo" class="size-16 rounded-full object-cover" />
                            @else
                                <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon icon="user" class="size-8 text-zinc-400" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-300" />
                                <p class="mt-1 text-xs text-zinc-500">{{ __('JPG, PNG, GIF up to 2MB') }}</p>
                            </div>
                            @if($existingPhotoUrl || $photo)
                                <flux:button variant="ghost" size="sm" wire:click="removePhoto" type="button" class="text-red-600">
                                    {{ __('Remove') }}
                                </flux:button>
                            @endif
                        </div>
                        @error('photo') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-member" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Member') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $deletingMember?->fullName() ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Member') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Permanent Delete Confirmation Modal -->
    <flux:modal wire:model.self="showForceDeleteModal" name="force-delete-member" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Permanently Delete Member') }}</flux:heading>

            <p class="text-sm text-red-600 dark:text-red-400">
                {{ __('This action cannot be undone. The member :name and all their data will be permanently removed from the database.', ['name' => $forceDeleting?->fullName() ?? '']) }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelForceDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="forceDelete">
                    {{ __('Delete Permanently') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="member-created" type="success">
        {{ __('Member added successfully.') }}
    </x-toast>

    <x-toast on="member-updated" type="success">
        {{ __('Member updated successfully.') }}
    </x-toast>

    <x-toast on="member-deleted" type="success">
        {{ __('Member deleted successfully.') }}
    </x-toast>

    <x-toast on="member-restored" type="success">
        {{ __('Member restored successfully.') }}
    </x-toast>

    <x-toast on="member-force-deleted" type="success">
        {{ __('Member permanently deleted.') }}
    </x-toast>
</section>
