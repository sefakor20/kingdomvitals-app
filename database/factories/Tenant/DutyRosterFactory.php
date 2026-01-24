<?php

namespace Database\Factories\Tenant;

use App\Enums\DutyRosterStatus;
use App\Models\Tenant\DutyRoster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\DutyRoster>
 */
class DutyRosterFactory extends Factory
{
    protected $model = DutyRoster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_date' => fake()->dateTimeBetween('now', '+3 months'),
            'theme' => fake()->optional(0.7)->sentence(4),
            'preacher_name' => fake()->optional(0.5)->name(),
            'liturgist_name' => fake()->optional(0.5)->name(),
            'hymn_numbers' => fake()->optional(0.8)->randomElements(
                range(1, 700),
                fake()->numberBetween(3, 6)
            ),
            'remarks' => fake()->optional(0.4)->sentence(),
            'status' => DutyRosterStatus::Draft,
            'is_published' => false,
        ];
    }

    /**
     * Indicate that the roster is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DutyRosterStatus::Published,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the roster is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DutyRosterStatus::Draft,
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the roster is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DutyRosterStatus::Scheduled,
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the roster is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DutyRosterStatus::Completed,
            'service_date' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the roster is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DutyRosterStatus::Cancelled,
        ]);
    }

    /**
     * Set specific hymn numbers.
     *
     * @param  array<int>  $hymns
     */
    public function withHymns(array $hymns): static
    {
        return $this->state(fn (array $attributes) => [
            'hymn_numbers' => $hymns,
        ]);
    }

    /**
     * Set the theme.
     */
    public function withTheme(string $theme): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => $theme,
        ]);
    }
}
