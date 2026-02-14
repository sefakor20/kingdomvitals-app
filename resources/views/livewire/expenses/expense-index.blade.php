<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Expenses') }}</flux:heading>
            <flux:subheading>{{ __('Manage expenses for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->expenses->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            <flux:button variant="ghost" :href="route('expenses.recurring', $branch)" icon="arrow-path">
                {{ __('Recurring') }}
            </flux:button>
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Expense') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="receipt-percent" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->expenseStats['total'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($this->expenseStats['count']) }} {{ __('expenses') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pending Approval') }}</flux:text>
                <div class="rounded-full {{ $this->expenseStats['pending'] > 0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-zinc-100 dark:bg-zinc-800' }} p-2">
                    <flux:icon icon="clock" class="size-4 {{ $this->expenseStats['pending'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-600 dark:text-zinc-400' }}" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->expenseStats['pending']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="calendar" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->expenseStats['thisMonth'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Top Category') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="tag" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="lg" class="mt-2">{{ $this->expenseStats['topCategory'] }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by description, vendor, or reference...') }}" icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-40">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                @foreach($this->categories as $category)
                    <flux:select.option value="{{ $category->value }}">
                        {{ str_replace('_', ' ', ucfirst($category->value)) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
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
    </div>

    <!-- Date Filters -->
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-end">
        <div class="flex-1">
            <flux:input wire:model.live="dateFrom" type="date" :label="__('From Date')" />
        </div>
        <div class="flex-1">
            <flux:input wire:model.live="dateTo" type="date" :label="__('To Date')" />
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    @if($this->expenses->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="receipt-percent" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No expenses found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by adding your first expense.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Expense') }}
                </flux:button>
            @endif
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
                            {{ __('Description') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Category') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Amount') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Vendor') }}
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
                    @foreach($this->expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $expense->expense_date?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="max-w-xs truncate text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $expense->description }}
                                    </div>
                                    @if($expense->isFromRecurringExpense())
                                        <flux:badge color="cyan" size="sm">{{ __('Auto') }}</flux:badge>
                                    @endif
                                </div>
                                @if($expense->reference_number)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Ref: {{ $expense->reference_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($expense->category->value) {
                                        'utilities' => 'blue',
                                        'salaries' => 'purple',
                                        'maintenance' => 'yellow',
                                        'supplies' => 'green',
                                        'events' => 'pink',
                                        'missions' => 'cyan',
                                        'transport' => 'orange',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ str_replace('_', ' ', ucfirst($expense->category->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->currency->symbol() }}{{ number_format((float) $expense->amount, 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $expense->vendor_name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($expense->status->value) {
                                        'pending' => 'yellow',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'paid' => 'blue',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($expense->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @if($expense->status->value === 'pending' && $this->canApprove)
                                            <flux:menu.item wire:click="confirmApprove('{{ $expense->id }}')" icon="check">
                                                {{ __('Approve') }}
                                            </flux:menu.item>
                                            <flux:menu.item wire:click="confirmReject('{{ $expense->id }}')" icon="x-mark" variant="danger">
                                                {{ __('Reject') }}
                                            </flux:menu.item>
                                        @endif

                                        @if($expense->status->value === 'approved' && $this->canApprove)
                                            <flux:menu.item wire:click="markAsPaid('{{ $expense->id }}')" icon="banknotes">
                                                {{ __('Mark as Paid') }}
                                            </flux:menu.item>
                                        @endif

                                        @can('update', $expense)
                                            <flux:menu.item wire:click="edit('{{ $expense->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $expense)
                                            <flux:menu.item wire:click="confirmDelete('{{ $expense->id }}')" icon="trash" variant="danger">
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

        @if($this->expenses->hasPages())
            <div class="mt-4">
                {{ $this->expenses->links() }}
            </div>
        @endif
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" name="create-expense" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Expense') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:textarea wire:model="description" :label="__('Description')" rows="2" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:input wire:model="expense_date" type="date" :label="__('Expense Date')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
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

                <flux:input wire:model="vendor_name" :label="__('Vendor Name (optional)')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="reference_number" :label="__('Reference Number (optional)')" />
                    <flux:input wire:model="receipt_url" type="url" :label="__('Receipt URL (optional)')" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Expense') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-expense" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Expense') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:textarea wire:model="description" :label="__('Description')" rows="2" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:input wire:model="expense_date" type="date" :label="__('Expense Date')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
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

                <flux:input wire:model="vendor_name" :label="__('Vendor Name (optional)')" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="reference_number" :label="__('Reference Number (optional)')" />
                    <flux:input wire:model="receipt_url" type="url" :label="__('Receipt URL (optional)')" />
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-expense" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Expense') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this expense of :currency:amount? This action cannot be undone.', ['currency' => $this->currency->symbol(), 'amount' => number_format((float) ($deletingExpense?->amount ?? 0), 2)]) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Expense') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Approve Confirmation Modal -->
    <flux:modal wire:model.self="showApproveModal" name="approve-expense" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Approve Expense') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to approve this expense of :currency:amount?', ['currency' => $this->currency->symbol(), 'amount' => number_format((float) ($approvingExpense?->amount ?? 0), 2)]) }}
            </flux:text>

            @if($approvingExpense)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $approvingExpense->description }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $approvingExpense->vendor_name ?? 'No vendor' }}</div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelApprove">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="approve">
                    {{ __('Approve Expense') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Reject Confirmation Modal -->
    <flux:modal wire:model.self="showRejectModal" name="reject-expense" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Reject Expense') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to reject this expense of :currency:amount?', ['currency' => $this->currency->symbol(), 'amount' => number_format((float) ($rejectingExpense?->amount ?? 0), 2)]) }}
            </flux:text>

            @if($rejectingExpense)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $rejectingExpense->description }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $rejectingExpense->vendor_name ?? 'No vendor' }}</div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelReject">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="reject">
                    {{ __('Reject Expense') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="expense-created" type="success">
        {{ __('Expense added successfully.') }}
    </x-toast>

    <x-toast on="expense-updated" type="success">
        {{ __('Expense updated successfully.') }}
    </x-toast>

    <x-toast on="expense-deleted" type="success">
        {{ __('Expense deleted successfully.') }}
    </x-toast>

    <x-toast on="expense-approved" type="success">
        {{ __('Expense approved successfully.') }}
    </x-toast>

    <x-toast on="expense-rejected" type="success">
        {{ __('Expense rejected.') }}
    </x-toast>

    <x-toast on="expense-paid" type="success">
        {{ __('Expense marked as paid.') }}
    </x-toast>
</section>
