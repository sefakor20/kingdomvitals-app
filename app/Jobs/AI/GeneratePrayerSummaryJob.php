<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Branch;
use App\Models\Tenant\PrayerSummary;
use App\Services\AI\PrayerAnalysisService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeneratePrayerSummaryJob implements ShouldQueue
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
        public string $periodType,
        public string $periodStart,
        public string $periodEnd,
        public bool $overwrite = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PrayerAnalysisService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('GeneratePrayerSummaryJob: Feature disabled, skipping', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('GeneratePrayerSummaryJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        $periodStart = Carbon::parse($this->periodStart);
        $periodEnd = Carbon::parse($this->periodEnd);

        Log::info('GeneratePrayerSummaryJob: Starting', [
            'branch_id' => $this->branchId,
            'period_type' => $this->periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ]);

        try {
            // Check for existing summary
            $existing = PrayerSummary::where('branch_id', $this->branchId)
                ->where('period_type', $this->periodType)
                ->where('period_start', $periodStart->toDateString())
                ->first();

            if ($existing && ! $this->overwrite) {
                Log::info('GeneratePrayerSummaryJob: Summary already exists, skipping', [
                    'branch_id' => $this->branchId,
                    'period_type' => $this->periodType,
                    'period_start' => $periodStart->toDateString(),
                ]);

                return;
            }

            // Generate summary
            $summaryData = $service->generateSummary(
                $branch,
                $this->periodType,
                $periodStart,
                $periodEnd
            );

            // Store or update
            PrayerSummary::updateOrCreate(
                [
                    'branch_id' => $this->branchId,
                    'period_type' => $this->periodType,
                    'period_start' => $periodStart->toDateString(),
                ],
                [
                    'period_end' => $periodEnd->toDateString(),
                    'category_breakdown' => $summaryData->categoryBreakdown,
                    'urgency_breakdown' => $summaryData->urgencyBreakdown,
                    'summary_text' => $summaryData->summaryText,
                    'key_themes' => $summaryData->keyThemes,
                    'pastoral_recommendations' => $summaryData->pastoralRecommendations,
                    'total_requests' => $summaryData->totalRequests,
                    'answered_requests' => $summaryData->answeredRequests,
                    'critical_requests' => $summaryData->criticalRequests,
                ]
            );

            Log::info('GeneratePrayerSummaryJob: Completed', [
                'branch_id' => $this->branchId,
                'period_type' => $this->periodType,
                'total_requests' => $summaryData->totalRequests,
                'provider' => $summaryData->provider,
            ]);
        } catch (\Throwable $e) {
            Log::error('GeneratePrayerSummaryJob: Failed', [
                'branch_id' => $this->branchId,
                'period_type' => $this->periodType,
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
        Log::error('GeneratePrayerSummaryJob failed', [
            'branch_id' => $this->branchId,
            'period_type' => $this->periodType,
            'exception' => $exception->getMessage(),
        ]);
    }
}
