<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">{{ __('Recurring Expenses') }}</flux:heading>
            </div>
            <flux:subheading>{{ __('Manage recurring expense templates for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('expenses.index', $branch)" icon="arrow-left">
                {{ __('Back to Expenses') }}
            </flux:button>
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Recurring') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Templates') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="arrow-path" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['total']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active') }}</flux:text>
                <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                    <flux:icon icon="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['active']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Paused') }}</flux:text>
                <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                    <flux:icon icon="pause-circle" class="size-4 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['paused']) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Monthly Projection') }}</flux:text>
                <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                    <flux:icon icon="calculator" class="size-4 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->stats['monthly_projection'], 2) }}</flux:heading>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by description, vendor, or notes...') }}" icon="magnifying-glass" />
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
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    @if($this->recurringExpenses->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="arrow-path" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No recurring expenses found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Create templates to automatically generate expenses on a schedule.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Recurring Expense') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
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
                            {{ __('Frequency') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Next Due') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Generated') }}
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
                    @foreach($this->recurringExpenses as $recurringExpense)
                        <tr wire:key="recurring-expense-{{ $recurringExpense->id }}">
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $recurringExpense->description }}
                                </div>
                                @if($recurringExpense->vendor_name)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $recurringExpense->vendor_name }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($recurringExpense->category->value) {
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
                                    {{ str_replace('_', ' ', ucfirst($recurringExpense->category->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->currency->symbol() }}{{ number_format((float) $recurringExpense->amount, 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ ucfirst($recurringExpense->frequency->value) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if($recurringExpense->isActive())
                                    @if($recurringExpense->next_generation_date?->isToday())
                                        <span class="font-medium text-green-600 dark:text-green-400">{{ __('Today') }}</span>
                                    @elseif($recurringExpense->next_generation_date?->isPast())
                                        <span class="font-medium text-red-600 dark:text-red-400">{{ __('Overdue') }}</span>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">
                                            {{ $recurringExpense->next_generation_date?->format('M d, Y') ?? '-' }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $recurringExpense->total_generated_count }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($recurringExpense->status->value) {
                                        'active' => 'green',
                                        'paused' => 'yellow',
                                        'completed' => 'zinc',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($recurringExpense->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @can('update', $recurringExpense)
                                            <flux:menu.item wire:click="edit('{{ $recurringExpense->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('toggleStatus', $recurringExpense)
                                            @if($recurringExpense->isActive())
                                                <flux:menu.item wire:click="toggleStatus('{{ $recurringExpense->id }}')" icon="pause">
                                                    {{ __('Pause') }}
                                                </flux:menu.item>
                                            @elseif($recurringExpense->isPaused())
                                                <flux:menu.item wire:click="toggleStatus('{{ $recurringExpense->id }}')" icon="play">
                                                    {{ __('Resume') }}
                                                </flux:menu.item>
                                            @endif
                                        @endcan

                                        @can('generateNow', $recurringExpense)
                                            @if($recurringExpense->isActive())
                                                <flux:menu.item wire:click="confirmGenerate('{{ $recurringExpense->id }}')" icon="bolt">
                                                    {{ __('Generate Now') }}
                                                </flux:menu.item>
                                            @endif
                                        @endcan

                                        @can('delete', $recurringExpense)
                                            <flux:menu.item wire:click="confirmDelete('{{ $recurringExpense->id }}')" icon="trash" variant="danger">
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
    <flux:modal wire:model.self="showCreateModal" name="create-recurring-expense" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Recurring Expense') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="description" :label="__('Description')" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                        @foreach($this->paymentMethods as $method)
                            <flux:select.option value="{{ $method->value }}">
                                {{ str_replace('_', ' ', ucfirst($method->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="vendor_name" :label="__('Vendor Name (optional)')" />
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-3">{{ __('Schedule') }}</flux:heading>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model.live="frequency" :label="__('Frequency')" required>
                            @foreach($this->frequencies as $freq)
                                <flux:select.option value="{{ $freq->value }}">
                                    {{ ucfirst($freq->value) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @if($frequency === 'weekly')
                            <flux:select wire:model="day_of_week" :label="__('Day of Week')">
                                <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                                <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                                <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                            </flux:select>
                        @else
                            <flux:input wire:model="day_of_month" type="number" min="1" max="28" :label="__('Day of Month (1-28)')" />
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                        <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
                    </div>
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Recurring Expense') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-recurring-expense" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Recurring Expense') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="description" :label="__('Description')" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (:currency)', ['currency' => $this->currency->code()])" required />
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="payment_method" :label="__('Payment Method')" required>
                        @foreach($this->paymentMethods as $method)
                            <flux:select.option value="{{ $method->value }}">
                                {{ str_replace('_', ' ', ucfirst($method->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="vendor_name" :label="__('Vendor Name (optional)')" />
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-3">{{ __('Schedule') }}</flux:heading>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model.live="frequency" :label="__('Frequency')" required>
                            @foreach($this->frequencies as $freq)
                                <flux:select.option value="{{ $freq->value }}">
                                    {{ ucfirst($freq->value) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @if($frequency === 'weekly')
                            <flux:select wire:model="day_of_week" :label="__('Day of Week')">
                                <flux:select.option value="0">{{ __('Sunday') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('Monday') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('Tuesday') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('Wednesday') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('Thursday') }}</flux:select.option>
                                <flux:select.option value="5">{{ __('Friday') }}</flux:select.option>
                                <flux:select.option value="6">{{ __('Saturday') }}</flux:select.option>
                            </flux:select>
                        @else
                            <flux:input wire:model="day_of_month" type="number" min="1" max="28" :label="__('Day of Month (1-28)')" />
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                        <flux:input wire:model="end_date" type="date" :label="__('End Date (optional)')" />
                    </div>
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />

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
    <flux:modal wire:model.self="showDeleteModal" name="delete-recurring-expense" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Recurring Expense') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete this recurring expense template? This action cannot be undone. Any expenses already generated will remain.') }}
            </flux:text>

            @if($deletingRecurringExpense)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $deletingRecurringExpense->description }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $this->currency->symbol() }}{{ number_format((float) $deletingRecurringExpense->amount, 2) }} / {{ ucfirst($deletingRecurringExpense->frequency->value) }}
                    </div>
                </div>
            @endif

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

    <!-- Generate Now Confirmation Modal -->
    <flux:modal wire:model.self="showGenerateModal" name="generate-recurring-expense" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Generate Expense Now') }}</flux:heading>

            <flux:text>
                {{ __('This will immediately create an expense from this template, regardless of the schedule. The next scheduled generation date will be updated.') }}
            </flux:text>

            @if($generatingRecurringExpense)
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $generatingRecurringExpense->description }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $this->currency->symbol() }}{{ number_format((float) $generatingRecurringExpense->amount, 2) }}
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelGenerate">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="generateNow">
                    {{ __('Generate Now') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="recurring-expense-created" type="success">
        {{ __('Recurring expense created successfully.') }}
    </x-toast>

    <x-toast on="recurring-expense-updated" type="success">
        {{ __('Recurring expense updated successfully.') }}
    </x-toast>

    <x-toast on="recurring-expense-deleted" type="success">
        {{ __('Recurring expense deleted successfully.') }}
    </x-toast>

    <x-toast on="recurring-expense-status-changed" type="success">
        {{ __('Status updated successfully.') }}
    </x-toast>

    <x-toast on="recurring-expense-generated" type="success">
        {{ __('Expense generated successfully.') }}
    </x-toast>

    <x-toast on="recurring-expense-generation-failed" type="error">
        {{ __('Failed to generate expense. Please try again.') }}
    </x-toast>
</section>
