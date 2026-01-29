<?php

use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use Illuminate\Support\Facades\Crypt;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch with SMS configured
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_birthday_sms' => true,
        ],
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command sends birthday SMS to members with birthday today', function (): void {
    // Create a member with birthday today
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'middle_name' => null,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    // Run the command with dry-run to test detection logic
    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput('Starting birthday SMS job...')
        ->expectsOutputToContain('Found 1 birthday(s)')
        ->assertSuccessful();
});

test('command skips members without birthday today', function (): void {
    // Create a member with birthday yesterday
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subDays(1)->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: No birthdays today")
        ->assertSuccessful();
});

test('command skips branch without SMS configured', function (): void {
    // Create branch without SMS config
    $branchNoSms = Branch::factory()->create([
        'settings' => [],
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $branchNoSms->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput("  Branch {$branchNoSms->name}: SMS not configured, skipping")
        ->assertSuccessful();
});

test('command skips branch with auto birthday sms disabled', function (): void {
    // Update branch to disable auto birthday SMS
    $this->branch->setSetting('auto_birthday_sms', false);
    $this->branch->save();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: Auto birthday SMS disabled, skipping")
        ->assertSuccessful();
});

test('command skips members without phone number', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => null,
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->assertSuccessful();
});

test('command skips inactive members', function (): void {
    $member = Member::factory()->inactive()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: No birthdays today")
        ->assertSuccessful();
});

test('command skips members already sent birthday SMS today', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    // Create an existing birthday SMS log for today
    SmsLog::factory()->birthday()->today()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'phone_number' => $member->phone,
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Already sent today, skipping')
        ->assertSuccessful();
});

test('command uses birthday template when configured', function (): void {
    // Create a birthday template
    $template = SmsTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Custom Birthday',
        'body' => 'Blessed birthday {first_name}! May God bless you!',
        'type' => SmsType::Birthday,
        'is_active' => true,
    ]);

    // Set the template in branch settings
    $this->branch->setSetting('birthday_template_id', $template->id);
    $this->branch->save();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'David',
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Blessed birthday David! May God bless you!')
        ->assertSuccessful();
});

test('command personalizes message with member details', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Mary',
        'last_name' => 'Smith',
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Happy Birthday, Mary!')
        ->assertSuccessful();
});

test('dry run mode does not send actual SMS', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput('DRY RUN MODE - No SMS will actually be sent')
        ->expectsOutputToContain('Would send to')
        ->assertSuccessful();
});

test('command handles multiple members with birthdays', function (): void {
    // Create multiple members with birthday today
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Found 3 birthday(s)')
        ->assertSuccessful();
});
