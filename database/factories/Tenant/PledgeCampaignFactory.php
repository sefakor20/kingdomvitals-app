<?php

namespace Database\Factories\Tenant;

use App\Enums\CampaignCategory;
use App\Enums\CampaignStatus;
use App\Models\Tenant\PledgeCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\PledgeCampaign>
 */
class PledgeCampaignFactory extends Factory
{
    protected $model = PledgeCampaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $campaigns = [
            'Building Fund 2026',
            'Missions Outreach',
            'Youth Ministry Support',
            'Widows & Orphans Fund',
            'Church Anniversary',
            'Equipment Fund',
            'Community Outreach',
            'Education Scholarship',
        ];

        return [
            'name' => fake()->randomElement($campaigns),
            'description' => fake()->optional(0.7)->paragraph(),
            'category' => fake()->randomElement(CampaignCategory::cases()),
            'goal_amount' => fake()->randomFloat(2, 5000, 100000),
            'goal_participants' => fake()->numberBetween(10, 100),
            'currency' => 'GHS',
            'start_date' => fake()->dateTimeBetween('-30 days', '+7 days'),
            'end_date' => fake()->optional(0.8)->dateTimeBetween('+30 days', '+365 days'),
            'status' => CampaignStatus::Active,
        ];
    }

    /**
     * Indicate that the campaign is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Draft,
        ]);
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Active,
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Completed,
            'end_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the campaign is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Cancelled,
        ]);
    }

    /**
     * Set a specific category for the campaign.
     */
    public function withCategory(CampaignCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Set specific goals for the campaign.
     */
    public function withGoals(float $amount, int $participants): static
    {
        return $this->state(fn (array $attributes) => [
            'goal_amount' => $amount,
            'goal_participants' => $participants,
        ]);
    }
}
