<?php

namespace Database\Factories\Tenant;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Models\Tenant\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(6),
            'body' => fake()->paragraphs(3, true),
            'message_type' => fake()->randomElement(EmailType::cases()),
            'status' => fake()->randomElement(EmailStatus::cases()),
            'provider' => 'smtp',
            'provider_message_id' => fake()->optional(0.7)->uuid(),
            'sent_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => fake()->optional(0.6)->dateTimeBetween('-30 days', 'now'),
            'opened_at' => fake()->optional(0.4)->dateTimeBetween('-30 days', 'now'),
            'clicked_at' => fake()->optional(0.2)->dateTimeBetween('-30 days', 'now'),
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the email was delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Delivered,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the email is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Pending,
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the email was sent but not yet delivered.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Sent,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the email bounced.
     */
    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Bounced,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => null,
            'error_message' => fake()->randomElement([
                'Mailbox not found',
                'Recipient rejected',
                'Invalid email address',
                'Mailbox full',
            ]),
        ]);
    }

    /**
     * Indicate that the email failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Failed,
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'delivered_at' => null,
            'error_message' => fake()->randomElement([
                'SMTP connection failed',
                'Authentication error',
                'Server timeout',
                'Rate limit exceeded',
            ]),
        ]);
    }

    /**
     * Indicate that the email was opened.
     */
    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Delivered,
            'sent_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'delivered_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'opened_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that a link in the email was clicked.
     */
    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmailStatus::Delivered,
            'sent_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'delivered_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'opened_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'clicked_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the email is a birthday message.
     */
    public function birthday(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::Birthday,
            'subject' => 'Happy Birthday!',
            'body' => '<p>Happy Birthday! May God bless you abundantly on this special day.</p>',
        ]);
    }

    /**
     * Indicate that the email is a reminder.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::Reminder,
            'subject' => 'Service Reminder',
            'body' => '<p>Reminder: Service starts at 9:00 AM this Sunday. See you there!</p>',
        ]);
    }

    /**
     * Indicate that the email is a welcome message.
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::Welcome,
            'subject' => 'Welcome to Our Church Family!',
            'body' => "<p>Welcome! We're excited to have you as part of our family. God bless you!</p>",
        ]);
    }

    /**
     * Indicate that the email is an announcement.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::Announcement,
            'subject' => fake()->sentence(5),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
        ]);
    }

    /**
     * Indicate that the email is a newsletter.
     */
    public function newsletter(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::Newsletter,
            'subject' => 'Monthly Newsletter - '.now()->format('F Y'),
            'body' => '<p>'.fake()->paragraphs(5, true).'</p>',
        ]);
    }

    /**
     * Indicate that the email is a follow-up message.
     */
    public function followup(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => EmailType::FollowUp,
            'subject' => 'We Missed You!',
            'body' => '<p>Hi, we missed you at Sunday Service! Hope all is well.</p>',
        ]);
    }

    /**
     * Indicate that the email was sent today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the email was sent this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween(now()->startOfWeek(), now()),
            'sent_at' => fake()->dateTimeBetween(now()->startOfWeek(), now()),
        ]);
    }

    /**
     * Indicate that the email was sent this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('first day of this month', 'now'),
            'sent_at' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }
}
