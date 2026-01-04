<?php

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
