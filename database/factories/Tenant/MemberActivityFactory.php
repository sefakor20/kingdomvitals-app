<?php

namespace Database\Factories\Tenant;

use App\Enums\ActivityEvent;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\MemberActivity>
 */
class MemberActivityFactory extends Factory
{
    protected $model = MemberActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'user_id' => User::factory(),
            'event' => fake()->randomElement(ActivityEvent::cases()),
            'old_values' => null,
            'new_values' => null,
            'changed_fields' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the activity is a creation event.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => ActivityEvent::Created,
            'new_values' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'status' => 'active',
            ],
        ]);
    }

    /**
     * Indicate that the activity is an update event.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => ActivityEvent::Updated,
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'inactive'],
            'changed_fields' => ['status'],
        ]);
    }

    /**
     * Indicate that the activity is a deletion event.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => ActivityEvent::Deleted,
        ]);
    }

    /**
     * Indicate that the activity is a restoration event.
     */
    public function restored(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => ActivityEvent::Restored,
        ]);
    }
}
