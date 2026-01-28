<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Donations') }}</flux:heading>
            <flux:subheading>{{ __('Manage donations for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->selectedDonationsCount > 0)
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="document-duplicate">
                        {{ __('Receipts') }} ({{ $this->selectedDonationsCount }})
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
            @if($this->donations->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Record Donation') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Donations') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="banknotes" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->donationStats['total'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($this->donationStats['count']) }} {{ __('donations') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="calendar" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->donationStats['thisMonth'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tithes') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="currency-dollar" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->donationStats['tithes'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Offerings') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="gift" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">GHS {{ number_format($this->donationStats['offerings'], 2) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by donor, reference, or notes...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                @foreach($this->donationTypes as $type)
                    <flux:select.option value="{{ $type->value }}">
                        {{ str_replace('_', ' ', ucfirst($type->value)) }}
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

    @if($this->donations->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="banknotes" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No donations found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by recording your first donation.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Record Donation') }}
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
                                wire:click="selectAllDonations"
                                :checked="count($selectedDonations) > 0 && count($selectedDonations) === $this->donations->count()"
                            />
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Donor') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
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
                    @foreach($this->donations as $donation)
                        <tr wire:key="donation-{{ $donation->id }}">
                            <td class="whitespace-nowrap px-3 py-4">
                                <flux:checkbox
                                    wire:click="toggleDonationSelection('{{ $donation->id }}')"
                                    :checked="in_array($donation->id, $selectedDonations)"
                                />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $donation->donation_date?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($donation->is_anonymous)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="?" class="bg-zinc-200 dark:bg-zinc-700" />
                                        <span class="text-sm italic text-zinc-500 dark:text-zinc-400">{{ __('Anonymous') }}</span>
                                    </div>
                                @elseif($donation->member)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $donation->member->fullName() }}" />
                                        <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $donation->member->fullName() }}</span>
                                    </div>
                                @elseif($donation->donor_name)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="sm" name="{{ $donation->donor_name }}" />
                                        <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $donation->donor_name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($donation->donation_type->value) {
                                        'tithe' => 'purple',
                                        'offering' => 'blue',
                                        'building_fund' => 'green',
                                        'missions' => 'yellow',
                                        'special' => 'pink',
                                        'welfare' => 'cyan',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ str_replace('_', ' ', ucfirst($donation->donation_type->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    GHS {{ number_format((float) $donation->amount, 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="zinc" size="sm">
                                    {{ str_replace('_', ' ', ucfirst($donation->payment_method->value)) }}
                                </flux:badge>
                                @if($donation->reference_number)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $donation->reference_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $donation->service?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @can('generateReceipt', $donation)
                                            <flux:menu.item wire:click="downloadReceipt('{{ $donation->id }}')" icon="document-arrow-down">
                                                {{ __('Download Receipt') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('sendReceipt', $donation)
                                            @if($donation->canSendReceipt())
                                                <flux:menu.item wire:click="emailReceipt('{{ $donation->id }}')" icon="envelope">
                                                    {{ __('Email Receipt') }}
                                                    @if($donation->receipt_sent_at)
                                                        <flux:badge size="sm" color="zinc" class="ml-2">{{ __('Sent') }}</flux:badge>
                                                    @endif
                                                </flux:menu.item>
                                            @endif
                                        @endcan

                                        <flux:menu.separator />

                                        @can('update', $donation)
                                            <flux:menu.item wire:click="edit('{{ $donation->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $donation)
                                            <flux:menu.item wire:click="confirmDelete('{{ $donation->id }}')" icon="trash" variant="danger">
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

        @if($this->donations->hasPages())
            <div class="mt-4">
                {{ $this->donations->links() }}
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-donation" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Record Donation') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (GHS)')" required />
                    <flux:input wire:model="donation_date" type="date" :label="__('Donation Date')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="donation_type" :label="__('Donation Type')" required>
                        @foreach($this->donationTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ str_replace('_', ' ', ucfirst($type->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                        @foreach($this->paymentMethods as $method)
                            <flux:select.option value="{{ $method->value }}">
                                {{ str_replace('_', ' ', ucfirst($method->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:switch wire:model.live="is_anonymous" :label="__('Anonymous Donation')" />

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

                <flux:select wire:model="service_id" :label="__('Associated Service (optional)')">
                    <flux:select.option value="">{{ __('Select a service...') }}</flux:select.option>
                    @foreach($this->services as $service)
                        <flux:select.option value="{{ $service->id }}">
                            {{ $service->name }} ({{ $service->date?->format('M d, Y') }})
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
                        {{ __('Record Donation') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-donation" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Donation') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (GHS)')" required />
                    <flux:input wire:model="donation_date" type="date" :label="__('Donation Date')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="donation_type" :label="__('Donation Type')" required>
                        @foreach($this->donationTypes as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ str_replace('_', ' ', ucfirst($type->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                        @foreach($this->paymentMethods as $method)
                            <flux:select.option value="{{ $method->value }}">
                                {{ str_replace('_', ' ', ucfirst($method->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:switch wire:model.live="is_anonymous" :label="__('Anonymous Donation')" />

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

                <flux:select wire:model="service_id" :label="__('Associated Service (optional)')">
                    <flux:select.option value="">{{ __('Select a service...') }}</flux:select.option>
                    @foreach($this->services as $service)
                        <flux:select.option value="{{ $service->id }}">
                            {{ $service->name }} ({{ $service->date?->format('M d, Y') }})
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-donation" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Donation') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this donation of GHS :amount? This action cannot be undone.', ['amount' => number_format((float) ($deletingDonation?->amount ?? 0), 2)]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Donation') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="donation-created" type="success">
        {{ __('Donation recorded successfully.') }}
    </x-toast>

    <x-toast on="donation-updated" type="success">
        {{ __('Donation updated successfully.') }}
    </x-toast>

    <x-toast on="donation-deleted" type="success">
        {{ __('Donation deleted successfully.') }}
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
