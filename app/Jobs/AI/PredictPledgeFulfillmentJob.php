<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Branch;
use App\Services\AI\PledgePredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PredictPledgeFulfillmentJob implements ShouldQueue
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
    public function handle(PledgePredictionService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('PredictPledgeFulfillmentJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('PredictPledgeFulfillmentJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('PredictPledgeFulfillmentJob: Starting pledge predictions', [
            'branch_id' => $this->branchId,
            'branch_name' => $branch->name,
        ]);

        try {
            $predictions = $service->predictForBranch($branch);
            $saved = $service->savePredictions($predictions, $branch);

            $riskCounts = $predictions->groupBy(fn ($p) => $p->riskLevel->value)
                ->map(fn ($group) => $group->count())
                ->toArray();

            Log::info('PredictPledgeFulfillmentJob: Completed', [
                'branch_id' => $this->branchId,
                'total_predictions' => $predictions->count(),
                'saved' => $saved,
                'risk_breakdown' => $riskCounts,
            ]);
        } catch (\Throwable $e) {
            Log::error('PredictPledgeFulfillmentJob: Failed', [
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
        Log::error('PredictPledgeFulfillmentJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
