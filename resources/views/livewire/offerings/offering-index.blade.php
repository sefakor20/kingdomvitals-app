<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Regular Offerings') }}</flux:heading>
            <flux:subheading>{{ __('Manage weekly offerings for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->selectedOfferingsCount > 0)
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="document-duplicate">
                        {{ __('Receipts') }} ({{ $this->selectedOfferingsCount }})
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="bulkDownloadReceipts" icon="arrow-down-tray">
                            {{ __('Download All as ZIP') }}
                        </flux:menu.item>
                        @if($this->canSendReceipts)
                            <flux:menu.item wire:click="bulkEmailReceipts" icon="envelope">
                                {{ __('Email to Donors') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif
            @if($this->offerings->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Record Offering') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Offerings') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="gift" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->stats['total'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($this->stats['count']) }} {{ __('offerings') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('This Week') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="calendar-days" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->stats['thisWeek'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="calendar" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->stats['thisMonth'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Count') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="hashtag" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['count']) }}</flux:heading>
        </div>
    </div>

    <!-- View Mode Toggle -->
    <div class="mb-4 flex gap-2">
        <flux:button
            wire:click="setViewMode('list')"
            :variant="$viewMode === 'list' ? 'primary' : 'ghost'"
            icon="list-bullet"
            size="sm"
        >
            {{ __('List View') }}
        </flux:button>
        <flux:button
            wire:click="setViewMode('summary')"
            :variant="$viewMode === 'summary' ? 'primary' : 'ghost'"
            icon="chart-bar"
            size="sm"
        >
            {{ __('Service Summary') }}
        </flux:button>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by donor, reference, or notes...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="serviceFilter">
                <flux:select.option value="">{{ __('All Services') }}</flux:select.option>
                @foreach($this->services as $service)
                    <flux:select.option value="{{ $service->id }}">
                        {{ $service->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="paymentMethodFilter">
                <flux:select.option value="">{{ __('All Methods') }}</flux:select.option>
                @foreach($this->paymentMethods as $method)
                    <flux:select.option value="{{ $method->value }}">
                        {{ str_replace('_', ' ', ucfirst($method->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live="dateFrom" type="date" :label="__('From Date')" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live="dateTo" type="date" :label="__('To Date')" />
        </div>
        <div class="flex-1">
            <flux:select wire:model.live="memberFilter" :label="__('Donor')">
                <flux:select.option :value="null">{{ __('All Donors') }}</flux:select.option>
                <flux:select.option value="anonymous">{{ __('Anonymous Only') }}</flux:select.option>
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

    @if($viewMode === 'summary')
        <!-- Service Summary View -->
        @if($this->serviceSummary->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="chart-bar" class="size-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No service data found') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    {{ __('Offerings grouped by service and date will appear here.') }}
                </flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Date') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Service') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Total Amount') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Count') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Average') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($this->serviceSummary as $summary)
                            <tr wire:key="summary-{{ $summary->service_id }}-{{ $summary->donation_date }}">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ \Carbon\Carbon::parse($summary->donation_date)->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($summary->service)
                                        <flux:badge color="blue" size="sm">
                                            {{ $summary->service->name }}
                                        </flux:badge>
                                    @else
                                        <span class="text-sm text-zinc-400">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        GHS {{ number_format((float) $summary->total, 2) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($summary->count) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                    GHS {{ number_format($summary->count > 0 ? $summary->total / $summary->count : 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ __('Grand Total') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-right">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">
                                    GHS {{ number_format($this->serviceSummary->sum('total'), 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">
                                {{ number_format($this->serviceSummary->sum('count')) }}
                            </td>
                            <td class="px-6 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @else
        <!-- List View -->
        @if($this->offerings->isEmpty())
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="gift" class="size-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No offerings found') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    @if($this->hasActiveFilters)
                        {{ __('Try adjusting your search or filter criteria.') }}
                    @else
                        {{ __('Get started by recording your first offering.') }}
                    @endif
                </flux:text>
                @if(!$this->hasActiveFilters && $this->canCreate)
                    <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                        {{ __('Record Offering') }}
                    </flux:button>
                @endif
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="w-10 px-3 py-3">
                                <flux:checkbox
                                    wire:click="selectAllOfferings"
                                    :checked="count($selectedOfferings) > 0 && count($selectedOfferings) === $this->offerings->count()"
                                />
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Date') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Donor') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Amount') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Payment') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Service') }}
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">{{ __('Actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($this->offerings as $offering)
                            <tr wire:key="offering-{{ $offering->id }}">
                                <td class="whitespace-nowrap px-3 py-4">
                                    <flux:checkbox
                                        wire:click="toggleOfferingSelection('{{ $offering->id }}')"
                                        :checked="in_array($offering->id, $selectedOfferings)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $offering->donation_date?->format('M d, Y') ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($offering->is_anonymous)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" name="?" class="bg-zinc-200 dark:bg-zinc-700" />
                                            <span class="text-sm italic text-zinc-500 dark:text-zinc-400">{{ __('Anonymous') }}</span>
                                        </div>
                                    @elseif($offering->member)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" name="{{ $offering->member->fullName() }}" />
                                            <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $offering->member->fullName() }}</span>
                                        </div>
                                    @elseif($offering->donor_name)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" name="{{ $offering->donor_name }}" />
                                            <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $offering->donor_name }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        GHS {{ number_format((float) $offering->amount, 2) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge color="zinc" size="sm">
                                        {{ str_replace('_', ' ', ucfirst($offering->payment_method->value)) }}
                                    </flux:badge>
                                    @if($offering->reference_number)
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $offering->reference_number }}
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $offering->service?->name ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                        <flux:menu>
                                            @can('generateReceipt', $offering)
                                                <flux:menu.item wire:click="downloadReceipt('{{ $offering->id }}')" icon="document-arrow-down">
                                                    {{ __('Download Receipt') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('sendReceipt', $offering)
                                                @if($offering->canSendReceipt())
                                                    <flux:menu.item wire:click="emailReceipt('{{ $offering->id }}')" icon="envelope">
                                                        {{ __('Email Receipt') }}
                                                        @if($offering->receipt_sent_at)
                                                            <flux:badge size="sm" color="zinc" class="ml-2">{{ __('Sent') }}</flux:badge>
                                                        @endif
                                                    </flux:menu.item>
                                                @endif
                                            @endcan

                                            <flux:menu.separator />

                                            @can('update', $offering)
                                                <flux:menu.item wire:click="edit('{{ $offering->id }}')" icon="pencil">
                                                    {{ __('Edit') }}
                                                </flux:menu.item>
                                            @endcan

                                            @can('delete', $offering)
                                                <flux:menu.item wire:click="confirmDelete('{{ $offering->id }}')" icon="trash" variant="danger">
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

            @if($this->offerings->hasPages())
                <div class="mt-4">
                    {{ $this->offerings->links() }}
                </div>
            @endif
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-offering" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Record Offering') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (GHS)')" required />
                    <flux:input wire:model="donation_date" type="date" :label="__('Offering Date')" required />
                </div>

                <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                    @foreach($this->paymentMethods as $method)
                        <flux:select.option value="{{ $method->value }}">
                            {{ str_replace('_', ' ', ucfirst($method->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:switch wire:model.live="is_anonymous" :label="__('Anonymous Offering')" />

                @if(!$is_anonymous)
                    <flux:select wire:model="member_id" :label="__('Member (optional)')">
                        <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="donor_name" :label="__('Donor Name (if not a member)')" />
                @endif

                <flux:select wire:model="service_id" :label="__('Service (optional)')">
                    <flux:select.option value="">{{ __('Select a service...') }}</flux:select.option>
                    @foreach($this->services as $service)
                        <flux:select.option value="{{ $service->id }}">
                            {{ $service->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="reference_number" :label="__('Reference Number (optional)')" />

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Record Offering') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-offering" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Offering') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (GHS)')" required />
                    <flux:input wire:model="donation_date" type="date" :label="__('Offering Date')" required />
                </div>

                <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                    @foreach($this->paymentMethods as $method)
                        <flux:select.option value="{{ $method->value }}">
                            {{ str_replace('_', ' ', ucfirst($method->value)) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:switch wire:model.live="is_anonymous" :label="__('Anonymous Offering')" />

                @if(!$is_anonymous)
                    <flux:select wire:model="member_id" :label="__('Member (optional)')">
                        <flux:select.option value="">{{ __('Select a member...') }}</flux:select.option>
                        @foreach($this->members as $member)
                            <flux:select.option value="{{ $member->id }}">
                                {{ $member->fullName() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="donor_name" :label="__('Donor Name (if not a member)')" />
                @endif

                <flux:select wire:model="service_id" :label="__('Service (optional)')">
                    <flux:select.option value="">{{ __('Select a service...') }}</flux:select.option>
                    @foreach($this->services as $service)
                        <flux:select.option value="{{ $service->id }}">
                            {{ $service->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="reference_number" :label="__('Reference Number (optional)')" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-offering" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Offering') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this offering of GHS :amount? This action cannot be undone.', ['amount' => number_format((float) ($deletingOffering?->amount ?? 0), 2)]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Offering') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="offering-created" type="success">
        {{ __('Offering recorded successfully.') }}
    </x-toast>

    <x-toast on="offering-updated" type="success">
        {{ __('Offering updated successfully.') }}
    </x-toast>

    <x-toast on="offering-deleted" type="success">
        {{ __('Offering deleted successfully.') }}
    </x-toast>

    <x-toast on="receipt-sent" type="success">
        {{ __('Receipt sent successfully.') }}
    </x-toast>

    <x-toast on="receipt-send-failed" type="error">
        {{ __('Failed to send receipt. Donor may not have an email address.') }}
    </x-toast>

    <x-toast on="bulk-receipts-sent" type="success">
        {{ __('Receipts sent successfully.') }}
    </x-toast>
</section>
