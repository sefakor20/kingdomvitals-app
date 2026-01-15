<section class="w-full">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('clusters.index', $branch) }}" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            @if($this->canEdit)
                @if($editing)
                    <flux:button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
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

    <!-- Cluster Header Card -->
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon icon="rectangle-group" class="size-6 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    @if($editing)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:input wire:model="name" placeholder="{{ __('Cluster Name') }}" class="w-64" />
                        </div>
                        @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    @else
                        <flux:heading size="xl">{{ $cluster->name }}</flux:heading>
                    @endif
                    <div class="mt-1 flex items-center gap-2">
                        @if($editing)
                            <flux:select wire:model="cluster_type" class="w-40">
                                @foreach($this->clusterTypes as $type)
                                    <flux:select.option value="{{ $type->value }}">
                                        {{ str_replace('_', ' ', ucwords($type->value, '_')) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:badge color="zinc" size="sm">
                                {{ str_replace('_', ' ', ucwords($cluster->cluster_type->value, '_')) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
            </div>
            @if($editing)
                <div class="flex items-center gap-2">
                    <flux:switch wire:model="is_active" />
                    <flux:text>{{ __('Active') }}</flux:text>
                </div>
            @else
                <flux:badge
                    :color="$cluster->is_active ? 'green' : 'zinc'"
                    size="lg"
                >
                    {{ $cluster->is_active ? __('Active') : __('Inactive') }}
                </flux:badge>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Cluster Details -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Details') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:textarea wire:model="description" rows="3" />
                        @else
                            {{ $cluster->description ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Meeting Day') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:input wire:model="meeting_day" placeholder="{{ __('e.g., Wednesday') }}" />
                            @else
                                {{ $cluster->meeting_day ?? '-' }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Meeting Time') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($editing)
                                <flux:input type="time" wire:model="meeting_time" />
                            @else
                                {{ $cluster->meeting_time ?? '-' }}
                            @endif
                        </dd>
                    </div>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Meeting Location') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input wire:model="meeting_location" />
                        @else
                            {{ $cluster->meeting_location ?? '-' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:input type="number" wire:model="capacity" min="1" class="w-24" />
                        @else
                            {{ $cluster->capacity ?? __('No limit') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Leadership -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Leadership') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Leader') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="leader_id">
                                <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                                @foreach($this->availableLeaders as $leader)
                                    <flux:select.option value="{{ $leader->id }}">
                                        {{ $leader->fullName() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            @if($cluster->leader)
                                <a href="{{ route('members.show', [$branch, $cluster->leader]) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                    {{ $cluster->leader->fullName() }}
                                </a>
                            @else
                                <span class="text-zinc-400">{{ __('Not assigned') }}</span>
                            @endif
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Assistant Leader') }}</dt>
                    <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                        @if($editing)
                            <flux:select wire:model="assistant_leader_id">
                                <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                                @foreach($this->availableLeaders as $leader)
                                    <flux:select.option value="{{ $leader->id }}">
                                        {{ $leader->fullName() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            @if($cluster->assistantLeader)
                                <a href="{{ route('members.show', [$branch, $cluster->assistantLeader]) }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                                    {{ $cluster->assistantLeader->fullName() }}
                                </a>
                            @else
                                <span class="text-zinc-400">{{ __('Not assigned') }}</span>
                            @endif
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Notes -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Notes') }}</flux:heading>
            @if($editing)
                <flux:textarea wire:model="notes" rows="4" />
            @else
                <p class="text-sm text-zinc-900 dark:text-zinc-100">
                    {{ $cluster->notes ?? __('No notes.') }}
                </p>
            @endif
        </div>

        <!-- Members -->
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">
                    {{ __('Members') }}
                    <span class="text-sm font-normal text-zinc-500">({{ $this->clusterMembers->count() }})</span>
                </flux:heading>
                @if($editing && $this->canEdit)
                    <flux:button variant="primary" size="sm" wire:click="openAddMemberModal" icon="plus">
                        {{ __('Add Member') }}
                    </flux:button>
                @endif
            </div>

            @if($this->clusterMembers->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No members in this cluster.') }}</p>
            @else
                <div class="space-y-3">
                    @foreach($this->clusterMembers as $member)
                        <div wire:key="member-{{ $member->id }}" class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <div class="flex items-center gap-3">
                                @if($member->photo_url)
                                    <img src="{{ $member->photo_url }}" alt="{{ $member->fullName() }}" class="size-8 rounded-full object-cover" />
                                @else
                                    <flux:avatar size="sm" name="{{ $member->fullName() }}" />
                                @endif
                                <div>
                                    <a href="{{ route('members.show', [$branch, $member]) }}" class="text-sm font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                        {{ $member->fullName() }}
                                    </a>
                                    <div class="flex items-center gap-2">
                                        @if($editing)
                                            <flux:select
                                                wire:change="updateMemberRole('{{ $member->id }}', $event.target.value)"
                                                class="!h-6 !min-h-0 !py-0 !text-xs"
                                            >
                                                @foreach($this->clusterRoles as $role)
                                                    <option value="{{ $role->value }}" @selected($member->pivot->role === $role)>
                                                        {{ ucfirst($role->value) }}
                                                    </option>
                                                @endforeach
                                            </flux:select>
                                        @else
                                            <flux:badge
                                                :color="match($member->pivot->role->value) {
                                                    'leader' => 'blue',
                                                    'assistant' => 'purple',
                                                    default => 'zinc',
                                                }"
                                                size="sm"
                                            >
                                                {{ ucfirst($member->pivot->role->value) }}
                                            </flux:badge>
                                        @endif
                                        @if($member->pivot->joined_at)
                                            <span class="text-xs text-zinc-500">
                                                {{ __('Joined') }} {{ \Carbon\Carbon::parse($member->pivot->joined_at)->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($editing)
                                <flux:button variant="ghost" size="sm" wire:click="removeMember('{{ $member->id }}')" class="text-red-600 hover:text-red-700">
                                    <flux:icon icon="x-mark" class="size-4" />
                                </flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Add Member Modal -->
    <flux:modal wire:model.self="showAddMemberModal" name="add-member" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Member to Cluster') }}</flux:heading>

            <form wire:submit="addMember" class="space-y-4">
                <flux:select wire:model="selectedMemberId" :label="__('Member')" required>
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    @foreach($this->availableMembers as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('selectedMemberId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <flux:select wire:model="selectedMemberRole" :label="__('Role')">
                    @foreach($this->clusterRoles as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ ucfirst($role->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input type="date" wire:model="memberJoinedAt" :label="__('Joined Date')" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeAddMemberModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Member') }}
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
                {{ __('Are you sure you want to delete :name? This will remove all member associations. This action cannot be undone.', ['name' => $cluster->name]) }}
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
    <x-toast on="cluster-updated" type="success">
        {{ __('Cluster updated successfully.') }}
    </x-toast>

    <x-toast on="member-added" type="success">
        {{ __('Member added to cluster.') }}
    </x-toast>

    <x-toast on="member-removed" type="success">
        {{ __('Member removed from cluster.') }}
    </x-toast>

    <x-toast on="member-role-updated" type="success">
        {{ __('Member role updated.') }}
    </x-toast>

    <x-toast on="cluster-deleted" type="success">
        {{ __('Cluster deleted successfully.') }}
    </x-toast>
</section>
