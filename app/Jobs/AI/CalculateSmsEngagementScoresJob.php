<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Services\AI\SmsCampaignOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateSmsEngagementScoresJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public string $branchId,
    ) {}

    public function handle(SmsCampaignOptimizationService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('SMS campaign optimization is disabled, skipping engagement calculation');

            return;
        }

        Log::info("Calculating SMS engagement scores for branch {$this->branchId}");

        $updated = $service->batchUpdateEngagementScores($this->branchId);

        Log::info("Updated {$updated} member engagement scores for branch {$this->branchId}");
    }

    public function tags(): array
    {
        return ['ai', 'sms-optimization', "branch:{$this->branchId}"];
    }
}
