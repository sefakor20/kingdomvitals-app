<?php

namespace Database\Factories\Tenant;

use App\Enums\SmsType;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\SmsTemplate>
 */
class SmsTemplateFactory extends Factory
{
    protected $model = SmsTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'body' => fake()->sentence(15),
            'type' => fake()->randomElement(SmsType::cases()),
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
            'type' => SmsType::Birthday,
            'body' => 'Happy Birthday {name}! May God bless you abundantly on this special day. We celebrate you!',
        ]);
    }

    /**
     * Indicate that the template is for reminders.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Service Reminder',
            'type' => SmsType::Reminder,
            'body' => 'Reminder: Service starts at {time} this Sunday at {location}. See you there!',
        ]);
    }

    /**
     * Indicate that the template is for announcements.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'General Announcement',
            'type' => SmsType::Announcement,
            'body' => fake()->paragraph(2),
        ]);
    }

    /**
     * Indicate that the template is for follow-ups.
     */
    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Follow Up Message',
            'type' => SmsType::FollowUp,
            'body' => 'Hello {name}, we noticed you were unable to join us this week. We missed you and hope to see you soon!',
        ]);
    }

    /**
     * Indicate that the template is a custom type.
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SmsType::Custom,
        ]);
    }
}
