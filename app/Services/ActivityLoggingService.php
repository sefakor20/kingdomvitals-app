<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\SubjectType;
use App\Models\Tenant\ActivityLog;
use BackedEnum;
use DateTimeInterface;

class ActivityLoggingService
{
    /**
     * Log a model CRUD event.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string>|null  $changedFields
     */
    public function logModelEvent(
        string $branchId,
        ActivityEvent $event,
        SubjectType $subjectType,
        string $subjectId,
        string $subjectName,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $changedFields = null
    ): ActivityLog {
        return ActivityLog::log(
            branchId: $branchId,
            event: $event,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectName: $subjectName,
            oldValues: $this->serializeValues($oldValues),
            newValues: $this->serializeValues($newValues),
            changedFields: $changedFields,
        );
    }

    /**
     * Log an authentication event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logAuthEvent(
        string $branchId,
        ActivityEvent $event,
        string $userId,
        string $userName,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::log(
            branchId: $branchId,
            event: $event,
            subjectType: SubjectType::User,
            subjectId: $userId,
            subjectName: $userName,
            metadata: $metadata,
        );
    }

    /**
     * Log a bulk operation event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logBulkEvent(
        string $branchId,
        ActivityEvent $event,
        SubjectType $subjectType,
        int $affectedCount,
        string $description,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::log(
            branchId: $branchId,
            event: $event,
            subjectType: $subjectType,
            subjectName: "{$affectedCount} {$subjectType->pluralLabel()}",
            description: $description,
            metadata: array_merge($metadata, ['affected_count' => $affectedCount]),
        );
    }

    /**
     * Log an export event.
     *
     * @param  array<string, mixed>  $filters
     */
    public function logExportEvent(
        string $branchId,
        SubjectType $subjectType,
        int $recordCount,
        string $format = 'csv',
        array $filters = []
    ): ActivityLog {
        return ActivityLog::log(
            branchId: $branchId,
            event: ActivityEvent::Exported,
            subjectType: $subjectType,
            subjectName: "{$recordCount} {$subjectType->pluralLabel()}",
            description: "Exported {$recordCount} records to {$format}",
            metadata: [
                'record_count' => $recordCount,
                'format' => $format,
                'filters' => $filters,
            ],
        );
    }

    /**
     * Log an import event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logImportEvent(
        string $branchId,
        SubjectType $subjectType,
        int $recordCount,
        string $source,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::log(
            branchId: $branchId,
            event: ActivityEvent::Imported,
            subjectType: $subjectType,
            subjectName: "{$recordCount} {$subjectType->pluralLabel()}",
            description: "Imported {$recordCount} records from {$source}",
            metadata: array_merge($metadata, [
                'record_count' => $recordCount,
                'source' => $source,
            ]),
        );
    }

    /**
     * Serialize values for storage.
     *
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public function serializeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)->map(function ($value) {
            if ($value instanceof BackedEnum) {
                return $value->value;
            }
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            return $value;
        })->toArray();
    }
}
