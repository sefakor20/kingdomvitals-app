<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\EquipmentCondition;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Tenant\EquipmentMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\EquipmentMaintenance>
 */
class EquipmentMaintenanceFactory extends Factory
{
    protected $model = EquipmentMaintenance::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(MaintenanceType::cases()),
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => fake()->dateTimeBetween('now', '+30 days'),
            'description' => fake()->sentence(),
            'service_provider' => fake()->optional(0.5)->company(),
            'cost' => fake()->optional(0.6)->randomFloat(2, 50, 1000),
            'currency' => 'GHS',
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => MaintenanceStatus::Scheduled,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => MaintenanceStatus::InProgress,
            'scheduled_date' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => MaintenanceStatus::Completed,
            'scheduled_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'completed_date' => fake()->dateTimeBetween('-7 days', 'now'),
            'work_performed' => fake()->sentence(),
            'findings' => fake()->optional(0.5)->sentence(),
            'condition_before' => fake()->randomElement([EquipmentCondition::Fair, EquipmentCondition::Poor]),
            'condition_after' => fake()->randomElement([EquipmentCondition::Good, EquipmentCondition::Excellent]),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => MaintenanceStatus::Cancelled,
        ]);
    }

    public function repair(): static
    {
        return $this->state(fn () => [
            'type' => MaintenanceType::Repair,
        ]);
    }

    public function inspection(): static
    {
        return $this->state(fn () => [
            'type' => MaintenanceType::Inspection,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn () => [
            'type' => MaintenanceType::Emergency,
        ]);
    }

    public function withCost(?float $amount = null): static
    {
        return $this->state(fn () => [
            'cost' => $amount ?? fake()->randomFloat(2, 100, 5000),
        ]);
    }
}
