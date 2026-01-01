<?php

namespace Database\Factories\Tenant;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkInTime = fake()->time('H:i');
        $hasCheckOut = fake()->boolean(70);

        return [
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'check_in_time' => $checkInTime,
            'check_out_time' => $hasCheckOut ? fake()->time('H:i') : null,
            'check_in_method' => fake()->randomElement(CheckInMethod::cases()),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that check-in was manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => CheckInMethod::Manual,
        ]);
    }

    /**
     * Indicate that check-in was via QR code.
     */
    public function qr(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => CheckInMethod::Qr,
        ]);
    }

    /**
     * Indicate that check-in was via kiosk.
     */
    public function kiosk(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => CheckInMethod::Kiosk,
        ]);
    }

    /**
     * Indicate that the person has not checked out.
     */
    public function notCheckedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out_time' => null,
        ]);
    }

    /**
     * Indicate that the person has checked out.
     */
    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out_time' => fake()->time('H:i'),
        ]);
    }

    /**
     * Set a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }
}
