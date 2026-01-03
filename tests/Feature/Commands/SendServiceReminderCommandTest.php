<?php

use App\Enums\SmsType;
use App\Models\Tenant;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Create main branch with SMS and service reminders configured
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_service_reminder' => true,
            'service_reminder_hours' => 24,
            'service_reminder_recipients' => 'all',
        ],
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('command detects services within reminder window', function () {
    // Create a service that occurs in 12 hours (within 24h window)
    $serviceTime = now()->addHours(12);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Today Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create a member
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutput('Starting service reminder SMS job...')
        ->expectsOutputToContain('upcoming service')
        ->assertSuccessful();
});

test('command skips services outside reminder window', function () {
    // Create a service that occurs in 3 days (outside 24h window)
    $threeDaysFromNow = now()->addDays(3);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Future Service',
        'day_of_week' => $threeDaysFromNow->dayOfWeek,
        'time' => $threeDaysFromNow->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No services within 24h window')
        ->assertSuccessful();
});

test('command skips branches without SMS configured', function () {
    $branchNoSms = Branch::factory()->create([
        'settings' => ['auto_service_reminder' => true],
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutput("  Branch {$branchNoSms->name}: SMS not configured, skipping")
        ->assertSuccessful();
});

test('command skips branches with service reminders disabled', function () {
    $this->branch->setSetting('auto_service_reminder', false);
    $this->branch->save();

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: Service reminders disabled, skipping")
        ->assertSuccessful();
});

test('command prevents duplicate reminders', function () {
    $serviceTime = now()->addHours(12);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
        'is_active' => true,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create an existing reminder SMS log for today with the service name
    SmsLog::factory()->reminder()->today()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'phone_number' => $member->phone,
        'message' => 'Reminder for Sunday Service at 10:00 AM',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('Already reminded today, skipping')
        ->assertSuccessful();
});

test('command uses custom template when configured', function () {
    $serviceTime = now()->addHours(12);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Midweek Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
        'is_active' => true,
    ]);

    $template = SmsTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Custom Reminder',
        'body' => 'Hey {first_name}, join us for {service_name}!',
        'type' => SmsType::Reminder,
        'is_active' => true,
    ]);

    $this->branch->setSetting('service_reminder_template_id', $template->id);
    $this->branch->save();

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Mary',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('Would send to Mary')
        ->assertSuccessful();
});

test('command filters recipients to attendees only', function () {
    $serviceTime = now()->addHours(12);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Prayer Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Set branch to only send to attendees
    $this->branch->setSetting('service_reminder_recipients', 'attendees');
    $this->branch->save();

    // Create members - one who attended, one who didn't
    $attendee = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Regular',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'New',
        'phone' => '+233241234568',
        'status' => 'active',
    ]);

    // Create attendance record for the attendee
    Attendance::factory()->create([
        'service_id' => $service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $attendee->id,
        'date' => now()->subWeek(),
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('1 recipient')
        ->expectsOutputToContain('Would send to Regular')
        ->assertSuccessful();
});

test('command skips inactive services', function () {
    $serviceTime = now()->addHours(12);
    Service::factory()->inactive()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Inactive Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No services within 24h window')
        ->assertSuccessful();
});

test('dry run mode does not send actual SMS', function () {
    $serviceTime = now()->addHours(12);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Service',
        'day_of_week' => $serviceTime->dayOfWeek,
        'time' => $serviceTime->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutput('DRY RUN MODE - No SMS will actually be sent')
        ->assertSuccessful();
});

test('command handles multiple services', function () {
    $serviceTime1 = now()->addHours(6);
    $serviceTime2 = now()->addHours(18);

    // Create multiple services within the window
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Morning Service',
        'day_of_week' => $serviceTime1->dayOfWeek,
        'time' => $serviceTime1->format('H:i:s'),
        'is_active' => true,
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Evening Service',
        'day_of_week' => $serviceTime2->dayOfWeek,
        'time' => $serviceTime2->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('upcoming service')
        ->assertSuccessful();
});

test('command respects configurable reminder hours', function () {
    // Set reminder to 48 hours
    $this->branch->setSetting('service_reminder_hours', 48);
    $this->branch->save();

    // Create a service in 36 hours (within 48h window but outside 24h)
    $inThirtySixHours = now()->addHours(36);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Extended Window Service',
        'day_of_week' => $inThirtySixHours->dayOfWeek,
        'time' => $inThirtySixHours->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-service-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('upcoming service')
        ->assertSuccessful();
});
