<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestPrivacy;
use App\Enums\PrayerRequestStatus;
use App\Models\Tenant\PrayerRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\PrayerRequest>
 */
class PrayerRequestFactory extends Factory
{
    protected $model = PrayerRequest::class;

    public function definition(): array
    {
        $category = fake()->randomElement(PrayerRequestCategory::cases());

        return [
            'title' => $this->getPrayerTitle($category),
            'description' => fake()->paragraph(3),
            'category' => $category,
            'status' => PrayerRequestStatus::Open,
            'privacy' => fake()->randomElement(PrayerRequestPrivacy::cases()),
            'submitted_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    private function getPrayerTitle(PrayerRequestCategory $category): string
    {
        return match ($category) {
            PrayerRequestCategory::Personal => fake()->randomElement([
                'Personal growth and strength',
                'Guidance for decisions',
                'Peace of mind',
            ]),
            PrayerRequestCategory::Family => fake()->randomElement([
                'Family unity and healing',
                'Protection for my family',
                'Wisdom in parenting',
            ]),
            PrayerRequestCategory::Health => fake()->randomElement([
                'Healing from illness',
                'Recovery from surgery',
                'Strength during treatment',
            ]),
            PrayerRequestCategory::Finances => fake()->randomElement([
                'Financial breakthrough',
                'Provision for needs',
                'Wisdom in finances',
            ]),
            PrayerRequestCategory::Work => fake()->randomElement([
                'Job opportunity',
                'Workplace challenges',
                'Career direction',
            ]),
            PrayerRequestCategory::Spiritual => fake()->randomElement([
                'Spiritual growth',
                'Deeper relationship with God',
                'Overcoming temptation',
            ]),
            PrayerRequestCategory::Relationships => fake()->randomElement([
                'Restoration of relationships',
                'Finding a life partner',
                'Reconciliation',
            ]),
            PrayerRequestCategory::Grief => fake()->randomElement([
                'Comfort in loss',
                'Peace during mourning',
                'Healing from grief',
            ]),
            PrayerRequestCategory::Guidance => fake()->randomElement([
                'Direction in life',
                'Wisdom for decisions',
                'Clarity and purpose',
            ]),
            PrayerRequestCategory::Thanksgiving => fake()->randomElement([
                'Gratitude for answered prayers',
                'Thanksgiving for blessings',
                'Praise for Gods faithfulness',
            ]),
            PrayerRequestCategory::Other => fake()->sentence(4),
        };
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => PrayerRequestStatus::Open,
        ]);
    }

    public function answered(): static
    {
        return $this->state(fn () => [
            'status' => PrayerRequestStatus::Answered,
            'answered_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'answer_details' => fake()->paragraph(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => PrayerRequestStatus::Cancelled,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn () => [
            'privacy' => PrayerRequestPrivacy::Public,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn () => [
            'privacy' => PrayerRequestPrivacy::Private,
        ]);
    }

    public function leadersOnly(): static
    {
        return $this->state(fn () => [
            'privacy' => PrayerRequestPrivacy::LeadersOnly,
        ]);
    }

    public function health(): static
    {
        return $this->state(fn () => [
            'category' => PrayerRequestCategory::Health,
            'title' => fake()->randomElement([
                'Healing from illness',
                'Recovery from surgery',
                'Strength during treatment',
            ]),
        ]);
    }

    public function family(): static
    {
        return $this->state(fn () => [
            'category' => PrayerRequestCategory::Family,
            'title' => fake()->randomElement([
                'Family unity and healing',
                'Protection for my family',
                'Wisdom in parenting',
            ]),
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'member_id' => null,
        ]);
    }
}
