<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class MemberObserver
{
    /**
     * Fields to exclude from logging.
     *
     * @var array<string>
     */
    protected array $excludedFields = [
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        $attributes = $member->getAttributes();

        // Filter out excluded fields and null values
        $newValues = array_filter(
            array_diff_key($attributes, array_flip($this->excludedFields)),
            fn ($value) => $value !== null
        );

        $this->logActivity($member, ActivityEvent::Created, null, $newValues);
    }

    /**
     * Handle the Member "updated" event.
     */
    public function updated(Member $member): void
    {
        $changes = $member->getChanges();
        $original = $member->getOriginal();

        // Filter out excluded fields
        $changedFields = array_diff(array_keys($changes), $this->excludedFields);

        if (empty($changedFields)) {
            return; // No meaningful changes to log
        }

        $oldValues = array_intersect_key($original, array_flip($changedFields));
        $newValues = array_intersect_key($changes, array_flip($changedFields));

        $this->logActivity(
            $member,
            ActivityEvent::Updated,
            $this->serializeValues($oldValues),
            $this->serializeValues($newValues),
            array_values($changedFields)
        );
    }

    /**
     * Handle the Member "deleted" event.
     */
    public function deleted(Member $member): void
    {
        // Only log soft deletes, not force deletes
        if ($member->isForceDeleting()) {
            return;
        }

        $this->logActivity($member, ActivityEvent::Deleted);
    }

    /**
     * Handle the Member "restored" event.
     */
    public function restored(Member $member): void
    {
        $this->logActivity($member, ActivityEvent::Restored);
    }

    /**
     * Create an activity log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string>|null  $changedFields
     */
    protected function logActivity(
        Member $member,
        ActivityEvent $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $changedFields = null
    ): void {
        MemberActivity::create([
            'member_id' => $member->id,
            'user_id' => Auth::id(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Serialize values for storage, handling enums and dates.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function serializeValues(array $values): array
    {
        return collect($values)->map(function ($value) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return $value;
        })->toArray();
    }
}
