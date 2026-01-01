<?php

namespace Database\Factories\Tenant;

use App\Enums\PledgeFrequency;
use App\Enums\PledgeStatus;
use App\Models\Tenant\Pledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Pledge>
 */
class PledgeFactory extends Factory
{
    protected $model = Pledge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $campaigns = [
            'Building Fund 2025',
            'Missions Outreach',
            'Youth Ministry Support',
            'Widows & Orphans Fund',
            'Church Anniversary',
            'Equipment Fund',
            'Community Outreach',
            'Education Scholarship',
        ];

        $amount = fake()->randomFloat(2, 100, 10000);

        return [
            'campaign_name' => fake()->randomElement($campaigns),
            'amount' => $amount,
            'currency' => 'GHS',
            'frequency' => fake()->randomElement(PledgeFrequency::cases()),
            'start_date' => fake()->dateTimeBetween('-60 days', 'now'),
            'end_date' => fake()->optional(0.6)->dateTimeBetween('+30 days', '+365 days'),
            'amount_fulfilled' => fake()->randomFloat(2, 0, $amount * 0.8),
            'status' => PledgeStatus::Active,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the pledge is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 1000;

            return [
                'status' => PledgeStatus::Active,
                'amount_fulfilled' => fake()->randomFloat(2, 0, $amount * 0.8),
            ];
        });
    }

    /**
     * Indicate that the pledge is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 1000;

            return [
                'status' => PledgeStatus::Completed,
                'amount_fulfilled' => $amount,
            ];
        });
    }

    /**
     * Indicate that the pledge is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PledgeStatus::Cancelled,
        ]);
    }

    /**
     * Indicate that the pledge is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PledgeStatus::Paused,
        ]);
    }

    /**
     * Indicate that the pledge is one-time.
     */
    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::OneTime,
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the pledge is monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Monthly,
        ]);
    }

    /**
     * Indicate that the pledge is weekly.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => PledgeFrequency::Weekly,
        ]);
    }

    /**
     * Set the pledge with no fulfillment yet.
     */
    public function unfulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_fulfilled' => 0,
        ]);
    }

    /**
     * Set a specific campaign name.
     */
    public function forCampaign(string $campaign): static
    {
        return $this->state(fn (array $attributes) => [
            'campaign_name' => $campaign,
        ]);
    }
}
