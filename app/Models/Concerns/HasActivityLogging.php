<?php

namespace App\Models\Concerns;

use App\Enums\SubjectType;
use App\Models\Tenant\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivityLogging
{
    /**
     * Get all activity logs for this model.
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject', 'subject_type', 'subject_id')
            ->latest();
    }

    /**
     * Get the subject type for activity logging.
     */
    abstract public function getActivitySubjectType(): SubjectType;

    /**
     * Get the display name for activity logging.
     */
    abstract public function getActivitySubjectName(): string;

    /**
     * Get the branch ID for activity logging.
     */
    abstract public function getActivityBranchId(): string;

    /**
     * Get fields to exclude from activity logging.
     *
     * @return array<string>
     */
    public function getActivityExcludedFields(): array
    {
        return ['updated_at', 'created_at', 'deleted_at'];
    }
}
