<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\FollowUpType;
use App\Models\Tenant\FollowUpTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUpTemplate>
 */
class FollowUpTemplateFactory extends Factory
{
    protected $model = FollowUpTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'body' => 'Hello {first_name}, '.fake()->sentence(10),
            'type' => fake()->randomElement([null, ...FollowUpType::cases()]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function generic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => null,
        ]);
    }

    public function forCalls(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Phone Call Script',
            'type' => FollowUpType::Call,
            'body' => "Hello {first_name}, this is calling from {branch_name}. We wanted to thank you for visiting us on {visit_date}. How are you doing today?\n\n[Listen and respond]\n\nWe'd love to see you again this Sunday. Is there anything we can pray for you about?",
        ]);
    }

    public function forSms(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'SMS Follow-Up',
            'type' => FollowUpType::Sms,
            'body' => 'Hi {first_name}! Thanks for visiting {branch_name}. We hope you had a great experience. Feel free to reach out if you have any questions!',
        ]);
    }

    public function forEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Email Follow-Up',
            'type' => FollowUpType::Email,
            'body' => "Dear {full_name},\n\nThank you for visiting {branch_name} on {visit_date}. We were delighted to have you with us!\n\nWe hope you felt welcome and would love to see you again. If you have any questions about our community or upcoming events, please don't hesitate to reach out.\n\nBlessings,\n{branch_name}",
        ]);
    }

    public function forVisit(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Home Visit Notes',
            'type' => FollowUpType::Visit,
            'body' => "Home Visit for {full_name}\nAddress: [fill in]\nVisit Date: Today\n\nPurpose: Follow-up from their visit on {visit_date} ({days_since_visit} days ago)\n\nNotes:\n- \n\nPrayer requests:\n- ",
        ]);
    }

    public function forWhatsApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'WhatsApp Message',
            'type' => FollowUpType::WhatsApp,
            'body' => 'Hi {first_name}! This is from {branch_name}. We wanted to reach out and thank you for visiting us. It was great having you! Let us know if you have any questions.',
        ]);
    }

    public function firstContact(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'First Contact',
            'type' => null,
            'body' => "Initial follow-up with {full_name} who visited on {visit_date}.\n\nContact: {phone} / {email}\n\nTalking points:\n- Thank them for visiting\n- Ask about their experience\n- Invite them back\n- Offer to answer any questions",
        ]);
    }
}
