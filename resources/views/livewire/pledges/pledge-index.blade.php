<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pledges') }}</flux:heading>
            <flux:subheading>{{ __('Manage pledges for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->pledges->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Pledge') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Pledges') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="hand-raised" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->pledgeStats['active']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pledged') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="banknotes" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->pledgeStats['totalPledged'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Fulfilled') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="check-circle" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->pledgeStats['totalFulfilled'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Fulfillment Rate') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="chart-bar" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->pledgeStats['fulfillmentRate'] }}%</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by member or campaign...') }}" icon="magnifying-glass" />
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
            <flux:select wire:model.live="campaignFilter">
                <flux:select.option value="">{{ __('All Campaigns') }}</flux:select.option>
                @foreach($this->campaigns as $campaign)
                    <flux:select.option value="{{ $campaign->id }}">{{ $campaign->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Member Filter -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:select wire:model.live="memberFilter" :label="__('Filter by Member')">
                <flux:select.option :value="null">{{ __('All Members') }}</flux:select.option>
                @foreach($this->members as $member)
                    <flux:select.option value="{{ $member->id }}">{{ $member->fullName() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    @if($this->pledges->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="hand-raised" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No pledges found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first pledge.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Pledge') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Member') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Campaign') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Amount') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Progress') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Frequency') }}
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
                    @foreach($this->pledges as $pledge)
                        <tr wire:key="pledge-{{ $pledge->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($pledge->member)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $pledge->member->fullName() }}" />
                                        <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $pledge->member->fullName() }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($pledge->campaign)
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $pledge->campaign->name }}
                                    </div>
                                @elseif($pledge->campaign_name)
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $pledge->campaign_name }}</div>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                                @if($pledge->start_date)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $pledge->start_date->format('M d, Y') }}
                                        @if($pledge->end_date)
                                            - {{ $pledge->end_date->format('M d, Y') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->currency->symbol() }}{{ number_format((float) $pledge->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-32">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-zinc-600 dark:text-zinc-400">{{ $this->currency->symbol() }}{{ number_format((float) $pledge->amount_fulfilled, 2) }}</span>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $pledge->completionPercentage() }}%</span>
                                    </div>
                                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div
                                            class="h-full rounded-full {{ $pledge->completionPercentage() >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                            style="width: {{ min($pledge->completionPercentage(), 100) }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="zinc" size="sm">
                                    {{ str_replace('_', ' ', ucfirst($pledge->frequency->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
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
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @if($pledge->status->value === 'active' && $this->canRecordPayment)
                                            <flux:menu.item wire:click="openPaymentModal('{{ $pledge->id }}')" icon="banknotes">
                                                {{ __('Record Payment') }}
                                            </flux:menu.item>
                                        @endif

                                        @can('update', $pledge)
                                            @if($pledge->status->value === 'active')
                                                <flux:menu.item wire:click="pausePledge('{{ $pledge->id }}')" icon="pause">
                                                    {{ __('Pause') }}
                                                </flux:menu.item>
                                            @endif

                                            @if($pledge->status->value === 'paused')
                                                <flux:menu.item wire:click="resumePledge('{{ $pledge->id }}')" icon="play">
                                                    {{ __('Resume') }}
                                                </flux:menu.item>
                                            @endif

                                            @if(in_array($pledge->status->value, ['active', 'paused']))
                                                <flux:menu.item wire:click="cancelPledge('{{ $pledge->id }}')" icon="x-circle" variant="danger">
                                                    {{ __('Cancel Pledge') }}
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.item wire:click="edit('{{ $pledge->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $pledge)
                                            <flux:menu.item wire:click="confirmDelete('{{ $pledge->id }}')" icon="trash" variant="danger">
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

        @if($this->pledges->hasPages())
            <div class="mt-4">
                {{ $this->pledges->links() }}
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-pledge" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Pledge') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:select wire:model="member_id" :label="__('Member')" required>
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if($this->activeCampaigns->isNotEmpty())
                    <flux:select wire:model="pledge_campaign_id" :label="__('Campaign')">
                        <flux:select.option value="">{{ __('Select a campaign (optional)...') }}</flux:select.option>
                        @foreach($this->activeCampaigns as $campaign)
                            <flux:select.option value="{{ $campaign->id }}">
                                {{ $campaign->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                @if(!$pledge_campaign_id)
                    <flux:input wire:model="campaign_name" :label="__('Custom Campaign Name')" placeholder="{{ __('e.g., Building Fund 2025') }}" :description="$this->activeCampaigns->isNotEmpty() ? __('Only required if no campaign selected above') : null" />
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Pledge Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:select wire:model="frequency" :label="__('Frequency')" required>
                        @foreach($this->frequencies as $freq)
                            <flux:select.option value="{{ $freq->value }}">
                                {{ str_replace('_', ' ', ucfirst($freq->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Pledge') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-pledge" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Pledge') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:select wire:model="member_id" :label="__('Member')" required>
                    <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                    @foreach($this->members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->fullName() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="pledge_campaign_id" :label="__('Campaign')">
                    <flux:select.option value="">{{ __('No campaign linked') }}</flux:select.option>
                    @foreach($this->campaigns as $campaign)
                        <flux:select.option value="{{ $campaign->id }}">
                            {{ $campaign->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if(!$pledge_campaign_id)
                    <flux:input wire:model="campaign_name" :label="__('Custom Campaign Name')" :description="__('Only required if no campaign selected above')" />
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Pledge Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:select wire:model="frequency" :label="__('Frequency')" required>
                        @foreach($this->frequencies as $freq)
                            <flux:select.option value="{{ $freq->value }}">
                                {{ str_replace('_', ' ', ucfirst($freq->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-pledge" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Pledge') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this pledge of :currency:amount? This action cannot be undone.', ['currency' => $this->currency->symbol(), 'amount' => number_format((float) ($deletingPledge?->amount ?? 0), 2)]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Pledge') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Record Payment Modal -->
    <flux:modal wire:model.self="showPaymentModal" name="record-payment" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Record Payment') }}</flux:heading>

            @if($recordingPaymentFor)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $recordingPaymentFor->campaign?->name ?? $recordingPaymentFor->campaign_name }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $recordingPaymentFor->member?->fullName() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Remaining') }}</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->currency->symbol() }}{{ number_format($recordingPaymentFor->remainingAmount(), 2) }}</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $this->currency->symbol() }}{{ number_format((float) $recordingPaymentFor->amount_fulfilled, 2) }} of {{ $this->currency->symbol() }}{{ number_format((float) $recordingPaymentFor->amount, 2) }}</span>
                            <span class="font-medium">{{ $recordingPaymentFor->completionPercentage() }}%</span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-full rounded-full bg-blue-500"
                                style="width: {{ min($recordingPaymentFor->completionPercentage(), 100) }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="recordPayment" class="space-y-4">
                <flux:input wire:model="paymentAmount" type="number" step="0.01" min="0.01" :label="__('Payment Amount (:currency)', ['currency' => $this->currency->code()])" required autofocus />
                @error('paymentAmount') <div class="text-sm text-red-600">{{ $message }}</div> @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelPayment" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Record Payment') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="pledge-created" type="success">
        {{ __('Pledge added successfully.') }}
    </x-toast>

    <x-toast on="pledge-updated" type="success">
        {{ __('Pledge updated successfully.') }}
    </x-toast>

    <x-toast on="pledge-deleted" type="success">
        {{ __('Pledge deleted successfully.') }}
    </x-toast>

    <x-toast on="payment-recorded" type="success">
        {{ __('Payment recorded successfully.') }}
    </x-toast>

    <x-toast on="pledge-paused" type="success">
        {{ __('Pledge paused.') }}
    </x-toast>

    <x-toast on="pledge-resumed" type="success">
        {{ __('Pledge resumed.') }}
    </x-toast>

    <x-toast on="pledge-cancelled" type="success">
        {{ __('Pledge cancelled.') }}
    </x-toast>
</section>
