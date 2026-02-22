<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\CheckInMethod;
use App\Enums\RegistrationStatus;
use App\Models\Tenant\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => RegistrationStatus::Registered,
            'registered_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'ticket_number' => null,
            'is_paid' => false,
            'price_paid' => null,
            'requires_payment' => false,
            'payment_transaction_id' => null,
            'payment_reference' => null,
            'check_in_time' => null,
            'check_out_time' => null,
            'check_in_method' => null,
            'notes' => fake()->optional(0.1)->sentence(),
        ];
    }

    /**
     * Indicate that the registration is for a guest.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'member_id' => null,
            'visitor_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->unique()->safeEmail(),
            'guest_phone' => fake()->phoneNumber(),
        ]);
    }

    /**
     * Indicate that the registrant has attended.
     */
    public function attended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::Attended,
            'check_in_time' => fake()->time('H:i:s'),
            'check_in_method' => fake()->randomElement(CheckInMethod::cases()),
        ]);
    }

    /**
     * Indicate that the registrant was a no-show.
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::NoShow,
        ]);
    }

    /**
     * Indicate that the registration is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the registration is paid.
     */
    public function paid(float $amount = 50.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_paid' => true,
            'price_paid' => $amount,
            'requires_payment' => false,
            'payment_reference' => 'PAY-'.fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the registration requires payment.
     */
    public function requiresPayment(float $amount = 50.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_paid' => false,
            'price_paid' => $amount,
            'requires_payment' => true,
        ]);
    }

    /**
     * Indicate that the registrant has a ticket.
     */
    public function withTicket(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ticket_number' => 'EVT-'.strtoupper(fake()->lexify('???')).'-'.fake()->numerify('####'),
        ]);
    }

    /**
     * Indicate that the registrant has checked in.
     */
    public function checkedIn(?CheckInMethod $method = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'check_in_time' => fake()->time('H:i:s'),
            'check_in_method' => $method ?? fake()->randomElement(CheckInMethod::cases()),
        ]);
    }

    /**
     * Indicate that the registrant has checked out.
     */
    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'check_in_time' => fake()->time('H:i:s'),
            'check_out_time' => fake()->time('H:i:s'),
            'check_in_method' => fake()->randomElement(CheckInMethod::cases()),
        ]);
    }
}
