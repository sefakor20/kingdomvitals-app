<?php

namespace Database\Factories\Tenant;

use App\Enums\BudgetStatus;
use App\Enums\ExpenseCategory;
use App\Models\Tenant\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->randomElement([2024, 2025, 2026]);
        $category = fake()->randomElement(ExpenseCategory::cases());

        return [
            'name' => "{$year} {$category->name} Budget",
            'category' => $category,
            'allocated_amount' => fake()->randomFloat(2, 1000, 50000),
            'fiscal_year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'currency' => 'GHS',
            'status' => BudgetStatus::Draft,
            'notes' => fake()->optional(0.3)->sentence(),
            'alerts_enabled' => true,
            'alert_threshold_warning' => 75,
            'alert_threshold_critical' => 90,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BudgetStatus::Draft,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BudgetStatus::Active,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BudgetStatus::Closed,
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'fiscal_year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'name' => "{$year} {$attributes['category']->name} Budget",
        ]);
    }

    public function forCategory(ExpenseCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
            'name' => "{$attributes['fiscal_year']} {$category->name} Budget",
        ]);
    }

    public function thisYear(): static
    {
        $year = (int) date('Y');

        return $this->state(fn (array $attributes) => [
            'fiscal_year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
        ]);
    }

    public function withAlertsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'alerts_enabled' => false,
        ]);
    }

    public function withCustomThresholds(int $warning, int $critical): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_threshold_warning' => $warning,
            'alert_threshold_critical' => $critical,
        ]);
    }

    public function withWarningSentAt(?\DateTimeInterface $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'last_warning_sent_at' => $date ?? now(),
        ]);
    }

    public function withCriticalSentAt(?\DateTimeInterface $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'last_critical_sent_at' => $date ?? now(),
        ]);
    }

    public function withExceededSentAt(?\DateTimeInterface $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'last_exceeded_sent_at' => $date ?? now(),
        ]);
    }
}
