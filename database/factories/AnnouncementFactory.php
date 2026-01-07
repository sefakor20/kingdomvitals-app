<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetAudience;
use App\Models\Announcement;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'super_admin_id' => SuperAdmin::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'target_audience' => AnnouncementTargetAudience::All,
            'specific_tenant_ids' => null,
            'priority' => AnnouncementPriority::Normal,
            'status' => AnnouncementStatus::Draft,
            'scheduled_at' => null,
            'sent_at' => null,
            'total_recipients' => 0,
            'successful_count' => 0,
            'failed_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Draft,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Scheduled,
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Sending,
            'total_recipients' => 10,
            'successful_count' => 5,
            'failed_count' => 0,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Sent,
            'sent_at' => now(),
            'total_recipients' => 10,
            'successful_count' => 10,
            'failed_count' => 0,
        ]);
    }

    public function partiallyFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::PartiallyFailed,
            'sent_at' => now(),
            'total_recipients' => 10,
            'successful_count' => 8,
            'failed_count' => 2,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => AnnouncementPriority::Urgent,
        ]);
    }

    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => AnnouncementPriority::Important,
        ]);
    }

    public function forActiveOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => AnnouncementTargetAudience::Active,
        ]);
    }

    public function forTrialOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => AnnouncementTargetAudience::Trial,
        ]);
    }

    public function forSpecificTenants(array $tenantIds): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => AnnouncementTargetAudience::Specific,
            'specific_tenant_ids' => $tenantIds,
        ]);
    }
}
