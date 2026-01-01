<?php

namespace Database\Factories\Tenant;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional(0.3)->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'gender' => $gender,
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'status' => MembershipStatus::Active,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip' => fake()->postcode(),
            'country' => 'Ghana',
            'joined_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'baptized_at' => fake()->optional(0.7)->dateTimeBetween('-5 years', 'now'),
            'notes' => fake()->optional(0.2)->sentence(),
            'photo_url' => null,
        ];
    }

    /**
     * Indicate that the member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the member is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Pending,
        ]);
    }

    /**
     * Indicate that the member is deceased.
     */
    public function deceased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Deceased,
        ]);
    }

    /**
     * Indicate that the member is transferred.
     */
    public function transferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Transferred,
        ]);
    }

    /**
     * Indicate that the member is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Gender::Male,
            'first_name' => fake()->firstName('male'),
        ]);
    }

    /**
     * Indicate that the member is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Gender::Female,
            'first_name' => fake()->firstName('female'),
        ]);
    }
}
