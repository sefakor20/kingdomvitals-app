<?php

namespace Database\Factories\Tenant;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vendors = [
            'Ghana Water Company',
            'ECG',
            'MTN Ghana',
            'Vodafone Ghana',
            'Office Mart',
            'Melcom',
            'Church Supplies Ltd',
            'Sound Systems Ghana',
            'Event Rentals GH',
            'Transport Services',
        ];

        return [
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 50, 10000),
            'currency' => 'GHS',
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'vendor_name' => fake()->randomElement($vendors),
            'receipt_url' => fake()->optional(0.3)->url(),
            'reference_number' => fake()->optional(0.4)->numerify('EXP-######'),
            'status' => ExpenseStatus::Pending,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the expense is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the expense is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Approved,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the expense is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Rejected,
        ]);
    }

    /**
     * Indicate that the expense is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Paid,
            'approved_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
        ]);
    }

    /**
     * Indicate that the expense is for utilities.
     */
    public function utilities(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::Utilities,
            'description' => fake()->randomElement([
                'Electricity bill',
                'Water bill',
                'Internet subscription',
                'Phone bill',
            ]),
        ]);
    }

    /**
     * Indicate that the expense is for maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::Maintenance,
            'description' => fake()->randomElement([
                'AC repair',
                'Generator servicing',
                'Building repairs',
                'Plumbing work',
            ]),
        ]);
    }

    /**
     * Indicate that the expense is for supplies.
     */
    public function supplies(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::Supplies,
            'description' => fake()->randomElement([
                'Office supplies',
                'Cleaning supplies',
                'Communion supplies',
                'Stationery',
            ]),
        ]);
    }

    /**
     * Indicate that the expense was made this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_date' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Set a specific expense date.
     */
    public function expensedOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expense_date' => $date,
        ]);
    }
}
