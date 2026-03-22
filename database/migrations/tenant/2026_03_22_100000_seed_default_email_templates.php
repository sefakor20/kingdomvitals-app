<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $branches = DB::table('branches')->get();

        $templates = [
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

        $now = now();

        foreach ($branches as $branch) {
            foreach ($templates as $template) {
                // Check if template of this type already exists for this branch
                $exists = DB::table('email_templates')
                    ->where('branch_id', $branch->id)
                    ->where('type', $template['type'])
                    ->exists();

                if (! $exists) {
                    DB::table('email_templates')->insert([
                        'id' => Str::uuid()->toString(),
                        'branch_id' => $branch->id,
                        'name' => $template['name'],
                        'subject' => $template['subject'],
                        'body' => $template['body'],
                        'type' => $template['type'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only delete the default templates (by name matching)
        $defaultNames = [
            'Birthday Greeting',
            'Welcome Message',
            'Service Reminder',
            'Attendance Follow-up',
            'General Announcement',
            'Monthly Newsletter',
            'Event Reminder',
        ];

        DB::table('email_templates')
            ->whereIn('name', $defaultNames)
            ->delete();
    }
};
