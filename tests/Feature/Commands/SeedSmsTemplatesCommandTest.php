<?php

use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command seeds default SMS templates for branches without them', function (): void {
    // Verify no templates exist initially
    expect(SmsTemplate::count())->toBe(0);

    // Run the command for this specific tenant
    $this->artisan('sms:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('Seeding default SMS templates...')
        ->expectsOutputToContain('created 6 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Verify 6 templates were created
    expect(SmsTemplate::count())->toBe(6);

    // Verify all template types exist
    $types = SmsTemplate::where('branch_id', $this->branch->id)
        ->pluck('type')
        ->map(fn ($type) => $type->value)
        ->toArray();

    expect($types)->toContain('birthday')
        ->toContain('welcome')
        ->toContain('reminder')
        ->toContain('follow_up')
        ->toContain('announcement')
        ->toContain('duty_roster_reminder');
});

test('command skips branches that already have templates', function (): void {
    // Create one template for the branch
    SmsTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'type' => SmsType::Birthday,
    ]);

    expect(SmsTemplate::count())->toBe(1);

    $this->artisan('sms:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('created 5 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Should have created 5 more (skipped birthday)
    expect(SmsTemplate::count())->toBe(6);
});

test('command skips all templates when branch already has all', function (): void {
    // Create all 6 templates for the types the command seeds
    $templateTypes = ['birthday', 'welcome', 'reminder', 'follow_up', 'announcement', 'duty_roster_reminder'];

    foreach ($templateTypes as $type) {
        SmsTemplate::factory()->create([
            'branch_id' => $this->branch->id,
            'type' => $type,
        ]);
    }

    expect(SmsTemplate::count())->toBe(6);

    $this->artisan('sms:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('Created 0 templates, skipped 6')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Count should remain the same
    expect(SmsTemplate::count())->toBe(6);
});

test('command handles multiple branches', function (): void {
    // Create a second branch
    $secondBranch = Branch::factory()->create();

    $this->artisan('sms:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('created 6 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Both branches should have 6 templates each
    expect(SmsTemplate::where('branch_id', $this->branch->id)->count())->toBe(6);
    expect(SmsTemplate::where('branch_id', $secondBranch->id)->count())->toBe(6);
    expect(SmsTemplate::count())->toBe(12);
});

test('templates are created with correct default content', function (): void {
    $this->artisan('sms:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    $birthdayTemplate = SmsTemplate::where('branch_id', $this->branch->id)
        ->where('type', SmsType::Birthday)
        ->first();

    expect($birthdayTemplate)
        ->name->toBe('Birthday Greeting')
        ->is_active->toBeTrue()
        ->body->toContain('Happy Birthday');
});
