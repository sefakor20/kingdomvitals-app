<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Enums\BranchRole;
use App\Enums\ClusterHealthLevel;
use App\Models\Tenant\Cluster;
use App\Notifications\ClusterHealthAlertNotification;
use App\Services\AI\ClusterHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CalculateClusterHealthJob implements ShouldQueue
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
        public int $chunkSize = 50,
        public bool $notifyOnStruggling = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ClusterHealthService $service): void
    {
        Log::info('CalculateClusterHealthJob: Starting', [
            'branch_id' => $this->branchId,
            'chunk_size' => $this->chunkSize,
        ]);

        $processed = 0;
        $errors = 0;
        $strugglingClusters = [];

        Cluster::query()
            ->where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->chunkById($this->chunkSize, function ($clusters) use ($service, &$processed, &$errors, &$strugglingClusters): void {
                foreach ($clusters as $cluster) {
                    try {
                        $assessment = $service->calculateHealth($cluster);

                        $previousLevel = $cluster->health_level;

                        $cluster->update([
                            'health_score' => $assessment->overallScore,
                            'health_level' => $assessment->level->value,
                            'health_factors' => $assessment->factors,
                            'health_calculated_at' => now(),
                        ]);

                        // Track clusters that became struggling/critical
                        if ($assessment->needsAttention()) {
                            $wasOkay = ! in_array($previousLevel, [
                                ClusterHealthLevel::Struggling->value,
                                ClusterHealthLevel::Critical->value,
                            ]);

                            if ($wasOkay || $assessment->level === ClusterHealthLevel::Critical) {
                                $strugglingClusters[] = [
                                    'cluster_id' => $cluster->id,
                                    'cluster_name' => $cluster->name,
                                    'health_level' => $assessment->level->value,
                                    'health_score' => $assessment->overallScore,
                                    'top_concerns' => $assessment->getTopConcerns(),
                                    'primary_recommendation' => $assessment->primaryRecommendation(),
                                ];
                            }
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('CalculateClusterHealthJob: Failed to calculate health', [
                            'cluster_id' => $cluster->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                }
            });

        // Send notifications for struggling clusters
        if ($this->notifyOnStruggling && $strugglingClusters !== []) {
            $this->sendHealthAlerts($strugglingClusters);
        }

        Log::info('CalculateClusterHealthJob: Completed', [
            'branch_id' => $this->branchId,
            'processed' => $processed,
            'struggling_clusters' => count($strugglingClusters),
            'errors' => $errors,
        ]);
    }

    /**
     * Send notifications for struggling clusters.
     */
    protected function sendHealthAlerts(array $clusters): void
    {
        if (! config('ai.features.cluster_health.notify_on_struggling', true)) {
            return;
        }

        try {
            // Get branch admins/managers to notify
            $branch = \App\Models\Tenant\Branch::find($this->branchId);
            if (! $branch) {
                return;
            }

            $notifiables = $branch->userAccess()
                ->whereIn('role', [BranchRole::Admin, BranchRole::Manager])
                ->with('user')
                ->get()
                ->pluck('user');

            if ($notifiables->isNotEmpty()) {
                Notification::send(
                    $notifiables,
                    new ClusterHealthAlertNotification($clusters, $this->branchId)
                );
            }
        } catch (\Throwable $e) {
            Log::warning('CalculateClusterHealthJob: Failed to send notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateClusterHealthJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
