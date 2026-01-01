<?php

namespace Database\Factories\Tenant;

use App\Enums\VisitorStatus;
use App\Models\Tenant\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $howDidYouHear = [
            'Friend or family',
            'Social media',
            'Church website',
            'Google search',
            'Passed by the church',
            'Flyer or brochure',
            'Community event',
            'Other',
        ];

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional(0.7)->safeEmail(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'visit_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => VisitorStatus::New,
            'how_did_you_hear' => fake()->optional(0.6)->randomElement($howDidYouHear),
            'notes' => fake()->optional(0.2)->sentence(),
            'is_converted' => false,
        ];
    }

    /**
     * Indicate that the visitor is new.
     */
    public function newVisitor(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VisitorStatus::New,
        ]);
    }

    /**
     * Indicate that the visitor has been followed up.
     */
    public function followedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VisitorStatus::FollowedUp,
        ]);
    }

    /**
     * Indicate that the visitor is returning.
     */
    public function returning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VisitorStatus::Returning,
        ]);
    }

    /**
     * Indicate that the visitor has been converted to a member.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VisitorStatus::Converted,
            'is_converted' => true,
        ]);
    }

    /**
     * Indicate that the visitor is not interested.
     */
    public function notInterested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VisitorStatus::NotInterested,
        ]);
    }

    /**
     * Set a specific visit date.
     */
    public function visitedOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_date' => $date,
        ]);
    }
}
