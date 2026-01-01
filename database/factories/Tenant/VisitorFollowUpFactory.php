<?php

namespace Database\Factories\Tenant;

use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\VisitorFollowUp>
 */
class VisitorFollowUpFactory extends Factory
{
    protected $model = VisitorFollowUp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(FollowUpType::cases()),
            'outcome' => FollowUpOutcome::Successful,
            'notes' => fake()->optional(0.7)->sentence(),
            'scheduled_at' => null,
            'completed_at' => now(),
            'is_scheduled' => false,
            'reminder_sent' => false,
        ];
    }

    /**
     * Indicate that the follow-up is scheduled for the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => FollowUpOutcome::Pending,
            'scheduled_at' => fake()->dateTimeBetween('now', '+7 days'),
            'completed_at' => null,
            'is_scheduled' => true,
        ]);
    }

    /**
     * Indicate that the follow-up has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => fake()->randomElement([
                FollowUpOutcome::Successful,
                FollowUpOutcome::NoAnswer,
                FollowUpOutcome::Voicemail,
                FollowUpOutcome::Callback,
            ]),
            'completed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'is_scheduled' => false,
        ]);
    }

    /**
     * Indicate that the follow-up is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => FollowUpOutcome::Pending,
            'scheduled_at' => fake()->dateTimeBetween('-7 days', '-1 day'),
            'completed_at' => null,
            'is_scheduled' => true,
        ]);
    }

    /**
     * Set a specific follow-up type.
     */
    public function ofType(FollowUpType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set a specific outcome.
     */
    public function withOutcome(FollowUpOutcome $outcome): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => $outcome,
        ]);
    }
}
