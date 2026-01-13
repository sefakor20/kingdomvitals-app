<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\ChildEmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\ChildEmergencyContact>
 */
class ChildEmergencyContactFactory extends Factory
{
    protected $model = ChildEmergencyContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Mother', 'Father', 'Grandmother', 'Grandfather', 'Aunt', 'Uncle', 'Guardian']),
            'phone' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional(0.3)->phoneNumber(),
            'email' => fake()->optional(0.5)->safeEmail(),
            'is_primary' => false,
            'can_pickup' => true,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function cannotPickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_pickup' => false,
        ]);
    }

    public function mother(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name('female'),
            'relationship' => 'Mother',
        ]);
    }

    public function father(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name('male'),
            'relationship' => 'Father',
        ]);
    }
}
