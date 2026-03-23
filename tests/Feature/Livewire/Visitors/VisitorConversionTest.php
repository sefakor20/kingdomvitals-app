<?php

use App\Enums\BranchRole;
use App\Enums\VisitorStatus;
use App\Livewire\Visitors\VisitorIndex;
use App\Livewire\Visitors\VisitorShow;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use App\Services\PlanAccessService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    Cache::flush();
    app()->forgetInstance(PlanAccessService::class);

    $this->branch = Branch::factory()->main()->create();

    $this->user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    $this->visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john.smith@example.com',
        'phone' => '123-456-7890',
        'status' => VisitorStatus::New,
        'is_converted' => false,
    ]);

    $this->existingMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// VisitorShow Tests
// ============================================

test('visitor show can link to existing member', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->assertSet('showConvertModal', true)
        ->assertSet('conversionMode', 'link')
        ->set('convertToMemberId', $this->existingMember->id)
        ->call('convert')
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted');

    $this->visitor->refresh();
    expect($this->visitor->is_converted)->toBeTrue();
    expect($this->visitor->converted_member_id)->toBe($this->existingMember->id);
    expect($this->visitor->status)->toBe(VisitorStatus::Converted);
});

test('visitor show can create new member and convert', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->assertSet('showConvertModal', true)
        ->set('conversionMode', 'create')
        ->assertSet('newMemberFirstName', 'John')
        ->assertSet('newMemberLastName', 'Smith')
        ->assertSet('newMemberEmail', 'john.smith@example.com')
        ->assertSet('newMemberPhone', '123-456-7890')
        ->set('newMemberGender', 'male')
        ->set('newMemberStatus', 'active')
        ->call('convertAndCreate')
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted-and-created');

    $this->visitor->refresh();
    expect($this->visitor->is_converted)->toBeTrue();
    expect($this->visitor->converted_member_id)->not->toBeNull();
    expect($this->visitor->status)->toBe(VisitorStatus::Converted);

    // Verify member was created with correct data
    $newMember = Member::find($this->visitor->converted_member_id);
    expect($newMember)->not->toBeNull();
    expect($newMember->first_name)->toBe('John');
    expect($newMember->last_name)->toBe('Smith');
    expect($newMember->email)->toBe('john.smith@example.com');
    expect($newMember->phone)->toBe('123-456-7890');
    expect($newMember->gender->value)->toBe('male');
    expect($newMember->primary_branch_id)->toBe($this->branch->id);
});

test('visitor show pre-fills form with visitor data when switching to create mode', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->assertSet('newMemberFirstName', '')
        ->set('conversionMode', 'create')
        ->assertSet('newMemberFirstName', 'John')
        ->assertSet('newMemberLastName', 'Smith')
        ->assertSet('newMemberEmail', 'john.smith@example.com')
        ->assertSet('newMemberPhone', '123-456-7890')
        ->assertSet('newMemberJoinedAt', $this->visitor->visit_date->format('Y-m-d'));
});

test('visitor show validates new member fields', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->set('conversionMode', 'create')
        ->set('newMemberFirstName', '')
        ->set('newMemberLastName', '')
        ->call('convertAndCreate')
        ->assertHasErrors(['newMemberFirstName', 'newMemberLastName']);
});

test('visitor show validates email format', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->set('conversionMode', 'create')
        ->set('newMemberFirstName', 'John')
        ->set('newMemberLastName', 'Smith')
        ->set('newMemberEmail', 'invalid-email')
        ->call('convertAndCreate')
        ->assertHasErrors(['newMemberEmail']);
});

// ============================================
// VisitorIndex Tests
// ============================================

test('visitor index can link to existing member', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $this->visitor)
        ->assertSet('showConvertModal', true)
        ->assertSet('conversionMode', 'link')
        ->set('convertToMemberId', $this->existingMember->id)
        ->call('convert')
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted');

    $this->visitor->refresh();
    expect($this->visitor->is_converted)->toBeTrue();
    expect($this->visitor->converted_member_id)->toBe($this->existingMember->id);
});

test('visitor index can create new member and convert', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $this->visitor)
        ->assertSet('showConvertModal', true)
        ->set('conversionMode', 'create')
        ->assertSet('newMemberFirstName', 'John')
        ->assertSet('newMemberLastName', 'Smith')
        ->set('newMemberGender', 'male')
        ->call('convertAndCreate')
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted-and-created');

    $this->visitor->refresh();
    expect($this->visitor->is_converted)->toBeTrue();
    expect($this->visitor->converted_member_id)->not->toBeNull();

    $newMember = Member::find($this->visitor->converted_member_id);
    expect($newMember->first_name)->toBe('John');
    expect($newMember->last_name)->toBe('Smith');
});

test('visitor index pre-fills form with visitor data', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $this->visitor)
        ->assertSet('newMemberFirstName', '')
        ->set('conversionMode', 'create')
        ->assertSet('newMemberFirstName', 'John')
        ->assertSet('newMemberLastName', 'Smith')
        ->assertSet('newMemberEmail', 'john.smith@example.com')
        ->assertSet('newMemberPhone', '123-456-7890');
});

// ============================================
// Quota Tests
// ============================================

test('create and convert fails when member quota exceeded', function (): void {
    // Create a limited plan
    $limitedPlan = SubscriptionPlan::create([
        'slug' => 'test-limited',
        'name' => 'Test Limited',
        'description' => 'Limited plan for testing',
        'price_monthly' => 0,
        'price_annual' => 0,
        'max_members' => 1,
        'max_branches' => 10,
        'storage_quota_gb' => 10,
        'sms_credits_monthly' => 100,
        'max_households' => 10,
        'max_clusters' => 10,
        'max_visitors' => 100,
        'max_equipment' => 100,
        'enabled_modules' => null,
        'features' => [],
        'is_active' => true,
        'is_default' => false,
        'display_order' => 1,
    ]);

    // Update tenant to use limited plan
    $this->tenant->update(['subscription_id' => $limitedPlan->id]);

    // Clear cache
    Cache::flush();
    app()->forgetInstance(PlanAccessService::class);

    $this->actingAs($this->user);

    // Existing member already counts toward quota
    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->set('conversionMode', 'create')
        ->set('newMemberFirstName', 'John')
        ->set('newMemberLastName', 'Smith')
        ->call('convertAndCreate')
        ->assertHasErrors(['newMemberFirstName']);

    // Visitor should not be converted
    $this->visitor->refresh();
    expect($this->visitor->is_converted)->toBeFalse();
});

test('conversion mode resets when modal is cancelled', function (): void {
    $this->actingAs($this->user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->set('conversionMode', 'create')
        ->set('newMemberFirstName', 'Modified Name')
        ->call('cancelConvert')
        ->assertSet('showConvertModal', false)
        ->assertSet('conversionMode', 'link')
        ->assertSet('newMemberFirstName', '');
});

test('conversion mode resets after successful conversion', function (): void {
    $this->actingAs($this->user);

    $component = Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openConvertModal')
        ->set('conversionMode', 'create')
        ->set('newMemberGender', 'female')
        ->call('convertAndCreate')
        ->assertSet('conversionMode', 'link')
        ->assertSet('newMemberGender', '');
});
