<?php

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_birthday_sms' => true,
        ],
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// Member Model Scope Tests

test('scopeNotOptedOutOfSms returns members who have not opted out', function () {
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'sms_opt_out' => false,
    ]);

    Member::factory()->count(2)->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $notOptedOut = Member::notOptedOutOfSms()->get();

    expect($notOptedOut)->toHaveCount(3);
    expect($notOptedOut->every(fn ($m) => $m->sms_opt_out === false))->toBeTrue();
});

test('scopeOptedOutOfSms returns only members who have opted out', function () {
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'sms_opt_out' => false,
    ]);

    Member::factory()->count(2)->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $optedOut = Member::optedOutOfSms()->get();

    expect($optedOut)->toHaveCount(2);
    expect($optedOut->every(fn ($m) => $m->sms_opt_out === true))->toBeTrue();
});

test('hasOptedOutOfSms returns correct boolean', function () {
    $notOptedOut = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'sms_opt_out' => false,
    ]);

    $optedOut = Member::factory()->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($notOptedOut->hasOptedOutOfSms())->toBeFalse();
    expect($optedOut->hasOptedOutOfSms())->toBeTrue();
});

test('sms_opt_out defaults to false for new members', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member->sms_opt_out)->toBeFalse();
});

test('sms_opt_out can be toggled', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'sms_opt_out' => false,
    ]);

    expect($member->sms_opt_out)->toBeFalse();

    $member->sms_opt_out = true;
    $member->save();
    $member->refresh();

    expect($member->sms_opt_out)->toBeTrue();

    $member->sms_opt_out = false;
    $member->save();
    $member->refresh();

    expect($member->sms_opt_out)->toBeFalse();
});

test('optedOutOfSms factory state sets sms_opt_out to true', function () {
    $member = Member::factory()->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member->sms_opt_out)->toBeTrue();
});

// Birthday SMS Command Tests

test('birthday command skips opted-out members', function () {
    $optedIn = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'OptedIn',
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
        'sms_opt_out' => false,
    ]);

    $optedOut = Member::factory()->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'OptedOut',
        'phone' => '+233241234568',
        'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Found 1 birthday(s)')
        ->assertSuccessful();
});

test('birthday command correctly counts birthdays excluding opted-out', function () {
    // 3 opted-in members with birthday today
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        'status' => 'active',
        'sms_opt_out' => false,
    ]);

    // 2 opted-out members with birthday today
    Member::factory()->count(2)->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234568',
        'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutputToContain('Found 3 birthday(s)')
        ->assertSuccessful();
});

test('birthday command shows no birthdays when all members are opted out', function () {
    // Only opted-out members with birthday today
    Member::factory()->count(2)->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234568',
        'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->artisan('sms:send-birthday', ['--dry-run' => true])
        ->expectsOutput("  Branch {$this->branch->name}: No birthdays today")
        ->assertSuccessful();
});

// Combined Query Tests

test('scopes can be combined with other queries', function () {
    // Active, opted-in members
    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'phone' => '+233241234567',
        'sms_opt_out' => false,
    ]);

    // Active, opted-out members
    Member::factory()->count(3)->optedOutOfSms()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'phone' => '+233241234568',
    ]);

    // Inactive, opted-in members
    Member::factory()->inactive()->count(1)->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234569',
        'sms_opt_out' => false,
    ]);

    $activeOptedIn = Member::where('status', 'active')
        ->notOptedOutOfSms()
        ->get();

    expect($activeOptedIn)->toHaveCount(2);

    $optedOutWithPhone = Member::optedOutOfSms()
        ->whereNotNull('phone')
        ->get();

    expect($optedOutWithPhone)->toHaveCount(3);
});
