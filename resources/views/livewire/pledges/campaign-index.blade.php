<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pledge Campaigns') }}</flux:heading>
            <flux:subheading>{{ __('Manage pledge campaigns for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->campaigns->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('New Campaign') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Campaigns') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="flag" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->campaignStats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Campaigns') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="play" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->campaignStats['active']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pledged') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="banknotes" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->campaignStats['totalPledged'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Goal Progress') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="chart-bar" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->campaignStats['overallProgress'] }}%</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search campaigns...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">
                        {{ ucfirst($status->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                @foreach($this->categories as $category)
                    <flux:select.option value="{{ $category->value }}">
                        {{ str_replace('_', ' ', ucfirst($category->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    @if($this->campaigns->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="flag" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No campaigns found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by creating your first pledge campaign.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('New Campaign') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Campaign') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Category') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Goal') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Progress') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Pledges') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach($this->campaigns as $campaign)
                        <tr wire:key="campaign-{{ $campaign->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $campaign->name }}</div>
                                @if($campaign->start_date)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $campaign->start_date->format('M d, Y') }}
                                        @if($campaign->end_date)
                                            - {{ $campaign->end_date->format('M d, Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($campaign->category)
                                    <flux:badge color="zinc" size="sm">
                                        {{ str_replace('_', ' ', ucfirst($campaign->category->value)) }}
                                    </flux:badge>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if($campaign->goal_amount)
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $this->currency->symbol() }}{{ number_format((float) $campaign->goal_amount, 2) }}
                                    </span>
                                    @if($campaign->goal_participants)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $campaign->goal_participants }} {{ __('participants') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-zinc-400">{{ __('No goal set') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-32">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $this->currency->symbol() }}{{ number_format($campaign->totalPledged(), 2) }}</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $campaign->amountProgress() }}%</span>
                                    </div>
                                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div
                                            class="h-full rounded-full {{ $campaign->amountProgress() >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                            style="width: {{ min($campaign->amountProgress(), 100) }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $campaign->pledges_count }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($campaign->status->value) {
                                        'draft' => 'zinc',
                                        'active' => 'green',
                                        'completed' => 'blue',
                                        'cancelled' => 'red',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($campaign->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="viewDetails('{{ $campaign->id }}')" icon="eye">
                                            {{ __('View Details') }}
                                        </flux:menu.item>

                                        @can('update', $campaign)
                                            @if($campaign->status->value === 'draft')
                                                <flux:menu.item wire:click="activateCampaign('{{ $campaign->id }}')" icon="play">
                                                    {{ __('Activate') }}
                                                </flux:menu.item>
                                            @endif

                                            @if($campaign->status->value === 'active')
                                                <flux:menu.item wire:click="completeCampaign('{{ $campaign->id }}')" icon="check-circle">
                                                    {{ __('Mark Complete') }}
                                                </flux:menu.item>
                                            @endif

                                            @if(in_array($campaign->status->value, ['draft', 'active']))
                                                <flux:menu.item wire:click="cancelCampaign('{{ $campaign->id }}')" icon="x-circle" variant="danger">
                                                    {{ __('Cancel Campaign') }}
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.item wire:click="edit('{{ $campaign->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $campaign)
                                            <flux:menu.item wire:click="confirmDelete('{{ $campaign->id }}')" icon="trash" variant="danger">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-campaign" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('New Campaign') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Campaign Name')" required placeholder="{{ __('e.g., Building Fund 2026') }}" />

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <flux:select wire:model="category" :label="__('Category')">
                    <flux:select.option value="">{{ __('Select category (optional)') }}</flux:select.option>
                    @foreach($this->categories as $cat)
                        <flux:select.option value="{{ $cat->value }}">
                            {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="goal_amount" type="number" step="0.01" min="0" :label="__('Goal Amount (:currency)', ['currency' => $this->currency->code()])" />
                    <flux:input wire:model="goal_participants" type="number" min="0" :label="__('Goal Participants')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Create Campaign') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-campaign" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Campaign') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Campaign Name')" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

                <flux:select wire:model="category" :label="__('Category')">
                    <flux:select.option value="">{{ __('No category') }}</flux:select.option>
                    @foreach($this->categories as $cat)
                        <flux:select.option value="{{ $cat->value }}">
                            {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="goal_amount" type="number" step="0.01" min="0" :label="__('Goal Amount (:currency)', ['currency' => $this->currency->code()])" />
                    <flux:input wire:model="goal_participants" type="number" min="0" :label="__('Goal Participants')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-campaign" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Campaign') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete the campaign ":name"? This will not delete associated pledges but they will no longer be linked to this campaign.', ['name' => $deletingCampaign?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Campaign') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Campaign Detail Modal -->
    <flux:modal wire:model.self="showDetailModal" name="campaign-details" class="w-full max-w-2xl">
        @if($viewingCampaign)
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $viewingCampaign->name }}</flux:heading>
                        @if($viewingCampaign->description)
                            <flux:text class="mt-1">{{ $viewingCampaign->description }}</flux:text>
                        @endif
                    </div>
                    <flux:badge
                        :color="match($viewingCampaign->status->value) {
                            'draft' => 'zinc',
                            'active' => 'green',
                            'completed' => 'blue',
                            'cancelled' => 'red',
                            default => 'zinc',
                        }"
                    >
                        {{ ucfirst($viewingCampaign->status->value) }}
                    </flux:badge>
                </div>

                <!-- Campaign Stats -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Goal') }}</div>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $viewingCampaign->goal_amount ? $this->currency->symbol().number_format((float)$viewingCampaign->goal_amount, 2) : '-' }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Pledged') }}</div>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->currency->symbol() }}{{ number_format($viewingCampaign->totalPledged(), 2) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Fulfilled') }}</div>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $this->currency->symbol() }}{{ number_format($viewingCampaign->totalFulfilled(), 2) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Participants') }}</div>
                        <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $viewingCampaign->participantCount() }}
                            @if($viewingCampaign->goal_participants)
                                / {{ $viewingCampaign->goal_participants }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ __('Amount Progress') }}</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $viewingCampaign->amountProgress() }}%</span>
                    </div>
                    <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div
                            class="h-full rounded-full {{ $viewingCampaign->amountProgress() >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                            style="width: {{ min($viewingCampaign->amountProgress(), 100) }}%"
                        ></div>
                    </div>
                </div>

                <!-- Pledges List -->
                @if($viewingCampaign->pledges->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">{{ __('Pledges') }} ({{ $viewingCampaign->pledges->count() }})</flux:heading>
                        <div class="max-h-64 space-y-2 overflow-y-auto">
                            @foreach($viewingCampaign->pledges as $pledge)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $pledge->member?->fullName() ?? 'Unknown' }}" />
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $pledge->member?->fullName() ?? __('Unknown') }}
                                            </div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $pledge->completionPercentage() }}% {{ __('fulfilled') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $this->currency->symbol() }}{{ number_format((float)$pledge->amount, 2) }}
                                        </div>
                                        <flux:badge
                                            :color="match($pledge->status->value) {
                                                'active' => 'green',
                                                'completed' => 'blue',
                                                'paused' => 'yellow',
                                                'cancelled' => 'red',
                                                default => 'zinc',
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst($pledge->status->value) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-6">
                        <flux:text class="text-zinc-500">{{ __('No pledges yet for this campaign.') }}</flux:text>
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button variant="ghost" wire:click="closeDetails">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="campaign-created" type="success">
        {{ __('Campaign created successfully.') }}
    </x-toast>

    <x-toast on="campaign-updated" type="success">
        {{ __('Campaign updated successfully.') }}
    </x-toast>

    <x-toast on="campaign-deleted" type="success">
        {{ __('Campaign deleted successfully.') }}
    </x-toast>

    <x-toast on="campaign-activated" type="success">
        {{ __('Campaign activated.') }}
    </x-toast>

    <x-toast on="campaign-completed" type="success">
        {{ __('Campaign marked as complete.') }}
    </x-toast>

    <x-toast on="campaign-cancelled" type="success">
        {{ __('Campaign cancelled.') }}
    </x-toast>
</section>
