<?php

namespace Database\Factories\Tenant;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\PledgeFrequency;
use App\Enums\RecurringExpenseStatus;
use App\Models\Tenant\RecurringExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    protected $model = RecurringExpense::class;

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
            'Church Supplies Ltd',
            'Pastor\'s Salary',
            'Security Services',
            'Cleaning Services',
        ];

        $frequency = fake()->randomElement([
            PledgeFrequency::Weekly,
            PledgeFrequency::Monthly,
            PledgeFrequency::Quarterly,
            PledgeFrequency::Yearly,
        ]);

        $startDate = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'GHS',
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'vendor_name' => fake()->randomElement($vendors),
            'notes' => fake()->optional(0.3)->sentence(),
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => fake()->optional(0.3)->dateTimeBetween('+6 months', '+2 years'),
            'day_of_month' => $frequency === PledgeFrequency::Weekly ? null : fake()->numberBetween(1, 28),
            'day_of_week' => $frequency === PledgeFrequency::Weekly ? fake()->numberBetween(0, 6) : null,
            'next_generation_date' => $startDate,
            'last_generated_date' => null,
            'total_generated_count' => 0,
            'status' => RecurringExpenseStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringExpenseStatus::Active,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringExpenseStatus::Paused,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringExpenseStatus::Completed,
            'end_date' => fake()->dateTimeBetween('-30 days', 'yesterday'),
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Weekly,
            'day_of_week' => fake()->numberBetween(0, 6),
            'day_of_month' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Monthly,
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Quarterly,
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Yearly,
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringExpenseStatus::Active,
            'next_generation_date' => now()->toDateString(),
            'start_date' => now()->subMonth()->toDateString(),
        ]);
    }

    public function duePast(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringExpenseStatus::Active,
            'next_generation_date' => now()->subDays(3)->toDateString(),
            'start_date' => now()->subMonths(2)->toDateString(),
        ]);
    }

    public function utilities(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::Utilities,
            'description' => fake()->randomElement([
                'Monthly Electricity Bill',
                'Monthly Water Bill',
                'Internet Subscription',
                'Phone Bill',
            ]),
        ]);
    }

    public function salaries(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::Salaries,
            'description' => fake()->randomElement([
                'Pastor Salary',
                'Choir Director Salary',
                'Security Guard Salary',
                'Cleaner Salary',
            ]),
            'frequency' => PledgeFrequency::Monthly,
            'day_of_month' => 28,
        ]);
    }

    public function withGeneratedCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'total_generated_count' => $count,
            'last_generated_date' => now()->subMonth()->toDateString(),
        ]);
    }
}
