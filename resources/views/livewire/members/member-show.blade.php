<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('members.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save,photo">
                        <span wire:loading.remove wire:target="save" class="flex items-center gap-1">
                            <flux:icon.check class="size-4" />
                            {{ __('Save') }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endif
            @if($this->canDelete && !$editing)
                <flux:button variant="danger" wire:click="confirmDelete" icon="trash">
                    {{ __('Delete') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Member Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                @if($editing)
                    <div class="flex flex-col items-center gap-2">
                        @if($photo && $photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('New photo') }}" class="size-16 rounded-full object-cover" />
                        @elseif($photo)
                            <div class="flex size-16 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <flux:icon.photo class="size-8 text-zinc-400" />
                            </div>
                        @elseif($existingPhotoUrl)
                            <img src="{{ $existingPhotoUrl }}" alt="{{ $member->fullName() }}" class="size-16 rounded-full object-cover" />
                        @else
                            <flux:avatar size="lg" name="{{ $member->fullName() }}" />
                        @endif
                        <div class="flex items-center gap-2">
                            <span wire:loading wire:target="photo" class="flex items-center gap-1 text-xs text-zinc-500">
                                <flux:icon.arrow-path class="size-3 animate-spin" />
                                {{ __('Uploading...') }}
                            </span>
                            <label wire:loading.remove wire:target="photo" class="cursor-pointer text-xs text-blue-600 hover:underline dark:text-blue-400">
                                {{ __('Upload') }}
                                <input type="file" wire:model="photo" class="hidden" accept="image/*" />
                            </label>
                            @if($existingPhotoUrl || $photo)
                                <button type="button" wire:click="removePhoto" wire:loading.attr="disabled" wire:target="photo,removePhoto" class="text-xs text-red-600 hover:underline disabled:opacity-50 dark:text-red-400">
                                    {{ __('Remove') }}
                                </button>
                            @endif
                        </div>
                        @error('photo') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                @else
                    @if($member->photo_url)
                        <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-16 rounded-full object-cover" />
                    @else
                        <flux:avatar size="lg" name="{{ $member->fullName() }}" />
                    @endif
                @endif
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="first_name" placeholder="{{ __('First Name') }}" class="w-32" />
                            <flux:input wire:model="middle_name" placeholder="{{ __('Middle Name') }}" class="w-32" />
                            <flux:input wire:model="last_name" placeholder="{{ __('Last Name') }}" class="w-32" />
                        </div>
                        @error('first_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @error('last_name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $member->fullName() }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if($editing)
                            <flux:select wire:model="gender" placeholder="{{ __('Gender') }}" class="w-28">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->genders as $genderOption)
                                    <flux:select.option value="{{ $genderOption->value }}">{{ ucfirst($genderOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="marital_status" placeholder="{{ __('Marital Status') }}" class="w-32">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->maritalStatuses as $maritalOption)
                                    <flux:select.option value="{{ $maritalOption->value }}">{{ ucfirst($maritalOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            @if($member->gender)
                                <span>{{ ucfirst($member->gender->value) }}</span>
                            @endif
                            @if($member->gender && $member->marital_status)
                                <span>&bull;</span>
                            @endif
                            @if($member->marital_status)
                                <span>{{ ucfirst($member->marital_status->value) }}</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @if($editing)
                <flux:select wire:model="status" class="w-36">
                    @foreach($this->statuses as $statusOption)
                        <flux:select.option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:badge
                    :color="match($member->status->value) {
                        'active' => 'green',
                        'inactive' => 'zinc',
                        'pending' => 'yellow',
                        'deceased' => 'red',
                        'transferred' => 'blue',
                        default => 'zinc',
                    }"
                    size="lg"
                >
                    {{ ucfirst($member->status->value) }}
                </flux:badge>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Personal Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Personal Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="date_of_birth" />
                        @else
                            {{ $member->date_of_birth?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="gender">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->genders as $genderOption)
                                    <flux:select.option value="{{ $genderOption->value }}">{{ ucfirst($genderOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            {{ $member->gender ? ucfirst($member->gender->value) : '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Marital Status') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="marital_status">
                                <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                                @foreach($this->maritalStatuses as $maritalOption)
                                    <flux:select.option value="{{ $maritalOption->value }}">{{ ucfirst($maritalOption->value) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            {{ $member->marital_status ? ucfirst($member->marital_status->value) : '-' }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Contact Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Contact Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="email" wire:model="email" placeholder="{{ __('Email') }}" />
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @else
                            @if($member->email)
                                <a href="mailto:{{ $member->email }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $member->email }}
                                </a>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="tel" wire:model="phone" placeholder="{{ __('Phone') }}" />
                        @else
                            @if($member->phone)
                                <a href="tel:{{ $member->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $member->phone }}
                                </a>
                            @else
                                -
                            @endif
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Address -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Address') }}</flux:heading>
            @if($editing)
                <div class="grid gap-4">
                    <flux:input wire:model="address" placeholder="{{ __('Street Address') }}" />
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="city" placeholder="{{ __('City') }}" />
                        <flux:input wire:model="state" placeholder="{{ __('State/Region') }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="zip" placeholder="{{ __('ZIP/Postal Code') }}" />
                        <flux:input wire:model="country" placeholder="{{ __('Country') }}" />
                    </div>
                </div>
            @else
                @if($member->address || $member->city || $member->state || $member->zip || $member->country)
                    <address class="not-italic text-sm text-zinc-900 dark:text-zinc-100">
                        @if($member->address)
                            <div>{{ $member->address }}</div>
                        @endif
                        @if($member->city || $member->state || $member->zip)
                            <div>
                                {{ collect([$member->city, $member->state])->filter()->implode(', ') }}
                                @if($member->zip)
                                    {{ $member->zip }}
                                @endif
                            </div>
                        @endif
                        @if($member->country)
                            <div>{{ $member->country }}</div>
                        @endif
                    </address>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No address on file') }}</p>
                @endif
            @endif
        </div>

        <!-- Church Information -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Church Information') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="joined_at" />
                        @else
                            {{ $member->joined_at?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Baptized') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="date" wire:model="baptized_at" />
                        @else
                            {{ $member->baptized_at?->format('M d, Y') ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Primary Branch') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $branch->name }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Clusters / Groups -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Groups & Clusters') }}</flux:heading>
                @if($editing && $this->canEdit)
                    <flux:button variant="ghost" size="sm" wire:click="openAddClusterModal" icon="plus">
                        {{ __('Add to Group') }}
                    </flux:button>
                @endif
            </div>

            @if($this->memberClusters->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Not assigned to any groups') }}
                </p>
            @else
                <div class="space-y-3">
                    @foreach($this->memberClusters as $cluster)
                        <div wire:key="cluster-{{ $cluster->id }}" class="flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex items-center gap-3">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $cluster->name }}
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span>{{ str_replace('_', ' ', ucwords($cluster->cluster_type->value, '_')) }}</span>
                                        @if($cluster->pivot->joined_at)
                                            <span>&bull;</span>
                                            <span>{{ __('Joined') }} {{ \Carbon\Carbon::parse($cluster->pivot->joined_at)->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($editing && $this->canEdit)
                                    <flux:select
                                        wire:change="updateClusterRole('{{ $cluster->id }}', $event.target.value)"
                                        size="sm"
                                        class="w-28"
                                    >
                                        @foreach($this->clusterRoles as $role)
                                            <flux:select.option
                                                value="{{ $role->value }}"
                                                :selected="($cluster->pivot->role->value ?? $cluster->pivot->role) === $role->value"
                                            >
                                                {{ ucfirst($role->value) }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="removeFromCluster('{{ $cluster->id }}')"
                                        wire:confirm="{{ __('Are you sure you want to remove this member from :cluster?', ['cluster' => $cluster->name]) }}"
                                        icon="x-mark"
                                        class="text-red-600 hover:text-red-700"
                                    />
                                @else
                                    <flux:badge
                                        :color="match($cluster->pivot->role->value ?? $cluster->pivot->role) {
                                            'leader' => 'blue',
                                            'assistant' => 'yellow',
                                            'member' => 'zinc',
                                            default => 'zinc',
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst($cluster->pivot->role->value ?? $cluster->pivot->role) }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Notes -->
    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Notes') }}</flux:heading>
        @if($editing)
            <flux:textarea wire:model="notes" placeholder="{{ __('Add notes about this member...') }}" rows="4" />
        @else
            @if($member->notes)
                <p class="whitespace-pre-wrap text-sm text-zinc-900 dark:text-zinc-100">{{ $member->notes }}</p>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notes') }}</p>
            @endif
        @endif
    </div>

    <!-- Activity History -->
    <div class="mt-6">
        <livewire:members.member-activity-log :member="$member" />
    </div>

    <!-- Add to Cluster Modal -->
    <flux:modal wire:model.self="showAddClusterModal" name="add-cluster" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add to Group') }}</flux:heading>

            <form wire:submit="addToCluster" class="space-y-4">
                <div>
                    <flux:select
                        wire:model="selectedClusterId"
                        :label="__('Select Group')"
                    >
                        <flux:select.option value="">{{ __('Choose a group...') }}</flux:select.option>
                        @foreach($this->availableClusters as $cluster)
                            <flux:select.option value="{{ $cluster->id }}">
                                {{ $cluster->name }} ({{ str_replace('_', ' ', ucwords($cluster->cluster_type->value, '_')) }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('selectedClusterId')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <flux:select
                    wire:model="selectedClusterRole"
                    :label="__('Role')"
                >
                    @foreach($this->clusterRoles as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ ucfirst($role->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="date"
                    wire:model="clusterJoinedAt"
                    :label="__('Joined Date')"
                />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeAddClusterModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add to Group') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-member" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Member') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $member->fullName()]) }}
            </p>
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

    <!-- Success Toasts -->
    <x-toast on="member-updated" type="success">
        {{ __('Member updated successfully.') }}
    </x-toast>

    <x-toast on="cluster-added" type="success">
        {{ __('Member added to group successfully.') }}
    </x-toast>

    <x-toast on="cluster-removed" type="success">
        {{ __('Member removed from group.') }}
    </x-toast>

    <x-toast on="cluster-updated" type="success">
        {{ __('Cluster role updated.') }}
    </x-toast>
</section>
