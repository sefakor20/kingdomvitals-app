<?php

namespace App\Console\Commands;

use App\Enums\BranchRole;
use App\Enums\BudgetStatus;
use App\Models\Tenant;
use App\Models\Tenant\Budget;
use App\Models\User;
use App\Notifications\BudgetThresholdNotification;
use Illuminate\Console\Command;

class CheckBudgetThresholdsCommand extends Command
{
    protected $signature = 'budgets:check-thresholds';

    protected $description = 'Check budget thresholds and send alerts to admins and managers';

    public function handle(): int
    {
        $this->info('Checking budget thresholds...');

        $totalAlerts = 0;

        Tenant::all()->each(function (Tenant $tenant) use (&$totalAlerts) {
            tenancy()->initialize($tenant);

            Budget::where('alerts_enabled', true)
                ->where('status', BudgetStatus::Active)
                ->each(function (Budget $budget) use (&$totalAlerts) {
                    $alertSent = $this->checkAndSendAlerts($budget);
                    if ($alertSent) {
                        $totalAlerts++;
                    }
                });

            tenancy()->end();
        });

        $this->info("Done! Sent {$totalAlerts} alert(s).");

        return Command::SUCCESS;
    }

    private function checkAndSendAlerts(Budget $budget): bool
    {
        $utilization = $budget->utilization_percentage;

        if ($utilization >= 100 && $this->shouldSendAlert($budget, 'exceeded')) {
            $this->sendAlert($budget, 'exceeded', $utilization);

            return true;
        }

        if ($utilization >= $budget->alert_threshold_critical && $this->shouldSendAlert($budget, 'critical')) {
            $this->sendAlert($budget, 'critical', $utilization);

            return true;
        }

        if ($utilization >= $budget->alert_threshold_warning && $this->shouldSendAlert($budget, 'warning')) {
            $this->sendAlert($budget, 'warning', $utilization);

            return true;
        }

        return false;
    }

    private function shouldSendAlert(Budget $budget, string $level): bool
    {
        $field = "last_{$level}_sent_at";
        $lastSent = $budget->$field;

        if ($lastSent === null) {
            return true;
        }

        $threshold = match ($level) {
            'exceeded' => now()->subDay(),
            'critical' => now()->subDay(),
            'warning' => now()->subWeek(),
            default => now()->subDay(),
        };

        return $lastSent < $threshold;
    }

    private function sendAlert(Budget $budget, string $level, float $utilization): void
    {
        $recipients = User::whereHas('branchAccess', function ($q) use ($budget) {
            $q->where('branch_id', $budget->branch_id)
                ->whereIn('role', [
                    BranchRole::Admin->value,
                    BranchRole::Manager->value,
                ]);
        })->get();

        foreach ($recipients as $user) {
            $user->notify(new BudgetThresholdNotification($budget, $level, $utilization));
            $this->line("  - Sent {$level} alert for \"{$budget->name}\" to {$user->email}");
        }

        $budget->update(["last_{$level}_sent_at" => now()]);
    }
}
