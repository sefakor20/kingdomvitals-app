<div>
    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Announcements') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                {{ __('Send system-wide announcements to tenants.') }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-down-tray" wire:click="exportCsv">
                {{ __('Export') }}
            </flux:button>
            @if($canCreate)
                <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
                    {{ __('New Announcement') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search announcements...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <!-- Announcements Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('Announcement') }}
                    </th>
                    <th scope="col" class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 md:table-cell">
                        {{ __('Target') }}
                    </th>
                    <th scope="col" class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 sm:table-cell">
                        {{ __('Status') }}
                    </th>
                    <th scope="col" class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 lg:table-cell">
                        {{ __('Delivery') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($announcements as $announcement)
                    <tr wire:key="announcement-{{ $announcement->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/30">
                        <td class="px-6 py-4">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-medium text-zinc-900 dark:text-white">{{ $announcement->title }}</span>
                                        <flux:badge size="sm" :color="$announcement->priority->color()">
                                            {{ $announcement->priority->label() }}
                                        </flux:badge>
                                    </div>
                                    <div class="mt-1 text-sm text-zinc-500">
                                        {{ __('by') }} {{ $announcement->superAdmin?->name ?? __('Unknown') }}
                                        &bull;
                                        {{ $announcement->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="hidden px-6 py-4 md:table-cell">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $announcement->target_audience->label() }}
                            </span>
                        </td>
                        <td class="hidden px-6 py-4 sm:table-cell">
                            <flux:badge size="sm" :color="$announcement->status->color()">
                                {{ $announcement->status->label() }}
                            </flux:badge>
                            @if($announcement->isScheduled() && $announcement->scheduled_at)
                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ $announcement->scheduled_at->format('M j, Y g:i A') }}
                                </div>
                            @endif
                        </td>
                        <td class="hidden px-6 py-4 lg:table-cell">
                            @if($announcement->total_recipients > 0)
                                <div class="text-sm">
                                    <span class="text-green-600">{{ $announcement->successful_count }}</span>
                                    /
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ $announcement->total_recipients }}</span>
                                    @if($announcement->failed_count > 0)
                                        <span class="text-red-600">({{ $announcement->failed_count }} {{ __('failed') }})</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-sm text-zinc-400">{{ __('Not sent') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="viewAnnouncement('{{ $announcement->id }}')">
                                        {{ __('View Details') }}
                                    </flux:menu.item>

                                    @if($canCreate && $announcement->canBeEdited())
                                        <flux:menu.item icon="pencil" wire:click="openEditModal('{{ $announcement->id }}')">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                    @endif

                                    @if($canSend && $announcement->canBeSent())
                                        <flux:menu.item icon="paper-airplane" wire:click="sendAnnouncement('{{ $announcement->id }}')">
                                            {{ __('Send Now') }}
                                        </flux:menu.item>
                                    @endif

                                    @if($canSend && $announcement->hasFailedRecipients())
                                        <flux:menu.item icon="arrow-path" wire:click="resendFailed('{{ $announcement->id }}')">
                                            {{ __('Resend Failed') }}
                                        </flux:menu.item>
                                    @endif

                                    @if($canCreate)
                                        <flux:menu.item icon="document-duplicate" wire:click="duplicateAnnouncement('{{ $announcement->id }}')">
                                            {{ __('Duplicate') }}
                                        </flux:menu.item>
                                    @endif

                                    @if($canCreate && $announcement->canBeDeleted())
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $announcement->id }}')">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <flux:icon.megaphone class="mx-auto size-12 text-zinc-400" />
                            <flux:heading size="lg" class="mt-4">{{ __('No announcements yet') }}</flux:heading>
                            <flux:text class="mt-2 text-zinc-500">
                                {{ __('Announcements you create will appear here.') }}
                            </flux:text>
                            @if($canCreate)
                                <flux:button variant="primary" class="mt-4" wire:click="$set('showCreateModal', true)">
                                    {{ __('Create First Announcement') }}
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($announcements->hasPages())
        <div class="mt-6">
            {{ $announcements->links() }}
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create Announcement') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Create a new announcement to send to tenants.') }}
                </flux:text>
            </div>

            <form wire:submit="createAnnouncement" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="title" placeholder="{{ __('Enter announcement title...') }}" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Content') }}</flux:label>
                    <flux:textarea wire:model="content" rows="6" placeholder="{{ __('Enter announcement message...') }}" />
                    <flux:error name="content" />
                </flux:field>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Target Audience') }}</flux:label>
                        <flux:select wire:model.live="targetAudience">
                            @foreach($audiences as $audience)
                                <option value="{{ $audience->value }}">{{ $audience->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="targetAudience" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Priority') }}</flux:label>
                        <flux:select wire:model="priority">
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="priority" />
                    </flux:field>
                </div>

                @if($targetAudience === 'specific')
                    <flux:field>
                        <flux:label>{{ __('Select Tenants') }}</flux:label>
                        <flux:select wire:model="specificTenantIds" multiple class="h-32">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:description>{{ __('Hold Ctrl/Cmd to select multiple tenants.') }}</flux:description>
                        <flux:error name="specificTenantIds" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:checkbox wire:model.live="scheduleForLater" label="{{ __('Schedule for later') }}" />
                </flux:field>

                @if($scheduleForLater)
                    <flux:field>
                        <flux:label>{{ __('Scheduled Date & Time') }}</flux:label>
                        <flux:input type="datetime-local" wire:model="scheduledAt" min="{{ now()->format('Y-m-d\TH:i') }}" />
                        <flux:error name="scheduledAt" />
                    </flux:field>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $scheduleForLater ? __('Schedule Announcement') : __('Save as Draft') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model="showEditModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit Announcement') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    {{ __('Update announcement details.') }}
                </flux:text>
            </div>

            <form wire:submit="updateAnnouncement" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="title" placeholder="{{ __('Enter announcement title...') }}" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Content') }}</flux:label>
                    <flux:textarea wire:model="content" rows="6" placeholder="{{ __('Enter announcement message...') }}" />
                    <flux:error name="content" />
                </flux:field>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Target Audience') }}</flux:label>
                        <flux:select wire:model.live="targetAudience">
                            @foreach($audiences as $audience)
                                <option value="{{ $audience->value }}">{{ $audience->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="targetAudience" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Priority') }}</flux:label>
                        <flux:select wire:model="priority">
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="priority" />
                    </flux:field>
                </div>

                @if($targetAudience === 'specific')
                    <flux:field>
                        <flux:label>{{ __('Select Tenants') }}</flux:label>
                        <flux:select wire:model="specificTenantIds" multiple class="h-32">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="specificTenantIds" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:checkbox wire:model.live="scheduleForLater" label="{{ __('Schedule for later') }}" />
                </flux:field>

                @if($scheduleForLater)
                    <flux:field>
                        <flux:label>{{ __('Scheduled Date & Time') }}</flux:label>
                        <flux:input type="datetime-local" wire:model="scheduledAt" min="{{ now()->format('Y-m-d\TH:i') }}" />
                        <flux:error name="scheduledAt" />
                    </flux:field>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showEditModal', false)" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- View Modal -->
    <flux:modal wire:model="showViewModal" class="max-w-3xl">
        @if($viewingAnnouncement)
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $viewingAnnouncement->title }}</flux:heading>
                            <flux:badge size="sm" :color="$viewingAnnouncement->priority->color()">
                                {{ $viewingAnnouncement->priority->label() }}
                            </flux:badge>
                        </div>
                        <flux:text class="mt-1 text-zinc-500">
                            {{ __('Created by') }} {{ $viewingAnnouncement->superAdmin?->name ?? __('Unknown') }}
                            {{ __('on') }} {{ $viewingAnnouncement->created_at->format('M j, Y g:i A') }}
                        </flux:text>
                    </div>
                    <flux:badge size="lg" :color="$viewingAnnouncement->status->color()">
                        {{ $viewingAnnouncement->status->label() }}
                    </flux:badge>
                </div>

                <!-- Content -->
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <flux:text class="whitespace-pre-wrap">{{ $viewingAnnouncement->content }}</flux:text>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Target') }}</flux:text>
                        <flux:text class="mt-1 font-semibold">{{ $viewingAnnouncement->target_audience->label() }}</flux:text>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wider text-zinc-500">{{ __('Total') }}</flux:text>
                        <flux:text class="mt-1 font-semibold">{{ $viewingAnnouncement->total_recipients }}</flux:text>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wider text-green-600">{{ __('Successful') }}</flux:text>
                        <flux:text class="mt-1 font-semibold text-green-600">{{ $viewingAnnouncement->successful_count }}</flux:text>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wider text-red-600">{{ __('Failed') }}</flux:text>
                        <flux:text class="mt-1 font-semibold text-red-600">{{ $viewingAnnouncement->failed_count }}</flux:text>
                    </div>
                </div>

                <!-- Recipients List -->
                @if($viewingAnnouncement->recipients->count() > 0)
                    <div>
                        <flux:heading size="sm" class="mb-3">{{ __('Recipients') }}</flux:heading>
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-900/50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Tenant') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Email') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach($viewingAnnouncement->recipients as $recipient)
                                        <tr wire:key="recipient-{{ $recipient->id }}">
                                            <td class="px-4 py-2 text-sm">{{ $recipient->tenant?->name ?? __('Unknown') }}</td>
                                            <td class="px-4 py-2 text-sm text-zinc-500">{{ $recipient->email }}</td>
                                            <td class="px-4 py-2">
                                                <flux:badge size="sm" :color="$recipient->delivery_status->color()">
                                                    {{ $recipient->delivery_status->label() }}
                                                </flux:badge>
                                                @if($recipient->isFailed() && $recipient->error_message)
                                                    <div class="mt-1 text-xs text-red-500" title="{{ $recipient->error_message }}">
                                                        {{ Str::limit($recipient->error_message, 30) }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end pt-4">
                    <flux:button variant="ghost" wire:click="$set('showViewModal', false)">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="size-8 text-red-600 dark:text-red-400" />
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ __('Delete Announcement') }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ __('Are you sure you want to delete this announcement? This action cannot be undone.') }}
                    </flux:text>
                </div>
            </div>

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" wire:click="$set('showDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" class="flex-1" wire:click="deleteAnnouncement">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Toast Notifications -->
    <x-toast on="announcement-created" type="success">
        {{ __('Announcement created successfully.') }}
    </x-toast>
    <x-toast on="announcement-updated" type="success">
        {{ __('Announcement updated successfully.') }}
    </x-toast>
    <x-toast on="announcement-deleted" type="success">
        {{ __('Announcement deleted successfully.') }}
    </x-toast>
    <x-toast on="announcement-sending" type="success">
        {{ __('Announcement is being sent. You can monitor progress in the list.') }}
    </x-toast>
    <x-toast on="announcement-duplicated" type="success">
        {{ __('Announcement duplicated successfully.') }}
    </x-toast>
    <x-toast on="announcement-resending" type="success">
        {{ __('Resending failed announcements...') }}
    </x-toast>
    <x-toast on="error" type="error" :message="true" />
</div>
