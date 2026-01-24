<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\MemberUnavailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\MemberUnavailability>
 */
class MemberUnavailabilityFactory extends Factory
{
    protected $model = MemberUnavailability::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unavailable_date' => fake()->dateTimeBetween('now', '+3 months'),
            'reason' => fake()->optional(0.7)->randomElement([
                'Traveling',
                'Family commitment',
                'Work',
                'Sick',
                'On leave',
                'Personal reasons',
            ]),
        ];
    }

    /**
     * Set a specific date.
     */
    public function onDate(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'unavailable_date' => $date,
        ]);
    }

    /**
     * Set a specific reason.
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => $reason,
        ]);
    }
}
