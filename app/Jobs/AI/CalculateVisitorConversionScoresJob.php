<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Visitor;
use App\Services\AI\VisitorConversionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalculateVisitorConversionScoresJob implements ShouldQueue
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
    public function handle(VisitorConversionService $service): void
    {
        Log::info('CalculateVisitorConversionScoresJob: Starting', [
            'branch_id' => $this->branchId,
            'chunk_size' => $this->chunkSize,
        ]);

        $processed = 0;
        $errors = 0;

        Visitor::query()
            ->where('branch_id', $this->branchId)
            ->where('is_converted', false)
            ->chunkById($this->chunkSize, function ($visitors) use ($service, &$processed, &$errors): void {
                foreach ($visitors as $visitor) {
                    try {
                        $prediction = $service->calculateScore($visitor);

                        $visitor->update([
                            'conversion_score' => $prediction->score,
                            'conversion_factors' => $prediction->factors,
                            'conversion_score_calculated_at' => now(),
                        ]);

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('CalculateVisitorConversionScoresJob: Failed to calculate score', [
                            'visitor_id' => $visitor->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                }
            });

        Log::info('CalculateVisitorConversionScoresJob: Completed', [
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
        Log::error('CalculateVisitorConversionScoresJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
