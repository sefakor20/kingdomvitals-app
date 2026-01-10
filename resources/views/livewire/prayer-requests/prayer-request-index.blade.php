<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Prayer Requests') }}</flux:heading>
            <flux:subheading>{{ __('Manage prayer requests for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('New Prayer Request') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Requests') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="hand-raised" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Open Requests') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="clock" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['open']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Answered') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['answered']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Answered This Month') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="sparkles" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['answeredThisMonth']) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search prayer requests...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                @foreach($this->categories as $category)
                    <flux:select.option value="{{ $category->value }}">
                        {{ ucfirst(str_replace('_', ' ', $category->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-36">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                @foreach($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">
                        {{ ucfirst($status->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-36">
            <flux:select wire:model.live="privacyFilter">
                <flux:select.option value="">{{ __('All Privacy') }}</flux:select.option>
                @foreach($this->privacyOptions as $privacy)
                    <flux:select.option value="{{ $privacy->value }}">
                        {{ ucfirst(str_replace('_', ' ', $privacy->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->clusters->isNotEmpty())
            <div class="w-full sm:w-40">
                <flux:select wire:model.live="clusterFilter">
                    <flux:select.option value="">{{ __('All Clusters') }}</flux:select.option>
                    @foreach($this->clusters as $cluster)
                        <flux:select.option value="{{ $cluster->id }}">
                            {{ $cluster->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
    </div>

    @if($this->hasActiveFilters)
        <div class="mb-4">
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" size="sm">
                {{ __('Clear Filters') }}
            </flux:button>
        </div>
    @endif

    @if($this->prayerRequests->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="hand-raised" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No prayer requests found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by creating a prayer request.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('New Prayer Request') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->prayerRequests as $prayerRequest)
                <div wire:key="prayer-{{ $prayerRequest->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('prayer-requests.show', [$branch, $prayerRequest]) }}" class="text-lg font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>
                                    {{ $prayerRequest->title }}
                                </a>
                                <flux:badge
                                    :color="match($prayerRequest->status->value) {
                                        'open' => 'yellow',
                                        'answered' => 'green',
                                        'cancelled' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($prayerRequest->status->value) }}
                                </flux:badge>
                                <flux:badge
                                    :color="match($prayerRequest->privacy->value) {
                                        'public' => 'blue',
                                        'private' => 'zinc',
                                        'leaders_only' => 'purple',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $prayerRequest->privacy->value)) }}
                                </flux:badge>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                                <div class="flex items-center gap-1">
                                    <flux:icon icon="tag" class="size-4" />
                                    {{ ucfirst(str_replace('_', ' ', $prayerRequest->category->value)) }}
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:icon icon="user" class="size-4" />
                                    @if($prayerRequest->isAnonymous())
                                        <span class="italic text-zinc-500 dark:text-zinc-400">{{ __('Anonymous') }}</span>
                                    @else
                                        {{ $prayerRequest->member->fullName() }}
                                    @endif
                                </div>
                                @if($prayerRequest->cluster)
                                    <div class="flex items-center gap-1">
                                        <flux:icon icon="user-group" class="size-4" />
                                        {{ $prayerRequest->cluster->name }}
                                    </div>
                                @endif
                                <div class="flex items-center gap-1">
                                    <flux:icon icon="calendar" class="size-4" />
                                    {{ $prayerRequest->submitted_at->format('M d, Y') }}
                                </div>
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $prayerRequest->description }}
                            </p>
                        </div>

                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                            <flux:menu>
                                <flux:menu.item :href="route('prayer-requests.show', [$branch, $prayerRequest])" icon="eye" wire:navigate>
                                    {{ __('View Details') }}
                                </flux:menu.item>

                                @if($prayerRequest->isOpen())
                                    @can('markAnswered', $prayerRequest)
                                        <flux:menu.item wire:click="openAnsweredModal('{{ $prayerRequest->id }}')" icon="check-circle">
                                            {{ __('Mark as Answered') }}
                                        </flux:menu.item>
                                    @endcan

                                    @can('sendPrayerChain', $prayerRequest)
                                        <flux:menu.item wire:click="sendPrayerChain('{{ $prayerRequest->id }}')" icon="megaphone">
                                            {{ __('Send Prayer Chain') }}
                                        </flux:menu.item>
                                    @endcan
                                @endif

                                @can('update', $prayerRequest)
                                    <flux:menu.item wire:click="edit('{{ $prayerRequest->id }}')" icon="pencil">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                @endcan

                                @can('delete', $prayerRequest)
                                    <flux:menu.item wire:click="confirmDelete('{{ $prayerRequest->id }}')" icon="trash" variant="danger">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-prayer-request" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('New Prayer Request') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="title" :label="__('Title')" required placeholder="{{ __('Brief title for this prayer request') }}" />

                <flux:textarea wire:model="description" :label="__('Description')" required rows="4" placeholder="{{ __('Share the details of this prayer request...') }}" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">{{ ucfirst(str_replace('_', ' ', $cat->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="privacy" :label="__('Privacy')" required>
                        @foreach($this->privacyOptions as $priv)
                            <flux:select.option value="{{ $priv->value }}">{{ ucfirst(str_replace('_', ' ', $priv->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:checkbox wire:model.live="is_anonymous" :label="__('Submit Anonymously')" :description="__('Hide the identity of the person submitting this request')" />

                @if(!$is_anonymous)
                    <flux:select wire:model="member_id" :label="__('Submitted By')" required>
                        <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model="cluster_id" :label="__('Cluster (Optional)')">
                    <flux:select.option value="">{{ __('No cluster') }}</flux:select.option>
                    @foreach($this->clusters as $cluster)
                        <flux:select.option value="{{ $cluster->id }}">{{ $cluster->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Request') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-prayer-request" class="w-full max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Prayer Request') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="title" :label="__('Title')" required />

                <flux:textarea wire:model="description" :label="__('Description')" required rows="4" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">{{ ucfirst(str_replace('_', ' ', $cat->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="privacy" :label="__('Privacy')" required>
                        @foreach($this->privacyOptions as $priv)
                            <flux:select.option value="{{ $priv->value }}">{{ ucfirst(str_replace('_', ' ', $priv->value)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:checkbox wire:model.live="is_anonymous" :label="__('Submit Anonymously')" :description="__('Hide the identity of the person submitting this request')" />

                @if(!$is_anonymous)
                    <flux:select wire:model="member_id" :label="__('Submitted By')" required>
                        <flux:select.option value="">{{ __('Select member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model="cluster_id" :label="__('Cluster (Optional)')">
                    <flux:select.option value="">{{ __('No cluster') }}</flux:select.option>
                    @foreach($this->clusters as $cluster)
                        <flux:select.option value="{{ $cluster->id }}">{{ $cluster->name }}</flux:select.option>
                    @endforeach
                </flux:select>

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-prayer-request" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Prayer Request') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this prayer request? This action cannot be undone.') }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Mark as Answered Modal -->
    <flux:modal wire:model.self="showAnsweredModal" name="answered-prayer-request" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Mark as Answered') }}</flux:heading>

            <flux:text>
                {{ __('Record details about how this prayer was answered. This testimony can encourage others in their faith.') }}
            </flux:text>

            <form wire:submit="markAsAnswered" class="space-y-4">
                <flux:textarea wire:model="answer_details" :label="__('Testimony / Answer Details')" rows="4" placeholder="{{ __('Share how this prayer was answered...') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelAnswered" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Mark as Answered') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="prayer-request-created" type="success">
        {{ __('Prayer request created successfully.') }}
    </x-toast>

    <x-toast on="prayer-request-updated" type="success">
        {{ __('Prayer request updated successfully.') }}
    </x-toast>

    <x-toast on="prayer-request-deleted" type="success">
        {{ __('Prayer request deleted successfully.') }}
    </x-toast>

    <x-toast on="prayer-request-answered" type="success">
        {{ __('Prayer request marked as answered. Praise God!') }}
    </x-toast>

    <x-toast on="prayer-chain-sent" type="success">
        {{ __('Prayer chain SMS notifications are being sent.') }}
    </x-toast>
</section>
