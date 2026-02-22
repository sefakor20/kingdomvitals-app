<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
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
// MODEL RELATIONSHIP TESTS
// ============================================

test('event belongs to a branch', function (): void {
    $event = Event::factory()->create(['branch_id' => $this->branch->id]);

    expect($event->branch)->toBeInstanceOf(Branch::class)
        ->and($event->branch->id)->toBe($this->branch->id);
});

test('event belongs to an organizer', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'organizer_id' => $member->id,
    ]);

    expect($event->organizer)->toBeInstanceOf(Member::class)
        ->and($event->organizer->id)->toBe($member->id);
});

test('event has many registrations', function (): void {
    $event = Event::factory()->create(['branch_id' => $this->branch->id]);
    EventRegistration::factory()->count(3)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->registrations)->toHaveCount(3);
});

// ============================================
// SCOPE TESTS
// ============================================

test('upcoming scope returns future events', function (): void {
    Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addDays(5),
    ]);
    Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->subDays(5),
    ]);

    $upcoming = Event::upcoming()->get();

    expect($upcoming)->toHaveCount(1)
        ->and($upcoming->first()->starts_at->isFuture())->toBeTrue();
});

test('past scope returns past events', function (): void {
    Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(5),
    ]);
    Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addDays(5),
    ]);

    $past = Event::past()->get();

    expect($past)->toHaveCount(1);
});

test('published scope returns published events', function (): void {
    Event::factory()->published()->create(['branch_id' => $this->branch->id]);
    Event::factory()->create(['branch_id' => $this->branch->id, 'status' => EventStatus::Draft]);

    $published = Event::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->status)->toBe(EventStatus::Published);
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

test('registered count returns correct number', function (): void {
    $event = Event::factory()->create(['branch_id' => $this->branch->id]);
    EventRegistration::factory()->count(5)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);
    // Create a cancelled registration that shouldn't count
    EventRegistration::factory()->cancelled()->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->registered_count)->toBe(5);
});

test('attended count returns correct number', function (): void {
    $event = Event::factory()->create(['branch_id' => $this->branch->id]);
    EventRegistration::factory()->attended()->count(3)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);
    EventRegistration::factory()->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->attended_count)->toBe(3);
});

test('available spots returns correct number', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => 100,
    ]);
    EventRegistration::factory()->count(30)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->available_spots)->toBe(70);
});

test('available spots returns null when no capacity', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => null,
    ]);

    expect($event->available_spots)->toBeNull();
});

test('is full returns true when at capacity', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => 5,
    ]);
    EventRegistration::factory()->count(5)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->is_full)->toBeTrue();
});

test('is full returns false when under capacity', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => 10,
    ]);
    EventRegistration::factory()->count(5)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->is_full)->toBeFalse();
});

test('is full returns false when no capacity limit', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => null,
    ]);

    expect($event->is_full)->toBeFalse();
});

test('formatted price returns correct format', function (): void {
    $event = Event::factory()->paid()->create([
        'branch_id' => $this->branch->id,
        'price' => 50.00,
        'currency' => 'GHS',
    ]);

    expect($event->formatted_price)->toBe('GHS 50.00');
});

test('formatted price returns free for non-paid events', function (): void {
    $event = Event::factory()->free()->create(['branch_id' => $this->branch->id]);

    expect($event->formatted_price)->toBe('Free');
});

// ============================================
// HELPER METHOD TESTS
// ============================================

test('publish changes status to published', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => EventStatus::Draft,
    ]);

    $event->publish();

    expect($event->status)->toBe(EventStatus::Published);
});

test('cancel changes status to cancelled', function (): void {
    $event = Event::factory()->published()->create(['branch_id' => $this->branch->id]);

    $event->cancel();

    expect($event->status)->toBe(EventStatus::Cancelled);
});

test('complete changes status to completed', function (): void {
    $event = Event::factory()->published()->create(['branch_id' => $this->branch->id]);

    $event->complete();

    expect($event->status)->toBe(EventStatus::Completed);
});

test('can register returns true for valid event', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'allow_registration' => true,
        'capacity' => 100,
        'starts_at' => now()->addDays(5),
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDays(3),
    ]);

    expect($event->canRegister())->toBeTrue();
});

test('can register returns false for full event', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'allow_registration' => true,
        'capacity' => 2,
        'starts_at' => now()->addDays(5),
    ]);
    EventRegistration::factory()->count(2)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    expect($event->canRegister())->toBeFalse();
});

test('can register returns false when registration disabled', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'allow_registration' => false,
        'starts_at' => now()->addDays(5),
    ]);

    expect($event->canRegister())->toBeFalse();
});

// ============================================
// ENUM CASTING TESTS
// ============================================

test('event type is cast to enum', function (): void {
    $event = Event::factory()->conference()->create(['branch_id' => $this->branch->id]);

    expect($event->event_type)->toBeInstanceOf(EventType::class)
        ->and($event->event_type)->toBe(EventType::Conference);
});

test('status is cast to enum', function (): void {
    $event = Event::factory()->published()->create(['branch_id' => $this->branch->id]);

    expect($event->status)->toBeInstanceOf(EventStatus::class)
        ->and($event->status)->toBe(EventStatus::Published);
});

test('visibility is cast to enum', function (): void {
    $event = Event::factory()->membersOnly()->create(['branch_id' => $this->branch->id]);

    expect($event->visibility)->toBeInstanceOf(EventVisibility::class)
        ->and($event->visibility)->toBe(EventVisibility::MembersOnly);
});
