<?php

use App\Enums\FollowUpType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FollowUpTemplate;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command seeds default follow-up templates for branches without them', function (): void {
    // Verify no templates exist initially
    expect(FollowUpTemplate::count())->toBe(0);

    // Run the command for this specific tenant
    $this->artisan('followup:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('Seeding default follow-up templates...')
        ->expectsOutputToContain('created 6 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Verify 6 templates were created
    expect(FollowUpTemplate::count())->toBe(6);

    // Verify all template types exist (including null for generic)
    $types = FollowUpTemplate::where('branch_id', $this->branch->id)
        ->pluck('type')
        ->map(fn ($type) => $type?->value)
        ->toArray();

    expect($types)->toContain(null)
        ->toContain('call')
        ->toContain('sms')
        ->toContain('email')
        ->toContain('visit')
        ->toContain('whatsapp');
});

test('command skips branches that already have templates', function (): void {
    // Create one template for the branch
    FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'type' => FollowUpType::Call,
    ]);

    expect(FollowUpTemplate::count())->toBe(1);

    $this->artisan('followup:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('created 5 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Should have created 5 more (skipped call)
    expect(FollowUpTemplate::count())->toBe(6);
});

test('command skips all templates when branch already has all', function (): void {
    // Create all 6 templates for the types the command seeds
    $templateTypes = [null, FollowUpType::Call, FollowUpType::Sms, FollowUpType::Email, FollowUpType::Visit, FollowUpType::WhatsApp];

    foreach ($templateTypes as $type) {
        FollowUpTemplate::factory()->create([
            'branch_id' => $this->branch->id,
            'type' => $type,
        ]);
    }

    expect(FollowUpTemplate::count())->toBe(6);

    $this->artisan('followup:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('Created 0 templates, skipped 6')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Count should remain the same
    expect(FollowUpTemplate::count())->toBe(6);
});

test('command handles multiple branches', function (): void {
    // Create a second branch
    $secondBranch = Branch::factory()->create();

    $this->artisan('followup:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->expectsOutputToContain('created 6 templates')
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Both branches should have 6 templates each
    expect(FollowUpTemplate::where('branch_id', $this->branch->id)->count())->toBe(6);
    expect(FollowUpTemplate::where('branch_id', $secondBranch->id)->count())->toBe(6);
    expect(FollowUpTemplate::count())->toBe(12);
});

test('templates are created with correct default content', function (): void {
    $this->artisan('followup:seed-templates', ['--tenant' => [$this->tenant->id]])
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    $callTemplate = FollowUpTemplate::where('branch_id', $this->branch->id)
        ->where('type', FollowUpType::Call)
        ->first();

    expect($callTemplate)
        ->name->toBe('Phone Call Script')
        ->is_active->toBeTrue()
        ->body->toContain('calling to follow up');
});
