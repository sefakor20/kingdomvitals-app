<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Event;
use App\Services\AI\EventPredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PredictEventAttendanceJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $eventId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EventPredictionService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('PredictEventAttendanceJob: Feature disabled, skipping', [
                'event_id' => $this->eventId,
            ]);

            return;
        }

        $event = Event::find($this->eventId);

        if (! $event) {
            Log::warning('PredictEventAttendanceJob: Event not found', [
                'event_id' => $this->eventId,
            ]);

            return;
        }

        // Skip if event has already started
        if ($event->starts_at <= now()) {
            Log::info('PredictEventAttendanceJob: Event already started, skipping', [
                'event_id' => $this->eventId,
                'starts_at' => $event->starts_at,
            ]);

            return;
        }

        Log::info('PredictEventAttendanceJob: Starting predictions', [
            'event_id' => $this->eventId,
            'event_name' => $event->name,
            'branch_id' => $event->branch_id,
        ]);

        try {
            $predictions = $service->predictForEvent($event);
            $saved = $service->savePredictions($predictions, $event);

            $tierCounts = $predictions->groupBy(fn ($p) => $p->tier->value)
                ->map(fn ($group) => $group->count())
                ->toArray();

            Log::info('PredictEventAttendanceJob: Completed', [
                'event_id' => $this->eventId,
                'total_predictions' => $predictions->count(),
                'saved' => $saved,
                'tier_breakdown' => $tierCounts,
            ]);
        } catch (\Throwable $e) {
            Log::error('PredictEventAttendanceJob: Failed', [
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PredictEventAttendanceJob failed', [
            'event_id' => $this->eventId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
