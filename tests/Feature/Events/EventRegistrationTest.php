<?php

use App\Enums\BranchRole;
use App\Enums\RegistrationStatus;
use App\Livewire\Events\EventShow;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
    $this->event = Event::factory()->published()->create(['branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admins can view event registrations', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->assertStatus(200);
});

test('staff can view event registrations', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);
    $this->actingAs($user);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->assertStatus(200);
});

// ============================================
// REGISTRATION DISPLAY TESTS
// ============================================

test('event show displays registrations', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    EventRegistration::factory()->guest()->count(3)->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    $component = Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->assertStatus(200);

    expect($this->event->registrations()->count())->toBe(3);
});

// ============================================
// CHECK-IN TESTS
// ============================================

test('admin can check in registration', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->call('checkIn', $registration)
        ->assertDispatched('attendee-checked-in');

    $registration->refresh();
    expect($registration->status)->toBe(RegistrationStatus::Attended)
        ->and($registration->check_in_time)->not->toBeNull();
});

test('admin can check out registration', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    $registration = EventRegistration::factory()->guest()->checkedIn()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'status' => RegistrationStatus::Attended,
    ]);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->call('checkOut', $registration)
        ->assertDispatched('attendee-checked-out');

    $registration->refresh();
    expect($registration->check_out_time)->not->toBeNull();
});

// ============================================
// ADD REGISTRATION TESTS
// ============================================

test('admin can add member registration', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->set('registrationType', 'member')
        ->set('member_id', $member->id)
        ->call('addRegistration')
        ->assertDispatched('registration-added');

    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $this->event->id,
        'member_id' => $member->id,
    ]);
});

test('admin can add guest registration', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->set('registrationType', 'guest')
        ->set('guest_name', 'John Doe')
        ->set('guest_email', 'john@example.com')
        ->call('addRegistration')
        ->assertDispatched('registration-added');

    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $this->event->id,
        'guest_name' => 'John Doe',
        'guest_email' => 'john@example.com',
    ]);
});

// ============================================
// CANCEL REGISTRATION TESTS
// ============================================

test('admin can cancel registration', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(EventShow::class, ['branch' => $this->branch, 'event' => $this->event])
        ->call('confirmCancel', $registration)
        ->call('cancelRegistration')
        ->assertDispatched('registration-cancelled');

    $registration->refresh();
    expect($registration->status)->toBe(RegistrationStatus::Cancelled);
});

// ============================================
// REGISTRATION MODEL TESTS
// ============================================

test('registration generates ticket number', function (): void {
    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => null,
    ]);

    $registration->generateTicketNumber();

    expect($registration->ticket_number)->not->toBeNull()
        ->and($registration->ticket_number)->toStartWith('EVT-');
});

test('registration marks as attended', function (): void {
    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    $registration->markAsAttended();

    expect($registration->status)->toBe(RegistrationStatus::Attended)
        ->and($registration->check_in_time)->not->toBeNull();
});

test('registration marks as cancelled', function (): void {
    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    $registration->markAsCancelled();

    expect($registration->status)->toBe(RegistrationStatus::Cancelled)
        ->and($registration->cancelled_at)->not->toBeNull();
});

test('registration marks as no show', function (): void {
    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
    ]);

    $registration->markAsNoShow();

    expect($registration->status)->toBe(RegistrationStatus::NoShow);
});

test('registration attendee name returns member name', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $registration = EventRegistration::factory()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    expect($registration->attendee_name)->toContain('Jane')
        ->and($registration->attendee_name)->toContain('Smith');
});

test('registration attendee name returns guest name', function (): void {
    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'guest_name' => 'Guest Person',
    ]);

    expect($registration->attendee_name)->toBe('Guest Person');
});
