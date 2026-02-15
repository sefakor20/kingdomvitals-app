<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Member;
use App\Services\AI\DonorChurnService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalculateDonorChurnScoresJob implements ShouldQueue
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
        public int $chunkSize = 50
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DonorChurnService $service): void
    {
        Log::info('CalculateDonorChurnScoresJob: Starting', [
            'branch_id' => $this->branchId,
            'chunk_size' => $this->chunkSize,
        ]);

        $processed = 0;
        $errors = 0;

        // Get members who have made at least one donation
        Member::query()
            ->where('primary_branch_id', $this->branchId)
            ->whereHas('donations')
            ->chunkById($this->chunkSize, function ($members) use ($service, &$processed, &$errors): void {
                foreach ($members as $member) {
                    try {
                        $assessment = $service->calculateScore($member);

                        $member->update([
                            'churn_risk_score' => $assessment->score,
                            'churn_risk_factors' => $assessment->factors,
                            'churn_risk_calculated_at' => now(),
                        ]);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('CalculateDonorChurnScoresJob: Failed to calculate score', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                }
            });

        Log::info('CalculateDonorChurnScoresJob: Completed', [
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
        Log::error('CalculateDonorChurnScoresJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
