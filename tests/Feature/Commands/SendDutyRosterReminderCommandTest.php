<?php

use App\Enums\SmsType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Create main branch with SMS configured and duty roster reminders enabled
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_duty_roster_reminder' => true,
            'duty_roster_reminder_days' => 3,
            'duty_roster_reminder_channels' => ['sms', 'email'],
        ],
    ]);

    // Create a service
    $this->service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Worship',
    ]);
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

test('command runs successfully with dry-run option', function (): void {
    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutput('DRY RUN MODE - No messages will actually be sent')
        ->expectsOutput('Starting duty roster reminder job...')
        ->assertSuccessful();
});

test('command skips branch with duty roster reminders disabled', function (): void {
    $this->branch->setSetting('auto_duty_roster_reminder', false);
    $this->branch->save();

    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('Duty roster reminders disabled, skipping')
        ->assertSuccessful();
});

test('command reports no rosters when none exist in reminder window', function (): void {
    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No rosters within 3 day window')
        ->assertSuccessful();
});

test('command finds published rosters within reminder window', function (): void {
    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(2)->startOfDay(),
        'preacher_id' => $preacher->id,
    ]);

    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('Found 1 upcoming roster')
        ->assertSuccessful();
});

test('command skips rosters outside reminder window', function (): void {
    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create roster 5 days away (outside 3-day window)
    DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(5)->startOfDay(),
        'preacher_id' => $preacher->id,
    ]);

    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No rosters within 3 day window')
        ->assertSuccessful();
});

test('command skips unpublished rosters', function (): void {
    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    DutyRoster::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(2)->startOfDay(),
        'preacher_id' => $preacher->id,
    ]);

    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No rosters within 3 day window')
        ->assertSuccessful();
});

test('command skips rosters that already have reminders sent', function (): void {
    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(2)->startOfDay(),
        'preacher_id' => $preacher->id,
        'reminder_sent_at' => now()->subDay(),
    ]);

    // Query filters out rosters with reminder_sent_at set
    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('No rosters within 3 day window')
        ->assertSuccessful();
});

test('command respects reminder days setting', function (): void {
    // Set reminder days to 5
    $this->branch->setSetting('duty_roster_reminder_days', 5);
    $this->branch->save();

    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create roster 4 days away (within 5-day window)
    DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(4)->startOfDay(),
        'preacher_id' => $preacher->id,
    ]);

    $this->artisan('sms:send-duty-roster-reminder', ['--dry-run' => true])
        ->expectsOutputToContain('Found 1 upcoming roster')
        ->assertSuccessful();
});

test('duty roster model has reminder tracking methods', function (): void {
    $roster = DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'service_date' => now()->addDays(2)->startOfDay(),
    ]);

    // Initially no reminder sent
    expect($roster->hasReminderBeenSent())->toBeFalse();

    // Mark reminder as sent
    $roster->markReminderSent();

    expect($roster->fresh()->hasReminderBeenSent())->toBeTrue();
    expect($roster->fresh()->reminder_sent_at)->not->toBeNull();
});

test('sms type enum includes duty roster reminder', function (): void {
    expect(SmsType::DutyRosterReminder->value)->toBe('duty_roster_reminder');
});
