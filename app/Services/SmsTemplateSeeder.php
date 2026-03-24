<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;

class SmsTemplateSeeder
{
    /**
     * Default SMS templates to seed for each branch.
     *
     * @var array<int, array{type: string, name: string, body: string}>
     */
    protected array $defaultTemplates = [
        [
            'type' => 'birthday',
            'name' => 'Birthday Greeting',
            'body' => "Happy Birthday, {first_name}! Wishing you God's richest blessings on your special day. May this new year of your life be filled with joy, peace, and His abundant grace.",
        ],
        [
            'type' => 'welcome',
            'name' => 'Welcome Message',
            'body' => "Welcome to {branch_name}, {first_name}! We're so glad you've joined our family. If you have any questions, please don't hesitate to reach out. God bless you!",
        ],
        [
            'type' => 'reminder',
            'name' => 'Service Reminder',
            'body' => 'Hi {first_name}, this is a friendly reminder that {service_name} is coming up on {service_day} at {service_time}. We look forward to seeing you!',
        ],
        [
            'type' => 'follow_up',
            'name' => 'Attendance Follow-up',
            'body' => 'Hi {first_name}, we missed you at {service_name} on {service_day}. We hope everything is well with you. Looking forward to seeing you soon!',
        ],
        [
            'type' => 'announcement',
            'name' => 'General Announcement',
            'body' => 'Dear {first_name}, we have an important announcement from {branch_name}. Please check our notice board or contact us for more details.',
        ],
        [
            'type' => 'duty_roster_reminder',
            'name' => 'Duty Roster Reminder',
            'body' => 'Hi {first_name}, reminder: You are assigned as {role} for the service on {service_date} at {branch_name}. Please prepare accordingly.',
        ],
    ];

    /**
     * Seed default SMS templates for a branch.
     *
     * @return array{created: int, skipped: int}
     */
    public function seedForBranch(Branch $branch): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($this->defaultTemplates as $template) {
            $exists = SmsTemplate::where('branch_id', $branch->id)
                ->where('type', $template['type'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            SmsTemplate::create([
                'branch_id' => $branch->id,
                'name' => $template['name'],
                'body' => $template['body'],
                'type' => $template['type'],
                'is_active' => true,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Get the default templates array.
     *
     * @return array<int, array{type: string, name: string, body: string}>
     */
    public function getDefaultTemplates(): array
    {
        return $this->defaultTemplates;
    }
}
