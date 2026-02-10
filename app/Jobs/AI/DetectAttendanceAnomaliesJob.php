<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Services\AI\AttendanceAnomalyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DetectAttendanceAnomaliesJob implements ShouldQueue
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
        public string $branchId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AttendanceAnomalyService $service): void
    {
        Log::info('DetectAttendanceAnomaliesJob: Starting', [
            'branch_id' => $this->branchId,
        ]);

        try {
            $updated = $service->updateMemberAnomalyScores($this->branchId);

            Log::info('DetectAttendanceAnomaliesJob: Completed', [
                'branch_id' => $this->branchId,
                'members_flagged' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('DetectAttendanceAnomaliesJob: Failed', [
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
        Log::error('DetectAttendanceAnomaliesJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
