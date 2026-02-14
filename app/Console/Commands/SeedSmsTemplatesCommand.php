<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Console\Command;

class SeedSmsTemplatesCommand extends Command
{
    protected $signature = 'sms:seed-templates
                            {--tenant=* : Specific tenant ID(s) to process}';

    protected $description = 'Seed default SMS templates for tenant branches';

    /**
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

    public function handle(): int
    {
        $tenantIds = $this->option('tenant');

        $tenants = ! empty($tenantIds)
            ? Tenant::whereIn('id', $tenantIds)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $this->info('Seeding default SMS templates...');

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

        if ($created > 0) {
            $this->line("  - Branch '{$branch->name}': created {$created} templates");
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
