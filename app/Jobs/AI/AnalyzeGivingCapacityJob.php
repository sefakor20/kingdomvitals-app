<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Branch;
use App\Services\AI\GivingCapacityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeGivingCapacityJob implements ShouldQueue
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
        public string $branchId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GivingCapacityService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('AnalyzeGivingCapacityJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('AnalyzeGivingCapacityJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('AnalyzeGivingCapacityJob: Starting capacity analysis', [
            'branch_id' => $this->branchId,
            'branch_name' => $branch->name,
        ]);

        try {
            $assessments = $service->assessForBranch($branch);
            $saved = $service->saveAssessments($assessments);

            $levelCounts = $assessments->groupBy(fn ($a) => $a->capacityLevel())
                ->map(fn ($group) => $group->count())
                ->toArray();

            Log::info('AnalyzeGivingCapacityJob: Completed', [
                'branch_id' => $this->branchId,
                'total_assessments' => $assessments->count(),
                'saved' => $saved,
                'level_breakdown' => $levelCounts,
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzeGivingCapacityJob: Failed', [
                'branch_id' => $this->branchId,
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
        Log::error('AnalyzeGivingCapacityJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
