<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastName = fake()->lastName();

        return [
            'name' => "The {$lastName} Family",
            'address' => fake()->optional(0.7)->streetAddress(),
        ];
    }
}
