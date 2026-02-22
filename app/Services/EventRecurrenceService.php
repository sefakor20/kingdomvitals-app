<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\RecurrencePattern;
use App\Models\Tenant\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EventRecurrenceService
{
    /**
     * Generate occurrence events from a parent event template.
     *
     * @param  int  $months  Number of months ahead to generate
     * @return Collection<int, Event> The created occurrence events
     */
    public function generateOccurrences(Event $parent, int $months = 3): Collection
    {
        if (! $parent->recurrence_pattern) {
            return collect();
        }

        $pattern = $parent->recurrence_pattern;
        $startDate = $parent->starts_at;
        $endDate = $this->calculateEndDate($parent, $months);

        // Get existing occurrence indices to avoid duplicates
        $existingIndices = $parent->occurrences()
            ->pluck('occurrence_index')
            ->toArray();

        $occurrences = collect();
        $currentDate = $this->getNextOccurrenceDate($pattern, $startDate, $startDate);
        $index = 1; // Start from 1 since parent is index 0

        while ($currentDate <= $endDate) {
            // Check if we've reached the occurrence count limit
            if ($parent->recurrence_count && $index >= $parent->recurrence_count) {
                break;
            }

            // Skip if this occurrence already exists
            if (! in_array($index, $existingIndices)) {
                $occurrence = $this->createOccurrence($parent, $currentDate, $index);
                $occurrences->push($occurrence);
            }

            $currentDate = $this->getNextOccurrenceDate($pattern, $startDate, $currentDate);
            $index++;
        }

        return $occurrences;
    }

    /**
     * Update all future occurrences when parent event is modified.
     *
     * @param  array<string, mixed>  $fieldsToSync
     */
    public function updateFutureOccurrences(Event $parent, array $fieldsToSync = []): int
    {
        if ($fieldsToSync === []) {
            // Default fields to sync from parent
            $fieldsToSync = [
                'name',
                'description',
                'event_type',
                'category',
                'location',
                'address',
                'city',
                'country',
                'capacity',
                'allow_registration',
                'is_paid',
                'price',
                'currency',
                'requires_ticket',
                'visibility',
                'is_public',
                'notes',
            ];
        }

        $updateData = [];
        foreach ($fieldsToSync as $field) {
            $updateData[$field] = $parent->$field;
        }

        // Only update future occurrences (not past ones)
        return $parent->occurrences()
            ->where('starts_at', '>', now())
            ->whereNotIn('status', [EventStatus::Cancelled, EventStatus::Completed])
            ->update($updateData);
    }

    /**
     * Calculate the next occurrence date based on pattern.
     */
    public function getNextOccurrenceDate(RecurrencePattern $pattern, Carbon $originalDate, Carbon $fromDate): Carbon
    {
        return match ($pattern) {
            RecurrencePattern::Weekly => $fromDate->copy()->addWeek(),
            RecurrencePattern::Biweekly => $fromDate->copy()->addWeeks(2),
            RecurrencePattern::Monthly => $this->addMonthSameDay($originalDate, $fromDate),
        };
    }

    /**
     * Delete occurrences that are beyond the recurrence end date.
     */
    public function deleteOrphanedOccurrences(Event $parent): int
    {
        $query = $parent->occurrences()
            ->where('starts_at', '>', now())
            ->whereNotIn('status', [EventStatus::Cancelled, EventStatus::Completed]);

        // If recurrence_ends_at is set, delete occurrences beyond that date
        if ($parent->recurrence_ends_at) {
            return $query->where('starts_at', '>', $parent->recurrence_ends_at)->delete();
        }

        // If recurrence_count is set, delete occurrences beyond that count
        if ($parent->recurrence_count) {
            $toDelete = $query->where('occurrence_index', '>=', $parent->recurrence_count)->get();

            return $toDelete->each->delete()->count();
        }

        return 0;
    }

    /**
     * Cancel all future occurrences of a recurring event.
     */
    public function cancelFutureOccurrences(Event $parent): int
    {
        return $parent->occurrences()
            ->where('starts_at', '>', now())
            ->whereNotIn('status', [EventStatus::Cancelled, EventStatus::Completed])
            ->update(['status' => EventStatus::Cancelled]);
    }

    /**
     * Create a single occurrence event from the parent template.
     */
    protected function createOccurrence(Event $parent, Carbon $startDate, int $index): Event
    {
        // Calculate end date based on duration of parent event
        $duration = $parent->ends_at
            ? $parent->starts_at->diffInMinutes($parent->ends_at)
            : null;

        $endDate = $duration ? $startDate->copy()->addMinutes($duration) : null;

        // Calculate registration window offsets
        $registrationOpenOffset = $parent->registration_opens_at
            ? $parent->starts_at->diffInMinutes($parent->registration_opens_at, false)
            : null;

        $registrationCloseOffset = $parent->registration_closes_at
            ? $parent->starts_at->diffInMinutes($parent->registration_closes_at, false)
            : null;

        return Event::create([
            'branch_id' => $parent->branch_id,
            'organizer_id' => $parent->organizer_id,
            'name' => $parent->name,
            'description' => $parent->description,
            'event_type' => $parent->event_type,
            'category' => $parent->category,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'location' => $parent->location,
            'address' => $parent->address,
            'city' => $parent->city,
            'country' => $parent->country,
            'capacity' => $parent->capacity,
            'allow_registration' => $parent->allow_registration,
            'registration_opens_at' => $registrationOpenOffset !== null
                ? $startDate->copy()->addMinutes($registrationOpenOffset)
                : null,
            'registration_closes_at' => $registrationCloseOffset !== null
                ? $startDate->copy()->addMinutes($registrationCloseOffset)
                : null,
            'is_paid' => $parent->is_paid,
            'price' => $parent->price,
            'currency' => $parent->currency,
            'requires_ticket' => $parent->requires_ticket,
            'status' => EventStatus::Published,
            'visibility' => $parent->visibility,
            'is_public' => $parent->is_public,
            'notes' => $parent->notes,
            'parent_event_id' => $parent->id,
            'occurrence_index' => $index,
        ]);
    }

    /**
     * Add months while trying to preserve the same day of month.
     */
    protected function addMonthSameDay(Carbon $originalDate, Carbon $fromDate): Carbon
    {
        $targetDay = $originalDate->day;
        $nextMonth = $fromDate->copy()->addMonth();

        // If the target day doesn't exist in the next month, use the last day
        $daysInMonth = $nextMonth->daysInMonth;
        $nextMonth->day = min($targetDay, $daysInMonth);

        return $nextMonth;
    }

    /**
     * Calculate the end date for generating occurrences.
     */
    protected function calculateEndDate(Event $parent, int $months): Carbon
    {
        $generationEnd = now()->addMonths($months);

        // If recurrence has an end date, use the earlier of the two
        if ($parent->recurrence_ends_at) {
            return $generationEnd->min($parent->recurrence_ends_at);
        }

        return $generationEnd;
    }
}
