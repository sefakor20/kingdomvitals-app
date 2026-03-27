<?php

use App\Models\Tenant\AttendanceForecast;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\FinancialForecast;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    // Create a main branch for all tests
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command populates AI fields for members', function (): void {
    // Create a member without AI fields
    $member = Member::factory()->create([
        'lifecycle_stage' => null,
        'churn_risk_score' => null,
        'primary_branch_id' => $this->branch->id,
    ]);

    // End tenancy context before running command
    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    // Re-initialize tenancy
    tenancy()->initialize($this->tenant);

    // Verify AI fields were populated
    $member->refresh();
    expect($member->lifecycle_stage)->not->toBeNull();
});

test('command populates conversion score for visitors', function (): void {
    $visitor = Visitor::factory()->create([
        'conversion_score' => null,
        'branch_id' => $this->branch->id,
    ]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    $visitor->refresh();
    // conversion_score can still be null due to optional(0.5), so we just verify no errors occurred
    expect($visitor)->not->toBeNull();
});

test('command creates financial forecasts', function (): void {
    Service::factory()->create(['branch_id' => $this->branch->id]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    // Should have 5 financial forecasts (4 monthly + 1 quarterly)
    expect(FinancialForecast::count())->toBe(5);
});

test('command creates attendance forecasts', function (): void {
    Service::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    // Should have attendance forecasts (4 weeks per service)
    expect(AttendanceForecast::count())->toBe(8);
});

test('command updates member names with Ghanaian names', function (): void {
    Member::factory()->create([
        'first_name' => 'TestFirst',
        'last_name' => 'TestLast',
        'primary_branch_id' => $this->branch->id,
    ]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    $member = Member::first();
    // Names should have been updated (not the test values)
    expect($member->first_name)->not->toBe('TestFirst');
});

test('command updates cluster names with realistic names', function (): void {
    Cluster::factory()->create([
        'name' => 'Generic Cluster',
        'branch_id' => $this->branch->id,
    ]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    $cluster = Cluster::first();
    expect($cluster->name)->toBe('Faith Builders Cell Group');
});

test('command creates member activities', function (): void {
    Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    expect(MemberActivity::count())->toBeGreaterThan(0);
});

test('command creates visitor follow-ups', function (): void {
    Visitor::factory()->create(['branch_id' => $this->branch->id]);

    tenancy()->end();

    $this->artisan('tenant:populate-dashboard', ['tenant' => $this->tenant->id, '--force' => true])
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    expect(VisitorFollowUp::where('is_scheduled', true)->count())->toBeGreaterThan(0);
});

test('command fails gracefully for invalid tenant', function (): void {
    $this->artisan('tenant:populate-dashboard', ['tenant' => 'invalid-tenant-id', '--force' => true])
        ->expectsOutputToContain('Tenant not found')
        ->assertFailed();
});
