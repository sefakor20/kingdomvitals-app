<?php

namespace App\Models\Tenant;

use App\Enums\BudgetStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'allocated_amount',
        'fiscal_year',
        'start_date',
        'end_date',
        'currency',
        'status',
        'notes',
        'created_by',
        'alerts_enabled',
        'alert_threshold_warning',
        'alert_threshold_critical',
        'last_warning_sent_at',
        'last_critical_sent_at',
        'last_exceeded_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'fiscal_year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'category' => ExpenseCategory::class,
            'status' => BudgetStatus::class,
            'alerts_enabled' => 'boolean',
            'alert_threshold_warning' => 'integer',
            'alert_threshold_critical' => 'integer',
            'last_warning_sent_at' => 'datetime',
            'last_critical_sent_at' => 'datetime',
            'last_exceeded_sent_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    /**
     * Scope to eager-load actual spending via subquery (prevents N+1).
     *
     * Usage: Budget::withActualSpending()->where(...)->get()
     */
    public function scopeWithActualSpending(Builder $query): Builder
    {
        $approvedStatuses = [ExpenseStatus::Approved->value, ExpenseStatus::Paid->value];

        return $query->addSelect([
            'calculated_actual_spending' => Expense::selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('expenses.branch_id', 'budgets.branch_id')
                ->whereColumn('expenses.category', 'budgets.category')
                ->whereColumn('expenses.currency', 'budgets.currency')
                ->whereRaw('expenses.expense_date >= budgets.start_date')
                ->whereRaw('expenses.expense_date <= budgets.end_date')
                ->whereIn('expenses.status', $approvedStatuses),
        ]);
    }

    public function getActualSpendingAttribute(): float
    {
        // Use pre-loaded value if available (from withActualSpending scope)
        if (array_key_exists('calculated_actual_spending', $this->attributes)) {
            return (float) ($this->attributes['calculated_actual_spending'] ?? 0);
        }

        // Fallback to query for single budget access
        return (float) Expense::where('branch_id', $this->branch_id)
            ->where('category', $this->category)
            ->where('currency', $this->currency)
            ->whereDate('expense_date', '>=', $this->start_date)
            ->whereDate('expense_date', '<=', $this->end_date)
            ->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Paid])
            ->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->allocated_amount - $this->actual_spending;
    }

    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->allocated_amount == 0) {
            return 0;
        }

        return round(($this->actual_spending / $this->allocated_amount) * 100, 1);
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->actual_spending > $this->allocated_amount;
    }
}
