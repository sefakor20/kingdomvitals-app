<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\PrayerRequest;
use App\Services\AI\PrayerAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzePrayerRequestsJob implements ShouldQueue
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
        public bool $reanalyze = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PrayerAnalysisService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('AnalyzePrayerRequestsJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        Log::info('AnalyzePrayerRequestsJob: Starting', [
            'branch_id' => $this->branchId,
            'reanalyze' => $this->reanalyze,
        ]);

        try {
            $query = PrayerRequest::where('branch_id', $this->branchId)
                ->open();

            if (! $this->reanalyze) {
                $query->needsAnalysis();
            }

            $prayers = $query->get();
            $analyzed = 0;
            $critical = 0;

            foreach ($prayers as $prayer) {
                $analysis = $service->analyze($prayer);
                $service->updatePrayerWithAnalysis($prayer, $analysis);
                $analyzed++;

                if ($analysis->shouldEscalate()) {
                    $critical++;
                    // Could dispatch notification job here
                    Log::warning('AnalyzePrayerRequestsJob: Critical prayer detected', [
                        'prayer_id' => $prayer->id,
                        'branch_id' => $this->branchId,
                        'urgency' => $analysis->urgencyLevel->value,
                    ]);
                }
            }

            Log::info('AnalyzePrayerRequestsJob: Completed', [
                'branch_id' => $this->branchId,
                'analyzed' => $analyzed,
                'critical' => $critical,
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzePrayerRequestsJob: Failed', [
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
        Log::error('AnalyzePrayerRequestsJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
