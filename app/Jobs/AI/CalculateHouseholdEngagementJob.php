<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Household;
use App\Services\AI\HouseholdEngagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalculateHouseholdEngagementJob implements ShouldQueue
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
        public string $branchId,
        public int $chunkSize = 50
    ) {}

    /**
     * Execute the job.
     */
    public function handle(HouseholdEngagementService $service): void
    {
        Log::info('CalculateHouseholdEngagementJob: Starting', [
            'branch_id' => $this->branchId,
            'chunk_size' => $this->chunkSize,
        ]);

        $processed = 0;
        $errors = 0;

        Household::query()
            ->where('branch_id', $this->branchId)
            ->chunkById($this->chunkSize, function ($households) use ($service, &$processed, &$errors): void {
                foreach ($households as $household) {
                    try {
                        $assessment = $service->calculateEngagement($household);

                        $household->update([
                            'engagement_score' => $assessment->engagementScore,
                            'engagement_level' => $assessment->level->value,
                            'attendance_score' => $assessment->attendanceScore,
                            'giving_score' => $assessment->givingScore,
                            'member_engagement_variance' => $assessment->memberVariance,
                            'engagement_factors' => $assessment->factors,
                            'engagement_calculated_at' => now(),
                        ]);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('CalculateHouseholdEngagementJob: Failed to calculate engagement', [
                            'household_id' => $household->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                }
            });

        Log::info('CalculateHouseholdEngagementJob: Completed', [
            'branch_id' => $this->branchId,
            'processed' => $processed,
            'errors' => $errors,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateHouseholdEngagementJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
