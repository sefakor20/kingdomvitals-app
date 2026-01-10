<?php

namespace Database\Factories\Tenant;

use App\Enums\EmploymentStatus;
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
        $maritalStatus = fake()->randomElement(MaritalStatus::cases());

        return [
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional(0.3)->firstName(),
            'maiden_name' => ($gender === Gender::Female && $maritalStatus === MaritalStatus::Married)
                ? fake()->optional(0.7)->lastName()
                : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'gender' => $gender,
            'marital_status' => $maritalStatus,
            'profession' => fake()->optional(0.7)->jobTitle(),
            'employment_status' => fake()->optional(0.7)->randomElement(EmploymentStatus::cases()),
            'status' => MembershipStatus::Active,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip' => fake()->postcode(),
            'country' => 'Ghana',
            'hometown' => fake()->optional(0.6)->city(),
            'gps_address' => fake()->optional(0.4)->regexify('[A-Z]{2}-[0-9]{3}-[0-9]{4}'),
            'joined_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'baptized_at' => fake()->optional(0.7)->dateTimeBetween('-5 years', 'now'),
            'confirmation_date' => fake()->optional(0.5)->dateTimeBetween('-5 years', 'now'),
            'notes' => fake()->optional(0.2)->sentence(),
            'previous_congregation' => fake()->optional(0.3)->company().' Church',
            'photo_url' => null,
            'sms_opt_out' => false,
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

    /**
     * Indicate that the member has opted out of SMS.
     */
    public function optedOutOfSms(): static
    {
        return $this->state(fn (array $attributes) => [
            'sms_opt_out' => true,
        ]);
    }
}
