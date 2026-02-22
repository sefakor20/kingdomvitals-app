<?php

use App\Enums\BranchRole;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Livewire\Events\EventIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
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
// AUTHORIZATION TESTS
// ============================================

test('admins can view events', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('managers can view events', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);
    $this->actingAs($user);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('staff can view events', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);
    $this->actingAs($user);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('component displays events', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Event::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertViewHas('events', fn ($events) => $events->count() === 3);
});

test('component shows empty state when no events', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->assertSee('No events found');
});

// ============================================
// FILTER TESTS
// ============================================

test('component can filter by event type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Event::factory()->conference()->create(['branch_id' => $this->branch->id]);
    Event::factory()->workshop()->create(['branch_id' => $this->branch->id]);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', EventType::Conference->value)
        ->assertViewHas('events', fn ($events) => $events->count() === 1);
});

test('component can filter by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Event::factory()->create(['branch_id' => $this->branch->id, 'status' => EventStatus::Draft]);
    Event::factory()->published()->create(['branch_id' => $this->branch->id]);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', EventStatus::Published->value)
        ->assertViewHas('events', fn ($events) => $events->count() === 1);
});

test('component can search by name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Event::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Youth Conference 2026']);
    Event::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Leadership Retreat']);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->set('search', 'Youth')
        ->assertViewHas('events', fn ($events) => $events->count() === 1);
});

// ============================================
// CRUD TESTS
// ============================================

test('admin can create event', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->set('name', 'Annual Conference')
        ->set('description', 'A great event')
        ->set('event_type', EventType::Conference->value)
        ->set('starts_at', now()->addDays(30)->format('Y-m-d\TH:i'))
        ->set('location', 'Main Hall')
        ->set('visibility', 'public')
        ->set('status', 'draft')
        ->call('store')
        ->assertDispatched('event-created');

    $this->assertDatabaseHas('events', [
        'branch_id' => $this->branch->id,
        'name' => 'Annual Conference',
    ]);
});

test('admin can delete event', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    $event = Event::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::test(EventIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $event)
        ->call('delete')
        ->assertDispatched('event-deleted');

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});
