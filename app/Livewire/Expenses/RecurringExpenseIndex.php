<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Enums\Currency;
use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\PledgeFrequency;
use App\Enums\RecurringExpenseStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\RecurringExpense;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RecurringExpenseIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showGenerateModal = false;

    // Form properties
    public string $category = '';

    public string $description = '';

    public string $amount = '';

    public string $payment_method = 'cash';

    public string $vendor_name = '';

    public string $notes = '';

    public string $frequency = 'monthly';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?int $day_of_month = null;

    public ?int $day_of_week = null;

    public ?RecurringExpense $editingRecurringExpense = null;

    public ?RecurringExpense $deletingRecurringExpense = null;

    public ?RecurringExpense $generatingRecurringExpense = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [RecurringExpense::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function recurringExpenses(): Collection
    {
        $query = RecurringExpense::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['description', 'vendor_name', 'notes']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'statusFilter', 'status');

        return $query->with('creator')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'paused' THEN 1 ELSE 2 END")
            ->orderBy('next_generation_date')
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
        return RecurringExpenseStatus::cases();
    }

    #[Computed]
    public function frequencies(): array
    {
        // Exclude OneTime since it doesn't make sense for recurring expenses
        return [
            PledgeFrequency::Weekly,
            PledgeFrequency::Monthly,
            PledgeFrequency::Quarterly,
            PledgeFrequency::Yearly,
        ];
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [RecurringExpense::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [RecurringExpense::class, $this->branch]);
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function stats(): array
    {
        $expenses = $this->recurringExpenses;

        $active = $expenses->where('status', RecurringExpenseStatus::Active);
        $paused = $expenses->where('status', RecurringExpenseStatus::Paused);

        $monthlyProjection = $active->sum(fn ($r) => $r->monthly_projection);

        return [
            'total' => $expenses->count(),
            'active' => $active->count(),
            'paused' => $paused->count(),
            'monthly_projection' => $monthlyProjection,
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
        return $this->isFilterActive($this->statusFilter);
    }

    protected function rules(): array
    {
        $categories = collect(ExpenseCategory::cases())->pluck('value')->implode(',');
        $paymentMethods = collect(PaymentMethod::cases())->pluck('value')->implode(',');
        $frequencies = collect($this->frequencies())->pluck('value')->implode(',');

        return [
            'category' => ['required', 'string', 'in:'.$categories],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:'.$paymentMethods],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'frequency' => ['required', 'string', 'in:'.$frequencies],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [RecurringExpense::class, $this->branch]);
        $this->resetForm();
        $this->start_date = now()->format('Y-m-d');
        $this->day_of_month = (int) now()->format('d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [RecurringExpense::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = tenant()->getCurrencyCode();
        $validated['status'] = RecurringExpenseStatus::Active->value;

        // Convert empty strings to null for nullable fields
        $nullableFields = ['vendor_name', 'notes', 'end_date'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Set next_generation_date based on start_date
        $validated['next_generation_date'] = $validated['start_date'];

        // Clear day fields based on frequency
        if ($validated['frequency'] === PledgeFrequency::Weekly->value) {
            $validated['day_of_month'] = null;
        } else {
            $validated['day_of_week'] = null;
        }

        RecurringExpense::create($validated);

        unset($this->recurringExpenses);
        unset($this->stats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('recurring-expense-created');
    }

    public function edit(RecurringExpense $recurringExpense): void
    {
        $this->authorize('update', $recurringExpense);
        $this->editingRecurringExpense = $recurringExpense;
        $this->fill([
            'category' => $recurringExpense->category->value,
            'description' => $recurringExpense->description,
            'amount' => (string) $recurringExpense->amount,
            'payment_method' => $recurringExpense->payment_method->value,
            'vendor_name' => $recurringExpense->vendor_name ?? '',
            'notes' => $recurringExpense->notes ?? '',
            'frequency' => $recurringExpense->frequency->value,
            'start_date' => $recurringExpense->start_date?->format('Y-m-d'),
            'end_date' => $recurringExpense->end_date?->format('Y-m-d') ?? '',
            'day_of_month' => $recurringExpense->day_of_month,
            'day_of_week' => $recurringExpense->day_of_week,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingRecurringExpense);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['vendor_name', 'notes', 'end_date'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Clear day fields based on frequency
        if ($validated['frequency'] === PledgeFrequency::Weekly->value) {
            $validated['day_of_month'] = null;
        } else {
            $validated['day_of_week'] = null;
        }

        $this->editingRecurringExpense->update($validated);

        unset($this->recurringExpenses);
        unset($this->stats);

        $this->showEditModal = false;
        $this->editingRecurringExpense = null;
        $this->resetForm();
        $this->dispatch('recurring-expense-updated');
    }

    public function confirmDelete(RecurringExpense $recurringExpense): void
    {
        $this->authorize('delete', $recurringExpense);
        $this->deletingRecurringExpense = $recurringExpense;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingRecurringExpense);

        $this->deletingRecurringExpense->delete();

        unset($this->recurringExpenses);
        unset($this->stats);

        $this->showDeleteModal = false;
        $this->deletingRecurringExpense = null;
        $this->dispatch('recurring-expense-deleted');
    }

    public function toggleStatus(RecurringExpense $recurringExpense): void
    {
        $this->authorize('toggleStatus', $recurringExpense);

        $newStatus = $recurringExpense->isActive()
            ? RecurringExpenseStatus::Paused
            : RecurringExpenseStatus::Active;

        $recurringExpense->update(['status' => $newStatus]);

        unset($this->recurringExpenses);
        unset($this->stats);

        $this->dispatch('recurring-expense-status-changed');
    }

    public function confirmGenerate(RecurringExpense $recurringExpense): void
    {
        $this->authorize('generateNow', $recurringExpense);
        $this->generatingRecurringExpense = $recurringExpense;
        $this->showGenerateModal = true;
    }

    public function generateNow(): void
    {
        $this->authorize('generateNow', $this->generatingRecurringExpense);

        // Temporarily set next_generation_date to today to allow generation
        $originalDate = $this->generatingRecurringExpense->next_generation_date;
        $this->generatingRecurringExpense->update(['next_generation_date' => now()->toDateString()]);

        $expense = $this->generatingRecurringExpense->generateExpense();

        if (! $expense instanceof \App\Models\Tenant\Expense) {
            // Restore original date if generation failed
            $this->generatingRecurringExpense->update(['next_generation_date' => $originalDate]);
            $this->dispatch('recurring-expense-generation-failed');
        } else {
            $this->dispatch('recurring-expense-generated');
        }

        unset($this->recurringExpenses);
        unset($this->stats);

        $this->showGenerateModal = false;
        $this->generatingRecurringExpense = null;
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingRecurringExpense = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingRecurringExpense = null;
    }

    public function cancelGenerate(): void
    {
        $this->showGenerateModal = false;
        $this->generatingRecurringExpense = null;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter']);
        unset($this->recurringExpenses);
        unset($this->stats);
        unset($this->hasActiveFilters);
    }

    private function resetForm(): void
    {
        $this->reset([
            'category', 'description', 'amount', 'vendor_name', 'notes',
            'start_date', 'end_date', 'day_of_month', 'day_of_week',
        ]);
        $this->payment_method = 'cash';
        $this->frequency = 'monthly';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.expenses.recurring-expense-index');
    }
}
