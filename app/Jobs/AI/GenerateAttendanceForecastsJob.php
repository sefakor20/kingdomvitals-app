<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Services\AI\AttendanceForecastService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateAttendanceForecastsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $branchId,
        public int $weeksAhead = 4
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AttendanceForecastService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('GenerateAttendanceForecastsJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('GenerateAttendanceForecastsJob: Starting', [
            'branch_id' => $this->branchId,
            'weeks_ahead' => $this->weeksAhead,
        ]);

        try {
            $forecastsGenerated = $service->updateForecastsForBranch(
                $this->branchId,
                $this->weeksAhead
            );

            Log::info('GenerateAttendanceForecastsJob: Completed', [
                'branch_id' => $this->branchId,
                'forecasts_generated' => $forecastsGenerated,
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateAttendanceForecastsJob: Failed', [
                'branch_id' => $this->branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAttendanceForecastsJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
