<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\ChildMedicalInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\ChildMedicalInfo>
 */
class ChildMedicalInfoFactory extends Factory
{
    protected $model = ChildMedicalInfo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'allergies' => fake()->optional(0.3)->randomElement(['Peanuts', 'Dairy', 'Eggs', 'Tree nuts', 'Shellfish', 'Wheat']),
            'medical_conditions' => fake()->optional(0.2)->randomElement(['Asthma', 'Diabetes', 'Epilepsy', 'ADHD']),
            'medications' => fake()->optional(0.15)->sentence(3),
            'special_needs' => fake()->optional(0.1)->sentence(),
            'dietary_restrictions' => fake()->optional(0.2)->randomElement(['Vegetarian', 'Vegan', 'Gluten-free', 'Lactose-free']),
            'blood_type' => fake()->optional(0.4)->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'doctor_name' => fake()->optional(0.3)->name(),
            'doctor_phone' => fake()->optional(0.3)->phoneNumber(),
            'insurance_info' => fake()->optional(0.2)->sentence(4),
            'emergency_instructions' => fake()->optional(0.2)->paragraph(1),
        ];
    }

    public function withAllergies(): static
    {
        return $this->state(fn (array $attributes) => [
            'allergies' => fake()->randomElement(['Peanuts', 'Dairy', 'Eggs', 'Tree nuts', 'Shellfish']),
        ]);
    }

    public function withMedicalConditions(): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_conditions' => fake()->randomElement(['Asthma', 'Diabetes', 'Epilepsy']),
            'medications' => 'Daily medication required',
            'emergency_instructions' => 'Please administer medication as prescribed. Contact parent immediately.',
        ]);
    }

    public function withSpecialNeeds(): static
    {
        return $this->state(fn (array $attributes) => [
            'special_needs' => 'Requires one-on-one supervision',
        ]);
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'allergies' => 'Peanuts, Tree nuts',
            'medical_conditions' => 'Asthma',
            'medications' => 'Albuterol inhaler as needed',
            'special_needs' => null,
            'dietary_restrictions' => 'No nuts',
            'blood_type' => 'A+',
            'doctor_name' => fake()->name(),
            'doctor_phone' => fake()->phoneNumber(),
            'insurance_info' => 'BlueCross BlueShield - Policy #123456',
            'emergency_instructions' => 'If wheezing occurs, administer inhaler. Call parent immediately. If severe, call 911.',
        ]);
    }
}
