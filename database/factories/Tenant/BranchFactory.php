<?php

namespace Database\Factories\Tenant;

use App\Enums\BranchStatus;
use App\Models\Tenant\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->city().' Campus';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_main' => false,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip' => fake()->postcode(),
            'country' => 'Ghana',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'capacity' => fake()->numberBetween(100, 1000),
            'timezone' => 'Africa/Accra',
            'status' => BranchStatus::Active,
            'logo_url' => null,
            'color_primary' => fake()->hexColor(),
            'settings' => [],
        ];
    }

    /**
     * Indicate that the branch is the main branch.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'is_main' => true,
        ]);
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the branch is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::Pending,
        ]);
    }

    /**
     * Indicate that the branch is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::Suspended,
        ]);
    }
}
