<?php

use App\Enums\AnnouncementStatus;
use App\Jobs\ProcessAnnouncementJob;
use App\Models\Announcement;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send follow-up reminders daily at 8 AM
Schedule::command('visitors:send-follow-up-reminders --hours=24')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Also check every 2 hours for same-day follow-ups
Schedule::command('visitors:send-follow-up-reminders --hours=2')
    ->everyTwoHours()
    ->withoutOverlapping();

// Send birthday SMS greetings daily at 8 AM
Schedule::command('sms:send-birthday')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Send service reminder SMS hourly
Schedule::command('sms:send-service-reminder')
    ->hourly()
    ->withoutOverlapping();

// Send attendance follow-up SMS hourly
Schedule::command('sms:send-attendance-followup')
    ->hourly()
    ->withoutOverlapping();

// Send duty roster reminders daily at 8 AM
Schedule::command('sms:send-duty-roster-reminder')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Check budget thresholds hourly and send alerts
Schedule::command('budgets:check-thresholds')
    ->hourly()
    ->withoutOverlapping();

// Generate recurring expenses daily at 7 AM
Schedule::command('expenses:generate-recurring')
    ->dailyAt('07:00')
    ->withoutOverlapping();

// Process scheduled announcements every minute
Schedule::call(function (): void {
    Announcement::query()
        ->where('status', AnnouncementStatus::Scheduled)
        ->where('scheduled_at', '<=', now())
        ->each(fn (Announcement $announcement) => ProcessAnnouncementJob::dispatch($announcement->id));
})->everyMinute()->name('process-scheduled-announcements');

// Aggregate usage analytics hourly
Schedule::command('analytics:aggregate-usage')
    ->hourly()
    ->withoutOverlapping();

// Generate monthly invoices on the 1st of each month at 1 AM
Schedule::command('billing:generate-invoices')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping();

// Check for overdue invoices daily at 6 AM
Schedule::command('billing:check-overdue')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Send payment reminders daily at 9 AM
Schedule::command('billing:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Reconcile payments weekly on Sunday at midnight
Schedule::command('billing:reconcile-payments')
    ->weeklyOn(0, '00:00')
    ->withoutOverlapping();

// AI: Recalculate conversion and churn scores weekly on Monday at 3 AM
Schedule::command('ai:recalculate-scores')
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping();

// AI: Detect attendance anomalies daily at 4 AM
Schedule::command('ai:detect-anomalies')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// AI: Generate attendance forecasts weekly on Sunday at 11 PM
Schedule::command('ai:forecast-attendance --weeks=4')
    ->weeklyOn(0, '23:00')
    ->withoutOverlapping();

// AI: Analyze new prayer requests hourly
Schedule::command('ai:analyze-prayers')
    ->hourly()
    ->withoutOverlapping();

// AI: Optimize duty roster pool scores weekly on Monday at 2 AM
Schedule::command('ai:optimize-roster-scores')
    ->weeklyOn(1, '02:00')
    ->withoutOverlapping();

// AI: Calculate SMS engagement scores weekly on Tuesday at 2 AM
Schedule::command('ai:calculate-sms-engagement')
    ->weeklyOn(2, '02:00')
    ->withoutOverlapping();

// AI: Detect member lifecycle stages weekly on Monday at 4 AM
Schedule::command('ai:detect-lifecycle-stages')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();

// AI: Calculate household engagement weekly on Monday at 5 AM
Schedule::command('ai:calculate-household-engagement')
    ->weeklyOn(1, '05:00')
    ->withoutOverlapping();

// AI: Calculate cluster health weekly on Monday at 6 AM
Schedule::command('ai:calculate-cluster-health')
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping();
