<section class="w-full">
    <div class="mb-6">
        <flux:button variant="ghost" :href="route('prayer-requests.index', $branch)" icon="arrow-left" wire:navigate>
            {{ __('Back to Prayer Requests') }}
        </flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Prayer Request Details -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                @if($editing)
                    <form wire:submit="save" class="space-y-4">
                        <flux:input wire:model="title" :label="__('Title')" required />

                        <flux:textarea wire:model="description" :label="__('Description')" required rows="4" />

                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model="category" :label="__('Category')" required>
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
                            <flux:button variant="ghost" wire:click="cancel" type="button">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button variant="primary" type="submit">
                                {{ __('Save Changes') }}
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="xl">{{ $prayerRequest->title }}</flux:heading>
                                <flux:badge
                                    :color="match($prayerRequest->status->value) {
                                        'open' => 'yellow',
                                        'answered' => 'green',
                                        'cancelled' => 'red',
                                        default => 'zinc',
                                    }"
                                >
                                    {{ ucfirst($prayerRequest->status->value) }}
                                </flux:badge>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
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
                                <span class="flex items-center gap-1">
                                    <flux:icon icon="tag" class="size-4" />
                                    {{ ucfirst(str_replace('_', ' ', $prayerRequest->category->value)) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            @if($this->canEdit)
                                <flux:button variant="ghost" wire:click="edit" icon="pencil" size="sm">
                                    {{ __('Edit') }}
                                </flux:button>
                            @endif
                            @if($this->canDelete)
                                <flux:button variant="ghost" wire:click="confirmDelete" icon="trash" size="sm">
                                    {{ __('Delete') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 prose prose-zinc dark:prose-invert max-w-none">
                        <p class="whitespace-pre-wrap">{{ $prayerRequest->description }}</p>
                    </div>

                    @if($prayerRequest->isAnswered() && $prayerRequest->answer_details)
                        <div class="mt-6 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <div class="flex items-center gap-2 text-green-800 dark:text-green-200">
                                <flux:icon icon="check-circle" class="size-5" />
                                <span class="font-medium">{{ __('Prayer Answered') }}</span>
                                @if($prayerRequest->answered_at)
                                    <span class="text-sm text-green-600 dark:text-green-400">{{ $prayerRequest->answered_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-green-700 dark:text-green-300 whitespace-pre-wrap">{{ $prayerRequest->answer_details }}</p>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Prayer Updates -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">{{ __('Updates & Comments') }}</flux:heading>
                    @if($this->canAddUpdate)
                        <flux:button variant="ghost" wire:click="openAddUpdateModal" icon="plus" size="sm">
                            {{ __('Add Update') }}
                        </flux:button>
                    @endif
                </div>

                @if($this->updates->isEmpty())
                    <div class="text-center py-8">
                        <flux:icon icon="chat-bubble-left-right" class="mx-auto size-10 text-zinc-400" />
                        <flux:text class="mt-2 text-zinc-500">{{ __('No updates yet.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($this->updates as $update)
                            <div wire:key="update-{{ $update->id }}" class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($update->member)
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $update->member->fullName() }}</span>
                                    @endif
                                    <span>{{ $update->created_at->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                                <p class="mt-2 text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $update->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Actions') }}</flux:heading>

                <div class="space-y-2">
                    @if($this->canMarkAnswered)
                        <flux:button variant="primary" wire:click="openAnsweredModal" icon="check-circle" class="w-full">
                            {{ __('Mark as Answered') }}
                        </flux:button>
                    @endif

                    @if($this->canSendPrayerChain)
                        <flux:button variant="ghost" wire:click="sendPrayerChain" icon="megaphone" class="w-full">
                            {{ __('Send Prayer Chain SMS') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <!-- Details Card -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Details') }}</flux:heading>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Submitted By') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                            @if($prayerRequest->isAnonymous())
                                <span class="italic text-zinc-500 dark:text-zinc-400">{{ __('Anonymous') }}</span>
                            @else
                                {{ $prayerRequest->member->fullName() }}
                            @endif
                        </dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Submitted') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $prayerRequest->submitted_at->format('M d, Y') }}
                        </dd>
                    </div>

                    @if($prayerRequest->cluster)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Cluster') }}</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $prayerRequest->cluster->name }}
                            </dd>
                        </div>
                    @endif

                    @if($prayerRequest->answered_at)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Answered') }}</dt>
                            <dd class="font-medium text-green-600 dark:text-green-400">
                                {{ $prayerRequest->answered_at->format('M d, Y') }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Updates') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->updates->count() }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

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

    <!-- Add Update Modal -->
    <flux:modal wire:model.self="showAddUpdateModal" name="add-update" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Update') }}</flux:heading>

            <form wire:submit="addUpdate" class="space-y-4">
                <flux:textarea wire:model="update_content" :label="__('Update')" required rows="4" placeholder="{{ __('Share an update or comment about this prayer request...') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelAddUpdate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Update') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="prayer-request-updated" type="success">
        {{ __('Prayer request updated successfully.') }}
    </x-toast>

    <x-toast on="prayer-request-deleted" type="success">
        {{ __('Prayer request deleted successfully.') }}
    </x-toast>

    <x-toast on="prayer-request-answered" type="success">
        {{ __('Prayer request marked as answered. Praise God!') }}
    </x-toast>

    <x-toast on="prayer-update-added" type="success">
        {{ __('Update added successfully.') }}
    </x-toast>

    <x-toast on="prayer-chain-sent" type="success">
        {{ __('Prayer chain SMS notifications are being sent.') }}
    </x-toast>
</section>
