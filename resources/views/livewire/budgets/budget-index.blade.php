<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Budgets') }}</flux:heading>
            <flux:subheading>{{ __('Manage budgets for :branch', ['branch' => $branch->name]) }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if($this->budgets->isNotEmpty())
                <flux:button variant="ghost" wire:click="exportToCsv" icon="arrow-down-tray">
                    {{ __('Export CSV') }}
                </flux:button>
            @endif
            @if($this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('Add Budget') }}
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Budget') }}</flux:text>
                <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                    <flux:icon icon="calculator" class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->budgetStats['total_allocated'], 2) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($this->budgetStats['count']) }} {{ __('budgets') }}</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Spent') }}</flux:text>
                <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                    <flux:icon icon="receipt-percent" class="size-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2">{{ $this->currency->symbol() }}{{ number_format($this->budgetStats['total_spent'], 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Remaining') }}</flux:text>
                <div class="rounded-full {{ $this->budgetStats['total_remaining'] >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }} p-2">
                    <flux:icon icon="banknotes" class="size-4 {{ $this->budgetStats['total_remaining'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 {{ $this->budgetStats['total_remaining'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                {{ $this->currency->symbol() }}{{ number_format($this->budgetStats['total_remaining'], 2) }}
            </flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Utilization') }}</flux:text>
                <div class="rounded-full {{ $this->budgetStats['utilization_percent'] > 100 ? 'bg-red-100 dark:bg-red-900' : ($this->budgetStats['utilization_percent'] > 90 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-purple-100 dark:bg-purple-900') }} p-2">
                    <flux:icon icon="chart-bar" class="size-4 {{ $this->budgetStats['utilization_percent'] > 100 ? 'text-red-600 dark:text-red-400' : ($this->budgetStats['utilization_percent'] > 90 ? 'text-yellow-600 dark:text-yellow-400' : 'text-purple-600 dark:text-purple-400') }}" />
                </div>
            </div>
            <flux:heading size="xl" class="mt-2 {{ $this->budgetStats['utilization_percent'] > 100 ? 'text-red-600 dark:text-red-400' : '' }}">
                {{ $this->budgetStats['utilization_percent'] }}%
            </flux:heading>
            @if($this->budgetStats['over_budget_count'] > 0)
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $this->budgetStats['over_budget_count'] }} {{ __('over budget') }}</flux:text>
            @endif
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name...') }}" icon="magnifying-glass" />
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
        <div class="w-full sm:w-36">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                @foreach($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">
                        {{ ucfirst($status->value) }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-32">
            <flux:select wire:model.live="yearFilter">
                <flux:select.option value="">{{ __('All Years') }}</flux:select.option>
                @foreach($this->availableYears as $year)
                    <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if($this->hasActiveFilters)
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="shrink-0">
                {{ __('Clear') }}
            </flux:button>
        @endif
    </div>

    @if($this->budgets->isEmpty())
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="calculator" class="size-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">{{ __('No budgets found') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">
                @if($this->hasActiveFilters)
                    {{ __('Try adjusting your search or filter criteria.') }}
                @else
                    {{ __('Get started by creating your first budget.') }}
                @endif
            </flux:text>
            @if(!$this->hasActiveFilters && $this->canCreate)
                <flux:button variant="primary" wire:click="create" icon="plus" class="mt-4">
                    {{ __('Add Budget') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Category') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Allocated') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Spent') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Remaining') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Utilization') }}
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
                    @foreach($this->budgets as $budget)
                        <tr wire:key="budget-{{ $budget->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $budget->name }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $budget->start_date?->format('M d') }} - {{ $budget->end_date?->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($budget->category->value) {
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
                                    {{ str_replace('_', ' ', ucfirst($budget->category->value)) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $this->currency->symbol() }}{{ number_format((float) $budget->allocated_amount, 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $this->currency->symbol() }}{{ number_format($budget->actual_spending, 2) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="text-sm {{ $budget->remaining_amount < 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                    {{ $this->currency->symbol() }}{{ number_format($budget->remaining_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        @php
                                            $utilization = min($budget->utilization_percentage, 100);
                                            $barColor = match(true) {
                                                $budget->utilization_percentage > 100 => 'bg-red-500',
                                                $budget->utilization_percentage > 90 => 'bg-yellow-500',
                                                $budget->utilization_percentage > 70 => 'bg-blue-500',
                                                default => 'bg-green-500',
                                            };
                                        @endphp
                                        <div class="h-full {{ $barColor }} transition-all duration-300" style="width: {{ $utilization }}%"></div>
                                    </div>
                                    <span class="text-sm {{ $budget->is_over_budget ? 'text-red-600 dark:text-red-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $budget->utilization_percentage }}%
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge
                                    :color="match($budget->status->value) {
                                        'draft' => 'zinc',
                                        'active' => 'green',
                                        'closed' => 'blue',
                                        default => 'zinc',
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($budget->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        @can('update', $budget)
                                            <flux:menu.item wire:click="edit('{{ $budget->id }}')" icon="pencil">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $budget)
                                            <flux:menu.item wire:click="confirmDelete('{{ $budget->id }}')" icon="trash" variant="danger">
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
    <flux:modal wire:model.self="showCreateModal" name="create-budget" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Add Budget') }}</flux:heading>

            <form wire:submit="store" class="space-y-4">
                <flux:input wire:model="name" :label="__('Budget Name')" placeholder="{{ __('e.g., 2025 Utilities Budget') }}" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="allocated_amount" type="number" step="0.01" min="0.01" :label="__('Allocated Amount (:currency)', ['currency' => $this->currency->code()])" required />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="fiscal_year" type="number" min="2000" max="2100" :label="__('Fiscal Year')" required />
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date')" required />
                </div>

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach($this->statuses as $stat)
                        <flux:select.option value="{{ $stat->value }}">
                            {{ ucfirst($stat->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />

                <!-- Alert Settings -->
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text class="font-medium">{{ __('Budget Alerts') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Get notified when spending approaches budget limits') }}
                            </flux:text>
                        </div>
                        <flux:switch wire:model.live="alerts_enabled" />
                    </div>

                    @if($alerts_enabled)
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="alert_threshold_warning"
                                type="number"
                                min="50"
                                max="95"
                                :label="__('Warning Threshold %')"
                                :description="__('First alert level')"
                            />
                            <flux:input
                                wire:model="alert_threshold_critical"
                                type="number"
                                min="80"
                                max="99"
                                :label="__('Critical Threshold %')"
                                :description="__('Urgent alert level')"
                            />
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="cancelCreate" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Add Budget') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Edit Modal -->
    <flux:modal wire:model.self="showEditModal" name="edit-budget" class="w-full max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Budget') }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <flux:input wire:model="name" :label="__('Budget Name')" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="category" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Select category...') }}</flux:select.option>
                        @foreach($this->categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">
                                {{ str_replace('_', ' ', ucfirst($cat->value)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="allocated_amount" type="number" step="0.01" min="0.01" :label="__('Allocated Amount (:currency)', ['currency' => $this->currency->code()])" required />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:input wire:model="fiscal_year" type="number" min="2000" max="2100" :label="__('Fiscal Year')" required />
                    <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
                    <flux:input wire:model="end_date" type="date" :label="__('End Date')" required />
                </div>

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach($this->statuses as $stat)
                        <flux:select.option value="{{ $stat->value }}">
                            {{ ucfirst($stat->value) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />

                <!-- Alert Settings -->
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text class="font-medium">{{ __('Budget Alerts') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Get notified when spending approaches budget limits') }}
                            </flux:text>
                        </div>
                        <flux:switch wire:model.live="alerts_enabled" />
                    </div>

                    @if($alerts_enabled)
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="alert_threshold_warning"
                                type="number"
                                min="50"
                                max="95"
                                :label="__('Warning Threshold %')"
                                :description="__('First alert level')"
                            />
                            <flux:input
                                wire:model="alert_threshold_critical"
                                type="number"
                                min="80"
                                max="99"
                                :label="__('Critical Threshold %')"
                                :description="__('Urgent alert level')"
                            />
                        </div>
                    @endif
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
    <flux:modal wire:model.self="showDeleteModal" name="delete-budget" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Budget') }}</flux:heading>

            <flux:text>
                {{ __('Are you sure you want to delete the budget ":name"? This action cannot be undone.', ['name' => $deletingBudget?->name ?? '']) }}
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete Budget') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Success Toasts -->
    <x-toast on="budget-created" type="success">
        {{ __('Budget created successfully.') }}
    </x-toast>

    <x-toast on="budget-updated" type="success">
        {{ __('Budget updated successfully.') }}
    </x-toast>

    <x-toast on="budget-deleted" type="success">
        {{ __('Budget deleted successfully.') }}
    </x-toast>
</section>
