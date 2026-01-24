<?php

namespace Database\Factories\Tenant;

use App\Enums\DutyRosterRoleType;
use App\Models\Tenant\DutyRosterPool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\DutyRosterPool>
 */
class DutyRosterPoolFactory extends Factory
{
    protected $model = DutyRosterPool::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleType = fake()->randomElement(DutyRosterRoleType::cases());

        return [
            'role_type' => $roleType,
            'name' => $roleType->label().' Pool',
            'description' => fake()->optional(0.5)->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the pool is for preachers.
     */
    public function preacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => DutyRosterRoleType::Preacher,
            'name' => 'Preacher Pool',
        ]);
    }

    /**
     * Indicate that the pool is for liturgists.
     */
    public function liturgist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => DutyRosterRoleType::Liturgist,
            'name' => 'Liturgist Pool',
        ]);
    }

    /**
     * Indicate that the pool is for readers.
     */
    public function reader(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => DutyRosterRoleType::Reader,
            'name' => 'Reader Pool',
        ]);
    }

    /**
     * Indicate that the pool is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a custom name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
