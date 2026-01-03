<?php

namespace Database\Factories\Tenant;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\SmsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\SmsLog>
 */
class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => fake()->e164PhoneNumber(),
            'message' => fake()->sentence(10),
            'message_type' => fake()->randomElement(SmsType::cases()),
            'status' => fake()->randomElement(SmsStatus::cases()),
            'provider' => 'texttango',
            'provider_message_id' => fake()->optional(0.7)->uuid(),
            'cost' => fake()->randomFloat(4, 0.01, 0.50),
            'currency' => 'GHS',
            'sent_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => fake()->optional(0.6)->dateTimeBetween('-30 days', 'now'),
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the SMS was delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsStatus::Delivered,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the SMS is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsStatus::Pending,
            'sent_at' => null,
            'delivered_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the SMS was sent but not yet delivered.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsStatus::Sent,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the SMS failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsStatus::Failed,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => null,
            'error_message' => fake()->randomElement([
                'Invalid phone number',
                'Insufficient balance',
                'Network error',
                'Provider timeout',
            ]),
        ]);
    }

    /**
     * Indicate that the SMS is a birthday message.
     */
    public function birthday(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => SmsType::Birthday,
            'message' => 'Happy Birthday! May God bless you abundantly on this special day.',
        ]);
    }

    /**
     * Indicate that the SMS is a reminder.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => SmsType::Reminder,
            'message' => 'Reminder: Service starts at 9:00 AM this Sunday. See you there!',
        ]);
    }

    /**
     * Indicate that the SMS is a welcome message.
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => SmsType::Welcome,
            'message' => "Welcome! We're excited to have you as part of our family. God bless you!",
        ]);
    }

    /**
     * Indicate that the SMS is an announcement.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => SmsType::Announcement,
            'message' => fake()->sentence(15),
        ]);
    }

    /**
     * Indicate that the SMS was sent today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the SMS was sent this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween(now()->startOfWeek(), now()),
            'sent_at' => fake()->dateTimeBetween(now()->startOfWeek(), now()),
        ]);
    }

    /**
     * Indicate that the SMS was sent this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('first day of this month', 'now'),
            'sent_at' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }
}
