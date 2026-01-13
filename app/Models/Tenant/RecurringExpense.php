<?php

namespace App\Models\Tenant;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PledgeFrequency;
use App\Enums\RecurringExpenseStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringExpense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'category',
        'description',
        'amount',
        'currency',
        'payment_method',
        'vendor_name',
        'notes',
        'frequency',
        'start_date',
        'end_date',
        'day_of_month',
        'day_of_week',
        'next_generation_date',
        'last_generated_date',
        'total_generated_count',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_generation_date' => 'date',
            'last_generated_date' => 'date',
            'total_generated_count' => 'integer',
            'day_of_month' => 'integer',
            'day_of_week' => 'integer',
            'category' => ExpenseCategory::class,
            'payment_method' => PaymentMethod::class,
            'frequency' => PledgeFrequency::class,
            'status' => RecurringExpenseStatus::class,
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

    public function generatedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function isActive(): bool
    {
        return $this->status === RecurringExpenseStatus::Active;
    }

    public function isPaused(): bool
    {
        return $this->status === RecurringExpenseStatus::Paused;
    }

    public function isCompleted(): bool
    {
        return $this->status === RecurringExpenseStatus::Completed;
    }

    public function hasEnded(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function shouldGenerateToday(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->hasEnded()) {
            return false;
        }

        if ($this->next_generation_date === null) {
            return false;
        }
        if ($this->next_generation_date->isToday()) {
            return true;
        }
        return (bool) $this->next_generation_date->isPast();
    }

    public function calculateNextGenerationDate(?Carbon $fromDate = null): ?Carbon
    {
        $from = $fromDate ?? ($this->last_generated_date ?? $this->start_date);

        if ($this->end_date !== null && $from->gte($this->end_date)) {
            return null;
        }

        $nextDate = match ($this->frequency) {
            PledgeFrequency::Weekly => $this->calculateNextWeeklyDate($from),
            PledgeFrequency::Monthly => $this->calculateNextMonthlyDate($from),
            PledgeFrequency::Quarterly => $this->calculateNextQuarterlyDate($from),
            PledgeFrequency::Yearly => $this->calculateNextYearlyDate($from),
            default => null,
        };

        if ($nextDate instanceof \Carbon\Carbon && $this->end_date !== null && $nextDate->gt($this->end_date)) {
            return null;
        }

        return $nextDate;
    }

    private function calculateNextWeeklyDate(Carbon $from): Carbon
    {
        if ($this->day_of_week !== null) {
            return $from->copy()->next($this->day_of_week);
        }
        return $from->copy()->addWeek();
    }

    private function calculateNextMonthlyDate(Carbon $from): Carbon
    {
        $nextDate = $from->copy()->addMonth();

        if ($this->day_of_month !== null) {
            $day = min($this->day_of_month, $nextDate->daysInMonth);
            $nextDate = $nextDate->day($day);
        }

        return $nextDate;
    }

    private function calculateNextQuarterlyDate(Carbon $from): Carbon
    {
        $nextDate = $from->copy()->addMonths(3);

        if ($this->day_of_month !== null) {
            $day = min($this->day_of_month, $nextDate->daysInMonth);
            $nextDate = $nextDate->day($day);
        }

        return $nextDate;
    }

    private function calculateNextYearlyDate(Carbon $from): Carbon
    {
        $nextDate = $from->copy()->addYear();

        if ($this->day_of_month !== null) {
            $day = min($this->day_of_month, $nextDate->daysInMonth);
            $nextDate = $nextDate->day($day);
        }

        return $nextDate;
    }

    public function generateExpense(): ?Expense
    {
        if (! $this->shouldGenerateToday()) {
            return null;
        }

        $expense = Expense::create([
            'branch_id' => $this->branch_id,
            'recurring_expense_id' => $this->id,
            'category' => $this->category->value,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'expense_date' => now()->toDateString(),
            'payment_method' => $this->payment_method->value,
            'vendor_name' => $this->vendor_name,
            'status' => ExpenseStatus::Pending->value,
            'notes' => $this->notes ? "{$this->notes} (Auto-generated)" : 'Auto-generated from recurring expense',
        ]);

        $nextDate = $this->calculateNextGenerationDate(now());

        $this->update([
            'last_generated_date' => now()->toDateString(),
            'next_generation_date' => $nextDate?->toDateString(),
            'total_generated_count' => $this->total_generated_count + 1,
        ]);

        // Mark as completed if no more dates
        if (!$nextDate instanceof \Carbon\Carbon) {
            $this->update(['status' => RecurringExpenseStatus::Completed]);
        }

        return $expense;
    }

    public function getMonthlyProjectionAttribute(): float
    {
        return match ($this->frequency) {
            PledgeFrequency::Weekly => (float) $this->amount * 4.33,
            PledgeFrequency::Monthly => (float) $this->amount,
            PledgeFrequency::Quarterly => (float) $this->amount / 3,
            PledgeFrequency::Yearly => (float) $this->amount / 12,
            default => 0,
        };
    }
}
