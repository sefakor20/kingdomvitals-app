<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailTemplate;
use Illuminate\Console\Command;

class SeedEmailTemplatesCommand extends Command
{
    protected $signature = 'email:seed-templates
                            {--tenant=* : Specific tenant ID(s) to process}';

    protected $description = 'Seed default email templates for tenant branches';

    /**
     * @var array<int, array{type: string, name: string, subject: string, body: string}>
     */
    protected array $defaultTemplates = [
        [
            'type' => 'birthday',
            'name' => 'Birthday Greeting',
            'subject' => 'Happy Birthday, {first_name}!',
            'body' => "Dear {first_name},\n\nHappy Birthday! Wishing you God's richest blessings on your special day. May this new year of your life be filled with joy, peace, and His abundant grace.\n\nWith love and prayers,\n{branch_name}",
        ],
        [
            'type' => 'welcome',
            'name' => 'Welcome Message',
            'subject' => 'Welcome to {branch_name}!',
            'body' => "Dear {first_name},\n\nWelcome to {branch_name}! We're so glad you've joined our family.\n\nWe believe that your presence among us is a blessing, and we look forward to growing together in faith and fellowship. If you have any questions or need anything at all, please don't hesitate to reach out.\n\nGod bless you!\n\nWarmly,\n{branch_name}",
        ],
        [
            'type' => 'reminder',
            'name' => 'Service Reminder',
            'subject' => 'Reminder: {service_name}',
            'body' => "Hi {first_name},\n\nThis is a friendly reminder that **{service_name}** is coming up on {service_day} at {service_time}.\n\nWe look forward to seeing you there!\n\nBlessings,\n{branch_name}",
        ],
        [
            'type' => 'follow_up',
            'name' => 'Attendance Follow-up',
            'subject' => 'We Missed You!',
            'body' => "Dear {first_name},\n\nWe missed you at {service_name} on {service_day}. We hope everything is well with you and your family.\n\nPlease know that you are always welcome, and we're here if you need anything. Looking forward to seeing you soon!\n\nWith care,\n{branch_name}",
        ],
        [
            'type' => 'announcement',
            'name' => 'General Announcement',
            'subject' => 'Important Announcement from {branch_name}',
            'body' => "Dear {first_name},\n\nWe have an important announcement to share with you from {branch_name}.\n\n[Your announcement content here]\n\nIf you have any questions, please don't hesitate to contact us.\n\nBlessings,\n{branch_name}",
        ],
        [
            'type' => 'newsletter',
            'name' => 'Monthly Newsletter',
            'subject' => '{branch_name} Newsletter',
            'body' => "## Welcome to our Newsletter!\n\nDear {first_name},\n\nThank you for being part of our community. Here's what's been happening and what's coming up:\n\n### Highlights\n\n[Add your highlights here]\n\n### Upcoming Events\n\n[Add upcoming events here]\n\n### Prayer Requests\n\n[Add prayer requests here]\n\nGod bless you,\n{branch_name}",
        ],
        [
            'type' => 'event_reminder',
            'name' => 'Event Reminder',
            'subject' => 'Reminder: {event_name}',
            'body' => "Hi {first_name},\n\nThis is a friendly reminder about the upcoming event:\n\n**{event_name}**\nDate: {event_date}\nTime: {event_time}\nLocation: {event_location}\n\nWe look forward to seeing you there!\n\nBlessings,\n{branch_name}",
        ],
    ];

    public function handle(): int
    {
        $tenantIds = $this->option('tenant');

        $tenants = empty($tenantIds)
            ? Tenant::all()
            : Tenant::whereIn('id', $tenantIds)->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $this->info('Seeding default email templates...');

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name}");

            tenancy()->initialize($tenant);

            $branches = Branch::all();

            foreach ($branches as $branch) {
                $result = $this->seedBranchTemplates($branch);
                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
            }

            tenancy()->end();
        }

        $this->newLine();
        $this->info("Done! Created {$totalCreated} templates, skipped {$totalSkipped} (already exist).");

        return Command::SUCCESS;
    }

    /**
     * @return array{created: int, skipped: int}
     */
    protected function seedBranchTemplates(Branch $branch): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($this->defaultTemplates as $template) {
            $exists = EmailTemplate::where('branch_id', $branch->id)
                ->where('type', $template['type'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            EmailTemplate::create([
                'branch_id' => $branch->id,
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'type' => $template['type'],
                'is_active' => true,
            ]);

            $created++;
        }

        if ($created > 0) {
            $this->line("  - Branch '{$branch->name}': created {$created} templates");
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
