<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Services\AI\EmailEngagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateEmailEngagementScoresJob implements ShouldQueue
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

    public function handle(EmailEngagementService $service): void
    {
        if (! $service->isEnabled()) {
            Log::info('Email engagement optimization is disabled, skipping engagement calculation');

            return;
        }

        Log::info("Calculating email engagement scores for branch {$this->branchId}");

        $updated = $service->batchUpdateEngagementScores($this->branchId);

        Log::info("Updated {$updated} member email engagement scores for branch {$this->branchId}");
    }

    public function tags(): array
    {
        return ['ai', 'email-optimization', "branch:{$this->branchId}"];
    }
}
