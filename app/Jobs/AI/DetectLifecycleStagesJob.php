<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\Tenant\Member;
use App\Notifications\MemberLifecycleTransitionNotification;
use App\Services\AI\MemberLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DetectLifecycleStagesJob implements ShouldQueue
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
        public bool $notifyOnTransition = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MemberLifecycleService $service): void
    {
        Log::info('DetectLifecycleStagesJob: Starting', [
            'branch_id' => $this->branchId,
            'chunk_size' => $this->chunkSize,
        ]);

        $processed = 0;
        $transitions = 0;
        $concerningTransitions = [];
        $errors = 0;

        Member::query()
            ->where('primary_branch_id', $this->branchId)
            ->chunkById($this->chunkSize, function ($members) use ($service, &$processed, &$transitions, &$concerningTransitions, &$errors) {
                foreach ($members as $member) {
                    try {
                        $assessment = $service->detectStage($member);

                        // Check if this is a transition
                        $isTransition = $assessment->isTransition();
                        $previousStage = $member->lifecycle_stage;

                        $member->update([
                            'lifecycle_stage' => $assessment->stage->value,
                            'lifecycle_stage_factors' => $assessment->factors,
                            'lifecycle_stage_changed_at' => $isTransition ? now() : $member->lifecycle_stage_changed_at,
                        ]);

                        if ($isTransition) {
                            $transitions++;

                            if ($assessment->isConcerningTransition()) {
                                $concerningTransitions[] = [
                                    'member_id' => $member->id,
                                    'member_name' => $member->fullName(),
                                    'from_stage' => $previousStage?->value,
                                    'to_stage' => $assessment->stage->value,
                                ];
                            }
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        Log::warning('DetectLifecycleStagesJob: Failed to detect stage', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                }
            });

        // Send notifications for concerning transitions
        if ($this->notifyOnTransition && ! empty($concerningTransitions)) {
            $this->sendTransitionNotifications($concerningTransitions);
        }

        Log::info('DetectLifecycleStagesJob: Completed', [
            'branch_id' => $this->branchId,
            'processed' => $processed,
            'transitions' => $transitions,
            'concerning_transitions' => count($concerningTransitions),
            'errors' => $errors,
        ]);
    }

    /**
     * Send notifications for concerning transitions.
     */
    protected function sendTransitionNotifications(array $transitions): void
    {
        if (! config('ai.features.lifecycle_detection.notify_on_at_risk', true)) {
            return;
        }

        try {
            // Get branch admins/pastors to notify
            $branch = \App\Models\Tenant\Branch::find($this->branchId);
            if (! $branch) {
                return;
            }

            $notifiables = $branch->users()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['pastor', 'admin']))
                ->get();

            if ($notifiables->isNotEmpty()) {
                Notification::send(
                    $notifiables,
                    new MemberLifecycleTransitionNotification($transitions, $this->branchId)
                );
            }
        } catch (\Throwable $e) {
            Log::warning('DetectLifecycleStagesJob: Failed to send notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DetectLifecycleStagesJob failed', [
            'branch_id' => $this->branchId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
