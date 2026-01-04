<?php

namespace App\Models\Tenant;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'recurring_expense_id',
        'category',
        'description',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'vendor_name',
        'receipt_url',
        'reference_number',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
            'category' => ExpenseCategory::class,
            'status' => ExpenseStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'approved_by');
    }

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }

    public function isFromRecurringExpense(): bool
    {
        return $this->recurring_expense_id !== null;
    }
}
