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
        ];

        $now = now();

        foreach ($branches as $branch) {
            foreach ($templates as $template) {
                // Check if template of this type already exists for this branch
                $exists = DB::table('sms_templates')
                    ->where('branch_id', $branch->id)
                    ->where('type', $template['type'])
                    ->exists();

                if (! $exists) {
                    DB::table('sms_templates')->insert([
                        'id' => Str::uuid()->toString(),
                        'branch_id' => $branch->id,
                        'name' => $template['name'],
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
        ];

        DB::table('sms_templates')
            ->whereIn('name', $defaultNames)
            ->delete();
    }
};
