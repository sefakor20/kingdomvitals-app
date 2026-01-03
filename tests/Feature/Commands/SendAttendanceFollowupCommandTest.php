<?php

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

    // Create main branch with SMS and attendance follow-up configured
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_attendance_followup' => true,
            'attendance_followup_hours' => 24,
            'attendance_followup_recipients' => 'all',
            'attendance_followup_min_attendance' => 3,
        ],
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('command detects missed services within follow-up window', function () {
    // Create a service that occurred 26 hours ago (within 24h-48h window after 24h delay)
    $pastTime = now()->subHours(26);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create a member who missed the service
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutput('Starting attendance follow-up SMS job...')
        ->expectsOutputToContain('service(s) to follow up')
        ->assertSuccessful();
});

test('command skips services outside follow-up window', function () {
    // Create a service that occurred 2 hours ago (outside follow-up window)
    $pastTime = now()->subHours(2);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Recent Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('No services in follow-up window')
        ->assertSuccessful();
});

test('command skips branches without SMS configured', function () {
    $branchNoSms = Branch::factory()->create([
        'settings' => ['auto_attendance_followup' => true],
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutput("  Branch {$branchNoSms->name}: SMS not configured, skipping")
        ->assertSuccessful();
});

test('command skips branches with attendance follow-up disabled', function () {
    $this->branch->setSetting('auto_attendance_followup', false);
    $this->branch->save();

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: Attendance follow-up disabled, skipping")
        ->assertSuccessful();
});

test('command excludes members who attended the service', function () {
    // Create a service that occurred 26 hours ago
    $pastTime = now()->subHours(26);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create a member who attended
    $attendee = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Attendee',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create attendance record
    Attendance::factory()->create([
        'service_id' => $service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $attendee->id,
        'date' => $pastTime->toDateString(),
    ]);

    // Create a member who missed
    $absentee = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Absentee',
        'middle_name' => null,
        'phone' => '+233241234568',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('Would send to Absentee')
        ->assertSuccessful();
});

test('command filters regular attendees only when configured', function () {
    $this->branch->setSetting('attendance_followup_recipients', 'regular');
    $this->branch->setSetting('attendance_followup_min_attendance', 2);
    $this->branch->save();

    // Create a service that occurred 26 hours ago
    $pastTime = now()->subHours(26);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create a regular attendee (has attended multiple times before)
    $regularMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Regular',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create past attendance records for the regular member on different dates
    for ($i = 1; $i <= 3; $i++) {
        Attendance::factory()->create([
            'service_id' => $service->id,
            'branch_id' => $this->branch->id,
            'member_id' => $regularMember->id,
            'date' => now()->subWeeks($i)->toDateString(),
        ]);
    }

    // Create an irregular member (has not attended enough)
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Irregular',
        'middle_name' => null,
        'phone' => '+233241234568',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('Would send to Regular')
        ->assertSuccessful();
});

test('command sends to all members when configured', function () {
    $this->branch->setSetting('attendance_followup_recipients', 'all');
    $this->branch->save();

    // Create a service that occurred 26 hours ago
    $pastTime = now()->subHours(26);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create members
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Member1',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Member2',
        'middle_name' => null,
        'phone' => '+233241234568',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('2 member(s) to follow up')
        ->assertSuccessful();
});

test('command prevents duplicate follow-ups', function () {
    // Create a service that occurred 26 hours ago
    $pastTime = now()->subHours(26);
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create an existing follow-up SMS log for this service
    SmsLog::factory()->followup()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'phone_number' => $member->phone,
        'message' => 'Hi John, we missed you at Sunday Service!',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('Already followed up, skipping')
        ->assertSuccessful();
});

test('command uses custom template when configured', function () {
    $pastTime = now()->subHours(26);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Midweek Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    $template = SmsTemplate::factory()->followUp()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Custom Follow-up',
        'body' => 'Hey {first_name}, we missed you at {service_name}!',
        'is_active' => true,
    ]);

    $this->branch->setSetting('attendance_followup_template_id', $template->id);
    $this->branch->save();

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Mary',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('Would send to Mary')
        ->assertSuccessful();
});

test('dry run mode does not send actual SMS', function () {
    $pastTime = now()->subHours(26);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutput('DRY RUN MODE - No SMS will actually be sent')
        ->assertSuccessful();
});

test('command skips inactive services', function () {
    $pastTime = now()->subHours(26);
    Service::factory()->inactive()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Inactive Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('No services in follow-up window')
        ->assertSuccessful();
});

test('command skips members without phone numbers', function () {
    $pastTime = now()->subHours(26);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create member without phone
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => null,
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('No members to follow up')
        ->assertSuccessful();
});

test('command skips inactive members', function () {
    $pastTime = now()->subHours(26);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    // Create inactive member
    Member::factory()->inactive()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('No members to follow up')
        ->assertSuccessful();
});

test('command respects configurable follow-up hours', function () {
    // Set follow-up to 6 hours
    $this->branch->setSetting('attendance_followup_hours', 6);
    $this->branch->save();

    // Create a service that occurred 8 hours ago (within 6h-30h window after 6h delay)
    $pastTime = now()->subHours(8);
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Short Window Service',
        'day_of_week' => $pastTime->dayOfWeek,
        'time' => $pastTime->format('H:i:s'),
        'is_active' => true,
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'middle_name' => null,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    $this->artisan('sms:send-attendance-followup', ['--dry-run' => true])
        ->expectsOutputToContain('service(s) to follow up')
        ->expectsOutputToContain('Would send to Test')
        ->assertSuccessful();
});
