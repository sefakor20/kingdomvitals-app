<?php

use App\Enums\ActivityEvent;
use App\Enums\SubjectType;
use App\Models\Tenant\ActivityLog;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// MODEL CRUD LOGGING TESTS
// ============================================

test('activity is logged when member is created', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Created)
        ->and($log->subject_type)->toBe(SubjectType::Member)
        ->and($log->subject_id)->toBe($member->id)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->branch_id)->toBe($this->branch->id);
});

test('activity is logged when member is updated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
    ]);

    ActivityLog::query()->delete();

    $member->update(['first_name' => 'Jane']);

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Updated)
        ->and($log->changed_fields)->toContain('first_name')
        ->and($log->old_values['first_name'])->toBe('John')
        ->and($log->new_values['first_name'])->toBe('Jane');
});

test('activity is logged when member is deleted', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    ActivityLog::query()->delete();

    $member->delete();

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Deleted)
        ->and($log->subject_id)->toBe($member->id);
});

test('activity is logged when member is restored', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $member->delete();
    ActivityLog::query()->delete();

    $member->restore();

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Restored);
});

test('activity is logged when donation is created', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
    ]);

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Created)
        ->and($log->subject_type)->toBe(SubjectType::Donation)
        ->and($log->subject_id)->toBe($donation->id);
});

test('activity is logged when visitor is created', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'last_name' => 'Visitor',
    ]);

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();
    expect($log->event)->toBe(ActivityEvent::Created)
        ->and($log->subject_type)->toBe(SubjectType::Visitor)
        ->and($log->subject_name)->toBe('Test Visitor');
});

// ============================================
// ACTIVITY LOG MODEL TESTS
// ============================================

test('formatted description is generated correctly for create event', function (): void {
    $user = User::factory()->create(['name' => 'Admin User']);
    $log = ActivityLog::factory()->created()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $user->id,
        'subject_type' => SubjectType::Member,
        'subject_name' => 'John Doe',
    ]);

    expect($log->formatted_description)->toBe('Admin User created John Doe');
});

test('formatted description is generated correctly for update event', function (): void {
    $user = User::factory()->create(['name' => 'Admin User']);
    $log = ActivityLog::factory()->updated()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $user->id,
        'subject_type' => SubjectType::Member,
        'subject_name' => 'John Doe',
    ]);

    expect($log->formatted_description)->toBe('Admin User updated John Doe');
});

test('formatted description uses System when no user', function (): void {
    $log = ActivityLog::factory()->created()->create([
        'branch_id' => $this->branch->id,
        'user_id' => null,
        'subject_type' => SubjectType::Member,
        'subject_name' => 'John Doe',
    ]);

    expect($log->formatted_description)->toBe('System created John Doe');
});

test('activity log can use static log method', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = ActivityLog::log(
        branchId: $this->branch->id,
        event: ActivityEvent::Created,
        subjectType: SubjectType::Member,
        subjectId: 'test-id',
        subjectName: 'Test Member',
    );

    expect($log->exists)->toBeTrue()
        ->and($log->event)->toBe(ActivityEvent::Created)
        ->and($log->subject_type)->toBe(SubjectType::Member)
        ->and($log->subject_name)->toBe('Test Member')
        ->and($log->user_id)->toBe($user->id);
});

// ============================================
// TIMESTAMP-ONLY CHANGES SHOULD NOT LOG
// ============================================

test('activity is not logged when only timestamps change', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    ActivityLog::query()->delete();

    // Manually touch the model (only updates timestamps)
    $member->touch();

    expect(ActivityLog::count())->toBe(0);
});
