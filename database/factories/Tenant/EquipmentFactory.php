<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Models\Tenant\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        $category = fake()->randomElement(EquipmentCategory::cases());

        return [
            'name' => $this->getEquipmentName($category),
            'category' => $category,
            'description' => fake()->optional(0.7)->sentence(),
            'serial_number' => fake()->optional(0.8)->bothify('??##??##??'),
            'model_number' => fake()->optional(0.6)->bothify('???-####'),
            'manufacturer' => fake()->optional(0.7)->company(),
            'purchase_date' => fake()->optional(0.8)->dateTimeBetween('-5 years', 'now'),
            'purchase_price' => fake()->optional(0.7)->randomFloat(2, 100, 10000),
            'source_of_equipment' => fake()->optional(0.7)->randomElement(['Purchased', 'Donated', 'Church fund', 'Member donation', 'Leased']),
            'currency' => 'GHS',
            'condition' => fake()->randomElement(EquipmentCondition::cases()),
            'location' => fake()->optional(0.6)->randomElement(['Main Hall', 'Office', 'Storage Room', 'Sanctuary', 'Youth Room']),
            'warranty_expiry' => fake()->optional(0.5)->dateTimeBetween('now', '+3 years'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    private function getEquipmentName(EquipmentCategory $category): string
    {
        return match ($category) {
            EquipmentCategory::Audio => fake()->randomElement(['Microphone', 'Speaker', 'Mixer', 'Amplifier', 'Headphones']),
            EquipmentCategory::Video => fake()->randomElement(['Projector', 'Camera', 'Screen', 'Monitor', 'Tripod']),
            EquipmentCategory::Musical => fake()->randomElement(['Keyboard', 'Guitar', 'Drums', 'Bass', 'Violin']),
            EquipmentCategory::Furniture => fake()->randomElement(['Chair', 'Table', 'Podium', 'Desk', 'Shelf']),
            EquipmentCategory::Computer => fake()->randomElement(['Laptop', 'Desktop', 'Printer', 'Tablet', 'Scanner']),
            EquipmentCategory::Lighting => fake()->randomElement(['Stage Light', 'Spotlight', 'LED Panel', 'Floodlight']),
            EquipmentCategory::Other => fake()->word(),
        };
    }

    public function audio(): static
    {
        return $this->state(fn () => [
            'category' => EquipmentCategory::Audio,
            'name' => fake()->randomElement(['Microphone', 'Speaker', 'Mixer', 'Amplifier']),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'category' => EquipmentCategory::Video,
            'name' => fake()->randomElement(['Projector', 'Camera', 'Screen', 'Monitor']),
        ]);
    }

    public function musical(): static
    {
        return $this->state(fn () => [
            'category' => EquipmentCategory::Musical,
            'name' => fake()->randomElement(['Keyboard', 'Guitar', 'Drums', 'Bass']),
        ]);
    }

    public function outOfService(): static
    {
        return $this->state(fn () => [
            'condition' => EquipmentCondition::OutOfService,
        ]);
    }

    public function excellent(): static
    {
        return $this->state(fn () => [
            'condition' => EquipmentCondition::Excellent,
        ]);
    }

    public function good(): static
    {
        return $this->state(fn () => [
            'condition' => EquipmentCondition::Good,
        ]);
    }

    public function withMaintenanceDue(): static
    {
        return $this->state(fn () => [
            'next_maintenance_date' => fake()->dateTimeBetween('-1 week', '+1 week'),
        ]);
    }

    public function withWarranty(): static
    {
        return $this->state(fn () => [
            'warranty_expiry' => fake()->dateTimeBetween('+6 months', '+3 years'),
        ]);
    }

    public function expiredWarranty(): static
    {
        return $this->state(fn () => [
            'warranty_expiry' => fake()->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }
}
