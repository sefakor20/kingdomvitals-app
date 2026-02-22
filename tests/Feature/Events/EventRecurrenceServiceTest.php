<?php

use App\Enums\EventStatus;
use App\Enums\RecurrencePattern;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Services\EventRecurrenceService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
    $this->service = app(EventRecurrenceService::class);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// OCCURRENCE GENERATION TESTS
// ============================================

test('generates weekly occurrences for recurring event', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addDays(7),
        'ends_at' => now()->addDays(7)->addHours(2),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'status' => EventStatus::Published,
    ]);

    $occurrences = $this->service->generateOccurrences($parent, 1);

    expect($occurrences)->toHaveCount(3); // ~4 weeks in a month, minus the parent
    expect($occurrences->first()->parent_event_id)->toBe($parent->id);
    expect($occurrences->first()->occurrence_index)->toBe(1);
});

test('generates biweekly occurrences for recurring event', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addDays(14),
        'ends_at' => now()->addDays(14)->addHours(2),
        'recurrence_pattern' => RecurrencePattern::Biweekly,
        'status' => EventStatus::Published,
    ]);

    $occurrences = $this->service->generateOccurrences($parent, 2);

    // ~8 weeks in 2 months = 4 biweekly occurrences, minus parent
    expect($occurrences->count())->toBeGreaterThanOrEqual(2);
    expect($occurrences->first()->parent_event_id)->toBe($parent->id);
});

test('generates monthly occurrences for recurring event', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addMonth(),
        'ends_at' => now()->addMonth()->addHours(2),
        'recurrence_pattern' => RecurrencePattern::Monthly,
        'status' => EventStatus::Published,
    ]);

    $occurrences = $this->service->generateOccurrences($parent, 3);

    // 3 months = 2-3 occurrences (depending on start date)
    expect($occurrences->count())->toBeGreaterThanOrEqual(1);
    expect($occurrences->first()->parent_event_id)->toBe($parent->id);
});

test('respects recurrence count limit', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addWeek(),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'recurrence_count' => 3, // Only 3 total (parent + 2 occurrences)
        'status' => EventStatus::Published,
    ]);

    $occurrences = $this->service->generateOccurrences($parent, 6);

    expect($occurrences)->toHaveCount(2); // recurrence_count = 3 means 2 more after parent
});

test('respects recurrence end date', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addWeek(),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'recurrence_ends_at' => now()->addWeeks(3),
        'status' => EventStatus::Published,
    ]);

    $occurrences = $this->service->generateOccurrences($parent, 6);

    foreach ($occurrences as $occurrence) {
        expect($occurrence->starts_at->toDateString())
            ->toBeLessThanOrEqual($parent->recurrence_ends_at->toDateString());
    }
});

test('does not generate duplicate occurrences', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addWeek(),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'status' => EventStatus::Published,
    ]);

    $firstRun = $this->service->generateOccurrences($parent, 1);
    $secondRun = $this->service->generateOccurrences($parent, 1);

    expect($secondRun)->toHaveCount(0); // No new occurrences on second run
});

// ============================================
// HELPER METHOD TESTS
// ============================================

test('isRecurring returns true for parent events with pattern', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'recurrence_pattern' => RecurrencePattern::Weekly,
    ]);

    expect($parent->isRecurring())->toBeTrue();
});

test('isRecurring returns false for occurrence events', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'recurrence_pattern' => RecurrencePattern::Weekly,
    ]);

    $occurrence = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'parent_event_id' => $parent->id,
        'occurrence_index' => 1,
    ]);

    expect($occurrence->isRecurring())->toBeFalse();
    expect($occurrence->isOccurrence())->toBeTrue();
});

test('getSeriesParent returns parent for occurrence', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'recurrence_pattern' => RecurrencePattern::Weekly,
    ]);

    $occurrence = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'parent_event_id' => $parent->id,
        'occurrence_index' => 1,
    ]);

    expect($occurrence->getSeriesParent()->id)->toBe($parent->id);
});

// ============================================
// UPDATE FUTURE OCCURRENCES TESTS
// ============================================

test('updates future occurrences when parent is modified', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Name',
        'starts_at' => now()->addWeek(),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'status' => EventStatus::Published,
    ]);

    $this->service->generateOccurrences($parent, 2);

    $parent->update(['name' => 'Updated Name']);
    $updated = $this->service->updateFutureOccurrences($parent);

    expect($updated)->toBeGreaterThan(0);

    $parent->refresh();
    foreach ($parent->occurrences as $occurrence) {
        expect($occurrence->name)->toBe('Updated Name');
    }
});

// ============================================
// CANCELLATION TESTS
// ============================================

test('cancels future occurrences', function (): void {
    $parent = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'starts_at' => now()->addWeek(),
        'recurrence_pattern' => RecurrencePattern::Weekly,
        'status' => EventStatus::Published,
    ]);

    $this->service->generateOccurrences($parent, 2);
    $cancelled = $this->service->cancelFutureOccurrences($parent);

    expect($cancelled)->toBeGreaterThan(0);

    foreach ($parent->occurrences()->get() as $occurrence) {
        expect($occurrence->status)->toBe(EventStatus::Cancelled);
    }
});
