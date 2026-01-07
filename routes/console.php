<?php

use App\Enums\AnnouncementStatus;
use App\Jobs\ProcessAnnouncementJob;
use App\Models\Announcement;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
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

// Check budget thresholds hourly and send alerts
Schedule::command('budgets:check-thresholds')
    ->hourly()
    ->withoutOverlapping();

// Generate recurring expenses daily at 7 AM
Schedule::command('expenses:generate-recurring')
    ->dailyAt('07:00')
    ->withoutOverlapping();

// Process scheduled announcements every minute
Schedule::call(function () {
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
