<?php

namespace Database\Factories\Tenant;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'GHS',
            'donation_type' => fake()->randomElement(DonationType::cases()),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'donation_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'reference_number' => fake()->optional(0.3)->numerify('REF-######'),
            'donor_name' => fake()->optional(0.4)->name(),
            'notes' => fake()->optional(0.2)->sentence(),
            'is_anonymous' => fake()->boolean(10),
        ];
    }

    /**
     * Indicate that the donation is a tithe.
     */
    public function tithe(): static
    {
        return $this->state(fn (array $attributes) => [
            'donation_type' => DonationType::Tithe,
        ]);
    }

    /**
     * Indicate that the donation is an offering.
     */
    public function offering(): static
    {
        return $this->state(fn (array $attributes) => [
            'donation_type' => DonationType::Offering,
        ]);
    }

    /**
     * Indicate that the donation is for building fund.
     */
    public function buildingFund(): static
    {
        return $this->state(fn (array $attributes) => [
            'donation_type' => DonationType::BuildingFund,
        ]);
    }

    /**
     * Indicate that the donation is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
            'member_id' => null,
            'donor_name' => null,
        ]);
    }

    /**
     * Indicate that the donation was made this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'donation_date' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Indicate that the donation was paid with cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::Cash,
        ]);
    }

    /**
     * Indicate that the donation was paid with mobile money.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::MobileMoney,
            'reference_number' => fake()->numerify('MM-##########'),
        ]);
    }

    /**
     * Set a specific donation date.
     */
    public function donatedOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'donation_date' => $date,
        ]);
    }
}
