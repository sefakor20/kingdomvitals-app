<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Services\AI\DutyRosterOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeDutyRosterPoolScoresJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $branchId,
    ) {}

    public function handle(DutyRosterOptimizationService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('Duty roster optimization is disabled, skipping score calculation');

            return;
        }

        Log::info("Optimizing duty roster pool scores for branch {$this->branchId}");

        $updated = $service->updateAllPoolScores($this->branchId);

        Log::info("Updated {$updated} pool member scores for branch {$this->branchId}");
    }

    public function tags(): array
    {
        return ['ai', 'roster-optimization', "branch:{$this->branchId}"];
    }
}
