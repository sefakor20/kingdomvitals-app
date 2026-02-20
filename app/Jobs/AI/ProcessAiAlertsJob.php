<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Enums\AiAlertType;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Branch;
use App\Notifications\AI\AiAlertNotification;
use App\Services\AI\AiAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessAiAlertsJob implements ShouldQueue
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
     *
     * @param  AiAlertType|null  $alertType  Specific alert type to process, or null for all
     * @param  bool  $sendNotifications  Whether to send notifications for created alerts
     */
    public function __construct(
        public string $branchId,
        public ?AiAlertType $alertType = null,
        public bool $sendNotifications = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiAlertService $service): void
    {
        Log::info('ProcessAiAlertsJob: Starting', [
            'branch_id' => $this->branchId,
            'alert_type' => $this->alertType?->value ?? 'all',
        ]);

        $branch = Branch::find($this->branchId);

        if (! $branch) {
            Log::warning('ProcessAiAlertsJob: Branch not found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        // Check if alerts feature is enabled
        if (! config('ai.features.alerts.enabled', true)) {
            Log::info('ProcessAiAlertsJob: Alerts feature disabled, skipping');

            return;
        }

        $alerts = $this->processAlerts($service, $branch);

        // Send notifications for created alerts
        if ($this->sendNotifications && $alerts->isNotEmpty()) {
            $this->sendAlertNotifications($branch, $alerts);
        }

        Log::info('ProcessAiAlertsJob: Completed', [
            'branch_id' => $this->branchId,
            'alerts_created' => $alerts->count(),
        ]);
    }

    /**
     * Process alerts based on the specified type or all types.
     *
     * @return Collection<int, AiAlert>
     */
    protected function processAlerts(AiAlertService $service, Branch $branch): Collection
    {
        if ($this->alertType instanceof \App\Enums\AiAlertType) {
            return match ($this->alertType) {
                AiAlertType::ChurnRisk => $service->checkChurnRiskAlerts($branch),
                AiAlertType::AttendanceAnomaly => $service->checkAttendanceAnomalyAlerts($branch),
                AiAlertType::LifecycleChange => $service->checkLifecycleTransitionAlerts($branch),
                AiAlertType::CriticalPrayer => $service->checkCriticalPrayerAlerts($branch),
                AiAlertType::ClusterHealth => $service->checkClusterHealthAlerts($branch),
                AiAlertType::HouseholdDisengagement => $service->checkHouseholdDisengagementAlerts($branch),
            };
        }

        return $service->processAllAlerts($branch);
    }

    /**
     * Send notifications for created alerts.
     *
     * @param  Collection<int, AiAlert>  $alerts
     */
    protected function sendAlertNotifications(Branch $branch, Collection $alerts): void
    {
        // Get branch admins/pastors to notify
        $notifiables = $branch->users()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['pastor', 'admin']))
            ->get();

        if ($notifiables->isEmpty()) {
            Log::info('ProcessAiAlertsJob: No notifiable users found', [
                'branch_id' => $this->branchId,
            ]);

            return;
        }

        // Send individual notifications for high priority alerts
        $highPriorityAlerts = $alerts->filter(fn (AiAlert $alert): bool => $alert->requiresImmediateAttention());

        foreach ($highPriorityAlerts as $alert) {
            try {
                Notification::send($notifiables, new AiAlertNotification($alert));
            } catch (\Throwable $e) {
                Log::warning('ProcessAiAlertsJob: Failed to send notification', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProcessAiAlertsJob: Notifications sent', [
            'branch_id' => $this->branchId,
            'high_priority_count' => $highPriorityAlerts->count(),
            'recipients' => $notifiables->count(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAiAlertsJob failed', [
            'branch_id' => $this->branchId,
            'alert_type' => $this->alertType?->value ?? 'all',
            'exception' => $exception->getMessage(),
        ]);
    }
}
