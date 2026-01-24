<section class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('duty-rosters.index', $branch) }}" class="hover:text-zinc-700 dark:hover:text-zinc-200" wire:navigate>
                {{ __('Duty Roster') }}
            </a>
            <flux:icon icon="chevron-right" class="size-4" />
            <span>{{ $dutyRoster->service_date->format('M j, Y') }}</span>
        </div>

        <div class="mt-4 flex items-start justify-between">
            <div>
                <flux:heading size="xl" level="1">
                    {{ $dutyRoster->service_date->format('l, F j, Y') }}
                </flux:heading>
                <div class="mt-2 flex items-center gap-3">
                    @if($dutyRoster->service)
                        <flux:badge color="blue" size="sm">{{ $dutyRoster->service->name }}</flux:badge>
                    @endif
                    <flux:badge :color="$dutyRoster->status->color()" size="sm">
                        {{ $dutyRoster->status->label() }}
                    </flux:badge>
                    @if($dutyRoster->is_published)
                        <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                @if($this->canPublish)
                    <flux:button variant="ghost" wire:click="togglePublish" icon="{{ $dutyRoster->is_published ? 'eye-slash' : 'check-circle' }}">
                        {{ $dutyRoster->is_published ? __('Unpublish') : __('Publish') }}
                    </flux:button>
                @endif
                @if($this->canEdit && !$editing)
                    <flux:button variant="primary" wire:click="edit" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if($this->canDelete)
                    <flux:button variant="ghost" wire:click="confirmDelete" icon="trash" class="text-red-600 hover:text-red-700">
                        {{ __('Delete') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info Card -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                @if($editing)
                    <form wire:submit="save" class="space-y-4">
                        <flux:heading size="lg" class="mb-4">{{ __('Service Details') }}</flux:heading>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="service_date" type="date" :label="__('Service Date')" required />
                            <flux:select wire:model="service_id" :label="__('Service Type')">
                                <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                                @foreach($this->services as $service)
                                    <flux:select.option value="{{ $service->id }}">
                                        {{ $service->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:input wire:model="theme" :label="__('Theme')" placeholder="{{ __('e.g., REJOICE, THE LORD DELIVERS') }}" />

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <flux:select wire:model="preacher_id" :label="__('Preacher (Member)')">
                                    <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                                    @foreach($this->members as $member)
                                        <flux:select.option value="{{ $member->id }}">
                                            {{ $member->fullName() }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="preacher_name" :label="__('Or External Preacher')" class="mt-2" />
                            </div>
                            <div>
                                <flux:select wire:model="liturgist_id" :label="__('Liturgist (Member)')">
                                    <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                                    @foreach($this->members as $member)
                                        <flux:select.option value="{{ $member->id }}">
                                            {{ $member->fullName() }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="liturgist_name" :label="__('Or External Liturgist')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Hymn Numbers -->
                        <div>
                            <flux:text class="mb-2 text-sm font-medium">{{ __('Hymn Numbers') }}</flux:text>
                            <div class="flex flex-wrap gap-2">
                                @foreach($hymn_numbers as $index => $hymn)
                                    <div class="flex items-center gap-1" wire:key="show-hymn-{{ $index }}">
                                        <flux:input wire:model="hymn_numbers.{{ $index }}" type="number" min="1" class="w-20" />
                                        <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeHymn({{ $index }})" />
                                    </div>
                                @endforeach
                                <flux:button variant="ghost" size="sm" icon="plus" wire:click="addHymn">
                                    {{ __('Add') }}
                                </flux:button>
                            </div>
                        </div>

                        <flux:textarea wire:model="remarks" :label="__('Remarks')" rows="2" />

                        <flux:select wire:model="status" :label="__('Status')">
                            @foreach($this->statuses as $status)
                                <flux:select.option value="{{ $status->value }}">
                                    {{ $status->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="flex justify-end gap-3 pt-4">
                            <flux:button variant="ghost" wire:click="cancel" type="button">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button variant="primary" type="submit">
                                {{ __('Save Changes') }}
                            </flux:button>
                        </div>
                    </form>
                @else
                    <flux:heading size="lg" class="mb-4">{{ __('Service Details') }}</flux:heading>

                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Theme') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->theme ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Service') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->service?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Preacher') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->preacher_display_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Liturgist') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->liturgist_display_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Hymn Numbers') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->hymn_numbers_display ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Remarks') }}</dt>
                            <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->remarks ?? '-' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>

            <!-- Scriptures Card -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Scripture Readings') }}</flux:heading>
                    @if($this->canEdit)
                        <flux:button variant="ghost" size="sm" icon="plus" wire:click="openAddScriptureModal">
                            {{ __('Add') }}
                        </flux:button>
                    @endif
                </div>

                @if($this->scriptures->isEmpty())
                    <div class="py-8 text-center">
                        <flux:icon icon="book-open" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('No scripture readings added yet.') }}</flux:text>
                    </div>
                @else
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->scriptures as $scripture)
                            <div class="flex items-center justify-between py-3" wire:key="scripture-{{ $scripture->id }}">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $scripture->reference }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($scripture->reading_type)
                                            <flux:badge color="zinc" size="sm">{{ $scripture->reading_type->label() }}</flux:badge>
                                        @endif
                                        @if($scripture->reader_display_name)
                                            <span>{{ __('Reader:') }} {{ $scripture->reader_display_name }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if($this->canEdit)
                                    <div class="flex gap-1">
                                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editScripture('{{ $scripture->id }}')" />
                                        <flux:button variant="ghost" size="sm" icon="trash" class="text-red-600" wire:click="confirmDeleteScripture('{{ $scripture->id }}')" />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Groups Card -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Groups') }}</flux:heading>
                    @if($this->canEdit && $this->availableClusters->isNotEmpty())
                        <flux:button variant="ghost" size="sm" icon="plus" wire:click="openAddClusterModal">
                            {{ __('Add') }}
                        </flux:button>
                    @endif
                </div>

                @if($dutyRoster->clusters->isEmpty())
                    <div class="py-4 text-center">
                        <flux:text class="text-zinc-500">{{ __('No groups assigned.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($dutyRoster->clusters as $cluster)
                            <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800" wire:key="cluster-{{ $cluster->id }}">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cluster->name }}</div>
                                    @if($cluster->pivot->notes)
                                        <div class="text-sm text-zinc-500">{{ $cluster->pivot->notes }}</div>
                                    @endif
                                </div>
                                @if($this->canEdit)
                                    <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="removeCluster('{{ $cluster->id }}')" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Info Card -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Information') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->created_at->format('M j, Y') }}</dd>
                    </div>
                    @if($dutyRoster->published_at)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Published') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->published_at->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    @if($dutyRoster->createdBy)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Created by') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $dutyRoster->createdBy->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Add/Edit Scripture Modal -->
    <flux:modal wire:model.self="showAddScriptureModal" name="add-scripture" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingScripture ? __('Edit Scripture') : __('Add Scripture Reading') }}</flux:heading>

            <form wire:submit="{{ $editingScripture ? 'updateScripture' : 'addScripture' }}" class="space-y-4">
                <flux:input wire:model="scripture_reference" :label="__('Scripture Reference')" placeholder="{{ __('e.g., Jer. 31:7-14') }}" required />

                <flux:select wire:model="scripture_reading_type" :label="__('Reading Type')">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    @foreach($this->readingTypes as $type)
                        <flux:select.option value="{{ $type->value }}">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="scripture_reader_id" :label="__('Reader (Member)')">
                    <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="scripture_reader_name" :label="__('Or External Reader')" placeholder="{{ __('e.g., Mr. John Doe') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeAddScriptureModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $editingScripture ? __('Update') : __('Add Scripture') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Add Cluster Modal -->
    <flux:modal wire:model.self="showAddClusterModal" name="add-cluster" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Group') }}</flux:heading>

            <form wire:submit="addCluster" class="space-y-4">
                <flux:select wire:model="selectedClusterId" :label="__('Group')" required>
                    <flux:select.option value="">{{ __('Select group...') }}</flux:select.option>
                    @foreach($this->availableClusters as $cluster)
                        <flux:select.option value="{{ $cluster->id }}">
                            {{ $cluster->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="clusterNotes" :label="__('Notes')" rows="2" placeholder="{{ __('Optional notes for this group') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="closeAddClusterModal" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Group') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Roster Modal -->
    <flux:modal wire:model.self="showDeleteModal" name="delete-roster" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Duty Roster') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this duty roster? This will also delete all associated scriptures and group assignments. This action cannot be undone.') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Roster') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Scripture Modal -->
    <flux:modal wire:model.self="showDeleteScriptureModal" name="delete-scripture" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Scripture') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to remove this scripture reading?') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDeleteScripture">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deleteScripture">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="roster-updated" type="success">
        {{ __('Duty roster updated successfully.') }}
    </x-toast>

    <x-toast on="roster-published" type="success">
        {{ __('Duty roster published successfully.') }}
    </x-toast>

    <x-toast on="roster-unpublished" type="success">
        {{ __('Duty roster unpublished successfully.') }}
    </x-toast>

    <x-toast on="scripture-added" type="success">
        {{ __('Scripture added successfully.') }}
    </x-toast>

    <x-toast on="scripture-updated" type="success">
        {{ __('Scripture updated successfully.') }}
    </x-toast>

    <x-toast on="scripture-deleted" type="success">
        {{ __('Scripture removed successfully.') }}
    </x-toast>

    <x-toast on="cluster-added" type="success">
        {{ __('Group added successfully.') }}
    </x-toast>

    <x-toast on="cluster-removed" type="success">
        {{ __('Group removed successfully.') }}
    </x-toast>
</section>
