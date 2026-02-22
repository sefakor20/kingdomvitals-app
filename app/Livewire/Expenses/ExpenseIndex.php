<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Enums\BranchRole;
use App\Enums\BudgetStatus;
use App\Enums\Currency;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Expense;
use App\Models\User;
use App\Notifications\BudgetThresholdNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class ExpenseIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showApproveModal = false;

    public bool $showRejectModal = false;

    // Form properties
    public string $category = '';

    public string $description = '';

    public string $amount = '';

    public ?string $expense_date = null;

    public string $payment_method = 'cash';

    public string $vendor_name = '';

    public string $receipt_url = '';

    public string $reference_number = '';

    public string $notes = '';

    public ?Expense $editingExpense = null;

    public ?Expense $deletingExpense = null;

    public ?Expense $approvingExpense = null;

    public ?Expense $rejectingExpense = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Expense::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function expenses(): LengthAwarePaginator
    {
        $query = Expense::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['description', 'vendor_name', 'reference_number', 'notes']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyDateRange($query, 'expense_date');

        return $query->with(['submitter', 'approver'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories(): array
    {
        return ExpenseCategory::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return ExpenseStatus::cases();
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Expense::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Expense::class, $this->branch]);
    }

    #[Computed]
    public function canApprove(): bool
    {
        // Check if user has Admin or Manager role in this branch
        return auth()->user()->branchAccess()
            ->where('branch_id', $this->branch->id)
            ->whereIn('role', [
                \App\Enums\BranchRole::Admin,
                \App\Enums\BranchRole::Manager,
            ])
            ->exists();
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function expenseStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = Expense::where('branch_id', $this->branch->id);

        $this->applySearch($baseQuery, ['description', 'vendor_name', 'reference_number', 'notes']);
        $this->applyEnumFilter($baseQuery, 'categoryFilter', 'category');
        $this->applyEnumFilter($baseQuery, 'statusFilter', 'status');
        $this->applyDateRange($baseQuery, 'expense_date');

        $total = (clone $baseQuery)->sum('amount');
        $count = (clone $baseQuery)->count();
        $pending = (clone $baseQuery)->where('status', ExpenseStatus::Pending)->count();
        $thisMonth = (clone $baseQuery)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Top category by amount - need to get this from a grouped query
        $topCategoryResult = (clone $baseQuery)
            ->selectRaw('category, SUM(amount) as total_amount')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->first();
        $topCategory = $topCategoryResult?->category?->value ?? null;

        return [
            'total' => $total,
            'count' => $count,
            'pending' => $pending,
            'thisMonth' => $thisMonth,
            'topCategory' => $topCategory ? str_replace('_', ' ', ucfirst($topCategory)) : '-',
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
        if ($this->isFilterActive($this->dateFrom)) {
            return true;
        }

        return $this->isFilterActive($this->dateTo);
    }

    protected function rules(): array
    {
        $categories = collect(ExpenseCategory::cases())->pluck('value')->implode(',');
        $paymentMethods = collect(PaymentMethod::cases())->pluck('value')->implode(',');

        return [
            'category' => ['required', 'string', 'in:'.$categories],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:'.$paymentMethods],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'receipt_url' => ['nullable', 'url', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Expense::class, $this->branch]);
        $this->resetForm();
        $this->expense_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Expense::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = tenant()->getCurrencyCode();
        $validated['status'] = ExpenseStatus::Pending;

        // Convert empty strings to null for nullable fields
        $nullableFields = ['vendor_name', 'receipt_url', 'reference_number', 'notes'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        Expense::create($validated);

        unset($this->expenses);
        unset($this->expenseStats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('expense-created');
    }

    public function edit(Expense $expense): void
    {
        $this->authorize('update', $expense);
        $this->editingExpense = $expense;
        $this->fill([
            'category' => $expense->category->value,
            'description' => $expense->description,
            'amount' => (string) $expense->amount,
            'expense_date' => $expense->expense_date?->format('Y-m-d'),
            'payment_method' => $expense->payment_method->value,
            'vendor_name' => $expense->vendor_name ?? '',
            'receipt_url' => $expense->receipt_url ?? '',
            'reference_number' => $expense->reference_number ?? '',
            'notes' => $expense->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingExpense);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['vendor_name', 'receipt_url', 'reference_number', 'notes'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingExpense->update($validated);

        unset($this->expenses);
        unset($this->expenseStats);

        $this->showEditModal = false;
        $this->editingExpense = null;
        $this->resetForm();
        $this->dispatch('expense-updated');
    }

    public function confirmDelete(Expense $expense): void
    {
        $this->authorize('delete', $expense);
        $this->deletingExpense = $expense;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingExpense);

        $this->deletingExpense->delete();

        unset($this->expenses);
        unset($this->expenseStats);

        $this->showDeleteModal = false;
        $this->deletingExpense = null;
        $this->dispatch('expense-deleted');
    }

    // Approval workflow methods
    public function confirmApprove(Expense $expense): void
    {
        $this->authorize('approve', $expense);
        $this->approvingExpense = $expense;
        $this->showApproveModal = true;
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->approvingExpense);

        $expense = $this->approvingExpense;

        $expense->update([
            'status' => ExpenseStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->checkBudgetThreshold($expense);

        unset($this->expenses);
        unset($this->expenseStats);

        $this->showApproveModal = false;
        $this->approvingExpense = null;
        $this->dispatch('expense-approved');
    }

    public function confirmReject(Expense $expense): void
    {
        $this->authorize('reject', $expense);
        $this->rejectingExpense = $expense;
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->rejectingExpense);

        $this->rejectingExpense->update([
            'status' => ExpenseStatus::Rejected,
        ]);

        unset($this->expenses);
        unset($this->expenseStats);

        $this->showRejectModal = false;
        $this->rejectingExpense = null;
        $this->dispatch('expense-rejected');
    }

    public function markAsPaid(Expense $expense): void
    {
        $this->authorize('markAsPaid', $expense);

        if ($expense->status !== ExpenseStatus::Approved) {
            return;
        }

        $expense->update([
            'status' => ExpenseStatus::Paid,
        ]);

        $this->checkBudgetThreshold($expense);

        unset($this->expenses);
        unset($this->expenseStats);

        $this->dispatch('expense-paid');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingExpense = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingExpense = null;
    }

    public function cancelApprove(): void
    {
        $this->showApproveModal = false;
        $this->approvingExpense = null;
    }

    public function cancelReject(): void
    {
        $this->showRejectModal = false;
        $this->rejectingExpense = null;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'categoryFilter', 'statusFilter',
            'dateFrom', 'dateTo',
        ]);
        $this->resetPage();
        unset($this->expenses);
        unset($this->expenseStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Expense::class, $this->branch]);

        // Build query with same filters but get all records (not paginated)
        $query = Expense::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['description', 'vendor_name', 'reference_number', 'notes']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyDateRange($query, 'expense_date');

        $expenses = $query->with(['submitter', 'approver'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = sprintf(
            'expenses_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($expenses): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Description',
                'Category',
                'Amount',
                'Currency',
                'Payment Method',
                'Vendor',
                'Reference',
                'Status',
                'Approved At',
                'Notes',
            ]);

            // Data rows
            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->expense_date?->format('Y-m-d') ?? '',
                    $expense->description,
                    str_replace('_', ' ', ucfirst($expense->category->value)),
                    number_format((float) $expense->amount, 2),
                    $expense->currency,
                    str_replace('_', ' ', ucfirst($expense->payment_method->value)),
                    $expense->vendor_name ?? '',
                    $expense->reference_number ?? '',
                    ucfirst($expense->status->value),
                    $expense->approved_at?->format('Y-m-d H:i') ?? '',
                    $expense->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function checkBudgetThreshold(Expense $expense): void
    {
        $budget = Budget::where('branch_id', $expense->branch_id)
            ->where('category', $expense->category)
            ->where('status', BudgetStatus::Active)
            ->where('alerts_enabled', true)
            ->where('start_date', '<=', $expense->expense_date)
            ->where('end_date', '>=', $expense->expense_date)
            ->first();

        if (! $budget) {
            return;
        }

        $utilization = $budget->utilization_percentage;

        // Check which alert level we've reached
        $alertLevel = null;
        $lastSentField = null;
        $cooldown = null;

        if ($utilization >= 100) {
            $alertLevel = 'exceeded';
            $lastSentField = 'last_exceeded_sent_at';
            $cooldown = now()->subDay();
        } elseif ($utilization >= $budget->alert_threshold_critical) {
            $alertLevel = 'critical';
            $lastSentField = 'last_critical_sent_at';
            $cooldown = now()->subDay();
        } elseif ($utilization >= $budget->alert_threshold_warning) {
            $alertLevel = 'warning';
            $lastSentField = 'last_warning_sent_at';
            $cooldown = now()->subWeek();
        }

        if (! $alertLevel) {
            return;
        }

        // Check cooldown period
        if ($budget->$lastSentField !== null && $cooldown <= $budget->$lastSentField) {
            return;
        }

        // Send alerts to admins and managers
        $recipients = User::whereHas('branchAccess', function ($q) use ($budget): void {
            $q->where('branch_id', $budget->branch_id)
                ->whereIn('role', [
                    BranchRole::Admin->value,
                    BranchRole::Manager->value,
                ]);
        })->get();

        foreach ($recipients as $user) {
            $user->notify(new BudgetThresholdNotification($budget, $alertLevel, $utilization));
        }

        $budget->update([$lastSentField => now()]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'category', 'description', 'amount', 'expense_date',
            'vendor_name', 'receipt_url', 'reference_number', 'notes',
        ]);
        $this->payment_method = 'cash';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.expenses.expense-index');
    }
}
