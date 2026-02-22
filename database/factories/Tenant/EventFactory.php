<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Models\Tenant\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('now', '+3 months');
        $endsAt = fake()->optional(0.7)->dateTimeBetween($startsAt, (clone $startsAt)->modify('+8 hours'));
        $isPaid = fake()->boolean(30);

        return [
            'name' => fake()->words(3, true).' '.fake()->randomElement(['Conference', 'Workshop', 'Retreat', 'Seminar', 'Event']),
            'description' => fake()->optional(0.8)->paragraph(),
            'event_type' => fake()->randomElement(EventType::cases()),
            'category' => fake()->optional(0.5)->randomElement(['Youth', 'Music', 'Outreach', 'Leadership', 'Family']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'location' => fake()->company().' Hall',
            'address' => fake()->optional(0.7)->streetAddress(),
            'city' => fake()->optional(0.7)->city(),
            'country' => 'Ghana',
            'capacity' => fake()->optional(0.6)->numberBetween(50, 500),
            'allow_registration' => true,
            'registration_opens_at' => fake()->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'registration_closes_at' => fake()->optional(0.3)->dateTimeBetween('now', $startsAt),
            'is_paid' => $isPaid,
            'price' => $isPaid ? fake()->randomFloat(2, 10, 200) : null,
            'currency' => 'GHS',
            'requires_ticket' => true,
            'status' => EventStatus::Draft,
            'is_public' => true,
            'visibility' => EventVisibility::Public,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the event is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Published,
        ]);
    }

    /**
     * Indicate that the event is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Ongoing,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHours(4),
        ]);
    }

    /**
     * Indicate that the event is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Completed,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDays(7)->addHours(6),
        ]);
    }

    /**
     * Indicate that the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Cancelled,
        ]);
    }

    /**
     * Indicate that the event is free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_paid' => false,
            'price' => null,
        ]);
    }

    /**
     * Indicate that the event is paid.
     */
    public function paid(float $price = 50.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_paid' => true,
            'price' => $price,
        ]);
    }

    /**
     * Indicate that the event is for members only.
     */
    public function membersOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => EventVisibility::MembersOnly,
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the event is invite only.
     */
    public function inviteOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => EventVisibility::InviteOnly,
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the event is a conference.
     */
    public function conference(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => EventType::Conference,
        ]);
    }

    /**
     * Indicate that the event is a workshop.
     */
    public function workshop(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => EventType::Workshop,
        ]);
    }

    /**
     * Indicate that the event is a retreat.
     */
    public function retreat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => EventType::Retreat,
        ]);
    }

    /**
     * Indicate that the event is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Published,
            'starts_at' => now()->addDays(fake()->numberBetween(7, 30)),
            'ends_at' => now()->addDays(fake()->numberBetween(7, 30))->addHours(6),
        ]);
    }

    /**
     * Indicate that the event is past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Completed,
            'starts_at' => now()->subDays(fake()->numberBetween(7, 60)),
            'ends_at' => now()->subDays(fake()->numberBetween(7, 60))->addHours(6),
        ]);
    }
}
