<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Branch;
use App\Services\AI\FinancialForecastService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFinancialForecastJob implements ShouldQueue
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
        public string $forecastType = 'monthly',
        public int $periodsAhead = 4
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FinancialForecastService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('GenerateFinancialForecastJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('GenerateFinancialForecastJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('GenerateFinancialForecastJob: Starting', [
            'branch_id' => $this->branchId,
            'forecast_type' => $this->forecastType,
            'periods_ahead' => $this->periodsAhead,
        ]);

        try {
            $updated = $service->updateForecastsForBranch(
                $this->branchId,
                $this->forecastType,
                $this->periodsAhead
            );

            Log::info('GenerateFinancialForecastJob: Completed', [
                'branch_id' => $this->branchId,
                'forecast_type' => $this->forecastType,
                'forecasts_updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateFinancialForecastJob: Failed', [
                'branch_id' => $this->branchId,
                'forecast_type' => $this->forecastType,
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
        Log::error('GenerateFinancialForecastJob failed', [
            'branch_id' => $this->branchId,
            'forecast_type' => $this->forecastType,
            'exception' => $exception->getMessage(),
        ]);
    }
}
