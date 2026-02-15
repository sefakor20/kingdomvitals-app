<?php

declare(strict_types=1);

namespace App\Livewire\Budgets;

use App\Enums\BudgetStatus;
use App\Enums\Currency;
use App\Enums\ExpenseCategory;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class BudgetIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $yearFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public string $category = '';

    public string $allocated_amount = '';

    public string $fiscal_year = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $status = 'draft';

    public string $notes = '';

    // Alert settings
    public bool $alerts_enabled = true;

    public int $alert_threshold_warning = 75;

    public int $alert_threshold_critical = 90;

    public ?Budget $editingBudget = null;

    public ?Budget $deletingBudget = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Budget::class, $branch]);
        $this->branch = $branch;
        $this->fiscal_year = date('Y');
    }

    #[Computed]
    public function budgets(): Collection
    {
        $query = Budget::withActualSpending()
            ->where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'notes']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'yearFilter', 'fiscal_year');

        return $query->with('creator')
            ->orderBy('fiscal_year', 'desc')
            ->orderBy('category')
            ->get();
    }

    #[Computed]
    public function categories(): array
    {
        return ExpenseCategory::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return BudgetStatus::cases();
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Budget::where('branch_id', $this->branch->id)
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();

        $currentYear = (int) date('Y');
        if (! in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        rsort($years);

        return $years;
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Budget::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Budget::class, $this->branch]);
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function budgetStats(): array
    {
        $budgets = $this->budgets;

        $totalAllocated = $budgets->sum('allocated_amount');
        $totalSpent = $budgets->sum(fn ($b) => $b->actual_spending);
        $totalRemaining = $totalAllocated - $totalSpent;
        $utilizationPercent = $totalAllocated > 0
            ? round(($totalSpent / $totalAllocated) * 100, 1)
            : 0;
        $overBudgetCount = $budgets->filter(fn ($b) => $b->is_over_budget)->count();

        return [
            'total_allocated' => (float) $totalAllocated,
            'total_spent' => (float) $totalSpent,
            'total_remaining' => (float) $totalRemaining,
            'utilization_percent' => $utilizationPercent,
            'over_budget_count' => $overBudgetCount,
            'count' => $budgets->count(),
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->categoryFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->statusFilter)) {
            return true;
        }
        return $this->isFilterActive($this->yearFilter);
    }

    protected function rules(): array
    {
        $categories = collect(ExpenseCategory::cases())->pluck('value')->implode(',');
        $statuses = collect(BudgetStatus::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.$categories],
            'allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:'.$statuses],
            'notes' => ['nullable', 'string'],
            'alerts_enabled' => ['boolean'],
            'alert_threshold_warning' => ['required_if:alerts_enabled,true', 'integer', 'min:50', 'max:95'],
            'alert_threshold_critical' => ['required_if:alerts_enabled,true', 'integer', 'min:80', 'max:99', 'gt:alert_threshold_warning'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Budget::class, $this->branch]);
        $this->resetForm();

        $year = (int) date('Y');
        $this->fiscal_year = (string) $year;
        $this->start_date = "{$year}-01-01";
        $this->end_date = "{$year}-12-31";
        $this->status = 'draft';

        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Budget::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = tenant()->getCurrencyCode();
        $validated['created_by'] = null;

        if ($validated['notes'] === '') {
            $validated['notes'] = null;
        }

        // Set alert defaults if alerts are disabled
        if (! $validated['alerts_enabled']) {
            $validated['alert_threshold_warning'] = 75;
            $validated['alert_threshold_critical'] = 90;
        }

        Budget::create($validated);

        unset($this->budgets);
        unset($this->budgetStats);
        unset($this->availableYears);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('budget-created');
    }

    public function edit(Budget $budget): void
    {
        $this->authorize('update', $budget);
        $this->editingBudget = $budget;
        $this->fill([
            'name' => $budget->name,
            'category' => $budget->category->value,
            'allocated_amount' => (string) $budget->allocated_amount,
            'fiscal_year' => (string) $budget->fiscal_year,
            'start_date' => $budget->start_date?->format('Y-m-d'),
            'end_date' => $budget->end_date?->format('Y-m-d'),
            'status' => $budget->status->value,
            'notes' => $budget->notes ?? '',
            'alerts_enabled' => $budget->alerts_enabled,
            'alert_threshold_warning' => $budget->alert_threshold_warning,
            'alert_threshold_critical' => $budget->alert_threshold_critical,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingBudget);
        $validated = $this->validate();

        if ($validated['notes'] === '') {
            $validated['notes'] = null;
        }

        // Set alert defaults if alerts are disabled
        if (! $validated['alerts_enabled']) {
            $validated['alert_threshold_warning'] = 75;
            $validated['alert_threshold_critical'] = 90;
        }

        $this->editingBudget->update($validated);

        unset($this->budgets);
        unset($this->budgetStats);
        unset($this->availableYears);

        $this->showEditModal = false;
        $this->editingBudget = null;
        $this->resetForm();
        $this->dispatch('budget-updated');
    }

    public function confirmDelete(Budget $budget): void
    {
        $this->authorize('delete', $budget);
        $this->deletingBudget = $budget;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingBudget);

        $this->deletingBudget->delete();

        unset($this->budgets);
        unset($this->budgetStats);
        unset($this->availableYears);

        $this->showDeleteModal = false;
        $this->deletingBudget = null;
        $this->dispatch('budget-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingBudget = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingBudget = null;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'categoryFilter', 'statusFilter', 'yearFilter',
        ]);
        unset($this->budgets);
        unset($this->budgetStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Budget::class, $this->branch]);

        $budgets = $this->budgets;

        $filename = sprintf(
            'budgets_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($budgets): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Category',
                'Fiscal Year',
                'Start Date',
                'End Date',
                'Allocated Amount',
                'Actual Spending',
                'Remaining',
                'Utilization %',
                'Status',
                'Notes',
            ]);

            foreach ($budgets as $budget) {
                fputcsv($handle, [
                    $budget->name,
                    str_replace('_', ' ', ucfirst($budget->category->value)),
                    $budget->fiscal_year,
                    $budget->start_date?->format('Y-m-d') ?? '',
                    $budget->end_date?->format('Y-m-d') ?? '',
                    number_format((float) $budget->allocated_amount, 2),
                    number_format($budget->actual_spending, 2),
                    number_format($budget->remaining_amount, 2),
                    $budget->utilization_percentage.'%',
                    ucfirst($budget->status->value),
                    $budget->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'category', 'allocated_amount', 'fiscal_year',
            'start_date', 'end_date', 'notes',
        ]);
        $this->status = 'draft';
        $this->alerts_enabled = true;
        $this->alert_threshold_warning = 75;
        $this->alert_threshold_critical = 90;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.budgets.budget-index');
    }
}
