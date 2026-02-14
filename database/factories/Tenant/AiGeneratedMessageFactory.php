<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\AiMessageStatus;
use App\Enums\FollowUpType;
use App\Models\Tenant\AiGeneratedMessage;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGeneratedMessage>
 */
class AiGeneratedMessageFactory extends Factory
{
    protected $model = AiGeneratedMessage::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'visitor_id' => null,
            'member_id' => null,
            'message_type' => $this->faker->randomElement(['follow_up', 'reengagement', 'welcome']),
            'channel' => $this->faker->randomElement(FollowUpType::cases()),
            'generated_content' => $this->faker->paragraph(),
            'context_used' => [
                'visitor_name' => $this->faker->name(),
                'visit_date' => $this->faker->date(),
            ],
            'status' => AiMessageStatus::Pending,
            'ai_provider' => 'anthropic',
            'ai_model' => 'claude-3-5-sonnet-20241022',
            'tokens_used' => $this->faker->numberBetween(50, 200),
            'approved_by' => null,
            'approved_at' => null,
            'sent_by' => null,
            'sent_at' => null,
        ];
    }

    public function forVisitor(?Visitor $visitor = null): static
    {
        return $this->state(fn (array $attributes) => [
            'visitor_id' => $visitor?->id ?? Visitor::factory(),
            'member_id' => null,
        ]);
    }

    public function forMember(?Member $member = null): static
    {
        return $this->state(fn (array $attributes) => [
            'visitor_id' => null,
            'member_id' => $member?->id ?? Member::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiMessageStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'sent_by' => null,
            'sent_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiMessageStatus::Approved,
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'sent_by' => null,
            'sent_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiMessageStatus::Sent,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHour(),
            'sent_by' => User::factory(),
            'sent_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AiMessageStatus::Rejected,
            'approved_by' => null,
            'approved_at' => null,
            'sent_by' => null,
            'sent_at' => null,
        ]);
    }
}
