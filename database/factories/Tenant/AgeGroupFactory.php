<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\AgeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\AgeGroup>
 */
class AgeGroupFactory extends Factory
{
    protected $model = AgeGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minAge = fake()->numberBetween(0, 15);

        return [
            'name' => fake()->randomElement(['Nursery', 'Toddlers', 'Pre-K', 'Kindergarten', 'Elementary', 'Pre-Teen', 'Teens']),
            'description' => fake()->optional(0.5)->sentence(),
            'min_age' => $minAge,
            'max_age' => $minAge + fake()->numberBetween(1, 3),
            'color' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function nursery(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Nursery',
            'min_age' => 0,
            'max_age' => 1,
            'sort_order' => 0,
        ]);
    }

    public function toddlers(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Toddlers',
            'min_age' => 2,
            'max_age' => 3,
            'sort_order' => 1,
        ]);
    }

    public function preK(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pre-K',
            'min_age' => 4,
            'max_age' => 5,
            'sort_order' => 2,
        ]);
    }

    public function elementary(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Elementary',
            'min_age' => 6,
            'max_age' => 11,
            'sort_order' => 3,
        ]);
    }

    public function teens(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Teens',
            'min_age' => 12,
            'max_age' => 17,
            'sort_order' => 4,
        ]);
    }
}
