<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Concerns\HasActivityLogging;
use App\Services\ActivityLoggingService;
use Illuminate\Database\Eloquent\Model;

abstract class BaseAuditObserver
{
    public function __construct(
        protected ActivityLoggingService $loggingService
    ) {}

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        /** @var HasActivityLogging&Model $model */
        $attributes = $model->getAttributes();
        $excludedFields = $model->getActivityExcludedFields();

        $newValues = array_filter(
            array_diff_key($attributes, array_flip($excludedFields)),
            fn ($value): bool => $value !== null
        );

        $this->loggingService->logModelEvent(
            branchId: $model->getActivityBranchId(),
            event: ActivityEvent::Created,
            subjectType: $model->getActivitySubjectType(),
            subjectId: $model->getKey(),
            subjectName: $model->getActivitySubjectName(),
            newValues: $newValues,
        );
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        /** @var HasActivityLogging&Model $model */
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        $excludedFields = $model->getActivityExcludedFields();

        $changedFields = array_values(array_diff(array_keys($changes), $excludedFields));

        if ($changedFields === []) {
            return;
        }

        $oldValues = array_intersect_key($original, array_flip($changedFields));
        $newValues = array_intersect_key($changes, array_flip($changedFields));

        $this->loggingService->logModelEvent(
            branchId: $model->getActivityBranchId(),
            event: ActivityEvent::Updated,
            subjectType: $model->getActivitySubjectType(),
            subjectId: $model->getKey(),
            subjectName: $model->getActivitySubjectName(),
            oldValues: $oldValues,
            newValues: $newValues,
            changedFields: $changedFields,
        );
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        // Skip force deletes
        if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
            return;
        }

        /** @var HasActivityLogging&Model $model */
        $this->loggingService->logModelEvent(
            branchId: $model->getActivityBranchId(),
            event: ActivityEvent::Deleted,
            subjectType: $model->getActivitySubjectType(),
            subjectId: $model->getKey(),
            subjectName: $model->getActivitySubjectName(),
        );
    }

    /**
     * Handle the "restored" event.
     */
    public function restored(Model $model): void
    {
        if (! $this->shouldLog($model)) {
            return;
        }

        /** @var HasActivityLogging&Model $model */
        $this->loggingService->logModelEvent(
            branchId: $model->getActivityBranchId(),
            event: ActivityEvent::Restored,
            subjectType: $model->getActivitySubjectType(),
            subjectId: $model->getKey(),
            subjectName: $model->getActivitySubjectName(),
        );
    }

    /**
     * Determine if the model should be logged.
     */
    protected function shouldLog(Model $model): bool
    {
        return in_array(HasActivityLogging::class, class_uses_recursive($model), true);
    }
}
