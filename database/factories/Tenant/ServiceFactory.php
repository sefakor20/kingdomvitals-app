<?php

namespace Database\Factories\Tenant;

use App\Enums\ServiceType;
use App\Models\Tenant\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Service',
            'day_of_week' => fake()->numberBetween(0, 6),
            'time' => fake()->time('H:i'),
            'service_type' => fake()->randomElement(ServiceType::cases()),
            'capacity' => fake()->optional(0.5)->numberBetween(50, 500),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the service is a Sunday service.
     */
    public function sunday(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Sunday,
        ]);
    }

    /**
     * Indicate that the service is a midweek service.
     */
    public function midweek(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Midweek,
        ]);
    }

    /**
     * Indicate that the service is a prayer service.
     */
    public function prayer(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Prayer,
        ]);
    }

    /**
     * Indicate that the service is a youth service.
     */
    public function youth(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Youth,
        ]);
    }

    /**
     * Indicate that the service is a children service.
     */
    public function children(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Children,
        ]);
    }

    /**
     * Indicate that the service is a special service.
     */
    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => ServiceType::Special,
        ]);
    }
}
