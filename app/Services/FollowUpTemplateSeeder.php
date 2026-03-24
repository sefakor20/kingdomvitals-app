<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FollowUpType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FollowUpTemplate;

class FollowUpTemplateSeeder
{
    /**
     * Default follow-up templates to seed for each branch.
     *
     * @var array<int, array{type: ?FollowUpType, name: string, body: string}>
     */
    protected array $defaultTemplates = [
        [
            'type' => null,
            'name' => 'First Contact',
            'body' => "Hi {first_name}, thank you for visiting {branch_name}! We're glad to have you and look forward to seeing you again. Please don't hesitate to reach out if you have any questions.",
        ],
        [
            'type' => FollowUpType::Call,
            'name' => 'Phone Call Script',
            'body' => "Hello {first_name}, this is [Your Name] from {branch_name}. I'm calling to follow up on your visit with us. We wanted to check in and see how you're doing. Is there anything we can help you with or any questions you might have?",
        ],
        [
            'type' => FollowUpType::Sms,
            'name' => 'SMS Follow-up',
            'body' => "Hi {first_name}, thanks for visiting {branch_name}! We'd love to see you again. Questions? Reply here.",
        ],
        [
            'type' => FollowUpType::Email,
            'name' => 'Email Follow-up',
            'body' => "Dear {first_name},\n\nThank you for visiting {branch_name}. We hope you felt welcomed and blessed during your time with us.\n\nWe would love to see you again and help you connect with our community. If you have any questions or need anything at all, please don't hesitate to reach out.\n\nBlessings,\n{branch_name}",
        ],
        [
            'type' => FollowUpType::Visit,
            'name' => 'Home Visit Notes',
            'body' => "Visited {first_name} at their home.\n\nTopics discussed:\n- \n\nPrayer requests:\n- \n\nFollow-up actions:\n- ",
        ],
        [
            'type' => FollowUpType::WhatsApp,
            'name' => 'WhatsApp Message',
            'body' => 'Hi {first_name}! This is {branch_name}. Thank you for visiting us. How can we serve you better? Feel free to message us anytime.',
        ],
    ];

    /**
     * Seed default follow-up templates for a branch.
     *
     * @return array{created: int, skipped: int}
     */
    public function seedForBranch(Branch $branch): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($this->defaultTemplates as $template) {
            $exists = FollowUpTemplate::where('branch_id', $branch->id)
                ->where('type', $template['type'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            FollowUpTemplate::create([
                'branch_id' => $branch->id,
                'name' => $template['name'],
                'body' => $template['body'],
                'type' => $template['type'],
                'is_active' => true,
                'sort_order' => $created,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Get the default templates array.
     *
     * @return array<int, array{type: ?FollowUpType, name: string, body: string}>
     */
    public function getDefaultTemplates(): array
    {
        return $this->defaultTemplates;
    }
}
