<?php

namespace Database\Factories\Tenant;

use App\Enums\ClusterType;
use App\Models\Tenant\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Cluster>
 */
class ClusterFactory extends Factory
{
    protected $model = Cluster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Group',
            'cluster_type' => fake()->randomElement(ClusterType::cases()),
            'description' => fake()->optional(0.5)->sentence(),
            'meeting_day' => fake()->optional(0.7)->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']),
            'meeting_time' => fake()->optional(0.7)->time('H:i'),
            'meeting_location' => fake()->optional(0.5)->address(),
            'capacity' => fake()->optional(0.3)->numberBetween(10, 50),
            'is_active' => true,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the cluster is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the cluster is a cell group.
     */
    public function cellGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'cluster_type' => ClusterType::CellGroup,
        ]);
    }

    /**
     * Indicate that the cluster is a house fellowship.
     */
    public function houseFellowship(): static
    {
        return $this->state(fn (array $attributes) => [
            'cluster_type' => ClusterType::HouseFellowship,
        ]);
    }

    /**
     * Indicate that the cluster is a zone.
     */
    public function zone(): static
    {
        return $this->state(fn (array $attributes) => [
            'cluster_type' => ClusterType::Zone,
        ]);
    }

    /**
     * Indicate that the cluster is a district.
     */
    public function district(): static
    {
        return $this->state(fn (array $attributes) => [
            'cluster_type' => ClusterType::District,
        ]);
    }
}
