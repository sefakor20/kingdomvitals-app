<?php

namespace Database\Factories\Tenant;

use App\Enums\EmailType;
use App\Models\Tenant\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'subject' => fake()->sentence(6),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'type' => fake()->randomElement(EmailType::cases()),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the template is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the template is for birthdays.
     */
    public function birthday(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Birthday Greeting',
            'type' => EmailType::Birthday,
            'subject' => 'Happy Birthday, {first_name}!',
            'body' => '<p>Dear {first_name},</p><p>Happy Birthday! May God bless you abundantly on this special day. We celebrate you!</p><p>With love,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is for reminders.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Service Reminder',
            'type' => EmailType::Reminder,
            'subject' => 'Service Reminder - This Sunday',
            'body' => '<p>Dear {first_name},</p><p>This is a reminder that our service starts at {time} this Sunday at {location}. We look forward to seeing you there!</p><p>Blessings,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is for welcome messages.
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Welcome Message',
            'type' => EmailType::Welcome,
            'subject' => 'Welcome to {branch_name}!',
            'body' => "<p>Dear {first_name},</p><p>Welcome to our church family! We're so excited to have you join us.</p><p>If you have any questions, please don't hesitate to reach out.</p><p>God bless,<br>{branch_name}</p>",
        ]);
    }

    /**
     * Indicate that the template is for announcements.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'General Announcement',
            'type' => EmailType::Announcement,
            'subject' => 'Important Announcement from {branch_name}',
            'body' => '<p>Dear {first_name},</p><p>'.fake()->paragraph(2).'</p><p>Blessings,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is for newsletters.
     */
    public function newsletter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly Newsletter',
            'type' => EmailType::Newsletter,
            'subject' => '{branch_name} Newsletter - {month} {year}',
            'body' => '<p>Dear {first_name},</p><p>Here is our monthly newsletter with all the latest updates and upcoming events.</p><p>'.fake()->paragraphs(3, true).'</p><p>Blessings,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is for follow-ups.
     */
    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Follow Up Message',
            'type' => EmailType::FollowUp,
            'subject' => 'We Missed You!',
            'body' => '<p>Dear {first_name},</p><p>We noticed you were unable to join us this week. We missed you and hope to see you soon!</p><p>Is there anything we can do for you? Please let us know.</p><p>Blessings,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is for event reminders.
     */
    public function eventReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Event Reminder',
            'type' => EmailType::EventReminder,
            'subject' => 'Reminder: {event_name} is Coming Up!',
            'body' => '<p>Dear {first_name},</p><p>This is a reminder about our upcoming event: <strong>{event_name}</strong></p><p>Date: {event_date}<br>Time: {event_time}<br>Location: {event_location}</p><p>We look forward to seeing you there!</p><p>Blessings,<br>{branch_name}</p>',
        ]);
    }

    /**
     * Indicate that the template is a custom type.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EmailType::Custom,
        ]);
    }
}
