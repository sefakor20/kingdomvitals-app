<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Branch;
use App\Services\AI\GivingTrendService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeGivingTrendsJob implements ShouldQueue
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
        public int $historyMonths = 12
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GivingTrendService $service): void
    {
        if (! $this->isEnabled()) {
            Log::info('AnalyzeGivingTrendsJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('AnalyzeGivingTrendsJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('AnalyzeGivingTrendsJob: Starting', [
            'branch_id' => $this->branchId,
            'history_months' => $this->historyMonths,
        ]);

        try {
            $processed = $service->processBranch($branch, $this->historyMonths);

            Log::info('AnalyzeGivingTrendsJob: Completed', [
                'branch_id' => $this->branchId,
                'members_processed' => $processed,
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzeGivingTrendsJob: Failed', [
                'branch_id' => $this->branchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if the feature is enabled.
     */
    protected function isEnabled(): bool
    {
        return config('ai.features.giving_trends.enabled', true);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeGivingTrendsJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
