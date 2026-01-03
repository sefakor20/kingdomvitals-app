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
}
