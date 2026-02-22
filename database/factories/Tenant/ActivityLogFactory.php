<?php

namespace Database\Factories\Tenant;

use App\Enums\ActivityEvent;
use App\Enums\SubjectType;
use App\Models\Tenant\ActivityLog;
use App\Models\Tenant\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'subject_type' => fake()->randomElement(SubjectType::cases()),
            'subject_id' => fake()->uuid(),
            'subject_name' => fake()->name(),
            'event' => fake()->randomElement([
                ActivityEvent::Created,
                ActivityEvent::Updated,
                ActivityEvent::Deleted,
            ]),
            'description' => null,
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'metadata' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the activity is a creation event.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Created,
            'new_values' => [
                'name' => fake()->name(),
                'email' => fake()->email(),
            ],
        ]);
    }

    /**
     * Indicate that the activity is an update event.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Updated,
            'old_values' => ['name' => fake()->name()],
            'new_values' => ['name' => fake()->name()],
            'changed_fields' => ['name'],
        ]);
    }

    /**
     * Indicate that the activity is a deletion event.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Deleted,
        ]);
    }

    /**
     * Indicate that the activity is a restoration event.
     */
    public function restored(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Restored,
        ]);
    }

    /**
     * Indicate that the activity is a login event.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Login,
            'subject_type' => SubjectType::User,
        ]);
    }

    /**
     * Indicate that the activity is a logout event.
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Logout,
            'subject_type' => SubjectType::User,
        ]);
    }

    /**
     * Indicate that the activity is an export event.
     */
    public function exported(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event' => ActivityEvent::Exported,
            'metadata' => [
                'record_count' => fake()->numberBetween(10, 100),
                'format' => 'csv',
            ],
        ]);
    }

    /**
     * Set a specific subject type.
     */
    public function forSubjectType(SubjectType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_type' => $type,
        ]);
    }

    /**
     * Set a specific branch.
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $branch->id,
        ]);
    }
}
