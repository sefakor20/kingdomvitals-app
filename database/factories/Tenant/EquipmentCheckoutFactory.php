<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\CheckoutStatus;
use App\Enums\EquipmentCondition;
use App\Models\Tenant\EquipmentCheckout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\EquipmentCheckout>
 */
class EquipmentCheckoutFactory extends Factory
{
    protected $model = EquipmentCheckout::class;

    public function definition(): array
    {
        $checkoutDate = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'status' => CheckoutStatus::Approved,
            'checkout_date' => $checkoutDate,
            'expected_return_date' => fake()->dateTimeBetween($checkoutDate, '+14 days'),
            'purpose' => fake()->optional(0.7)->sentence(),
            'checkout_notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => CheckoutStatus::Pending,
            'approved_by' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => CheckoutStatus::Approved,
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => CheckoutStatus::Returned,
            'actual_return_date' => fake()->dateTimeBetween($attrs['checkout_date'] ?? '-30 days', 'now'),
            'return_condition' => fake()->randomElement(EquipmentCondition::cases()),
            'return_notes' => fake()->optional(0.5)->sentence(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => CheckoutStatus::Approved,
            'checkout_date' => fake()->dateTimeBetween('-30 days', '-15 days'),
            'expected_return_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => CheckoutStatus::Cancelled,
        ]);
    }
}
