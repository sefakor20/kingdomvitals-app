<?php

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

test('membership number is auto-generated on member creation', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member->membership_number)->toStartWith('MEM-');
    expect($member->membership_number)->toBe('MEM-0001');
});

test('membership numbers are sequential', function (): void {
    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $member3 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member1->membership_number)->toBe('MEM-0001');
    expect($member2->membership_number)->toBe('MEM-0002');
    expect($member3->membership_number)->toBe('MEM-0003');
});

test('membership number generation considers soft-deleted members', function (): void {
    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $member1->delete(); // Soft delete

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member1->membership_number)->toBe('MEM-0001');
    expect($member2->membership_number)->toBe('MEM-0002');
});

test('membership number is not overwritten if already set', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'membership_number' => 'CUSTOM-999',
    ]);

    expect($member->membership_number)->toBe('CUSTOM-999');
});

test('generateMembershipNumber returns correct format', function (): void {
    $number = Member::generateMembershipNumber();

    expect($number)->toStartWith('MEM-');
    expect(strlen($number))->toBe(8); // MEM-0001 = 8 chars
});
