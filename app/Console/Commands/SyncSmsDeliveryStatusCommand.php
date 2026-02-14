<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SmsStatus;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Services\TextTangoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSmsDeliveryStatusCommand extends Command
{
    protected $signature = 'sms:sync-delivery-status
                            {--hours=2 : Check SMS sent more than X hours ago}
                            {--limit=100 : Maximum SMS to check per run}
                            {--tenant=* : Specific tenant ID(s) to process}
                            {--dry-run : Show what would be checked without making API calls}';

    protected $description = 'Sync SMS delivery status from TextTango for messages stuck in Sent status';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');
        $tenantIds = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No API calls will be made');
        }

        $this->info("Checking SMS sent more than {$hours} hour(s) ago...");

        $tenants = ! empty($tenantIds)
            ? Tenant::whereIn('id', $tenantIds)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $totalChecked = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $result = $this->processTenant($tenant, $hours, $limit - $totalChecked, $dryRun);
                $totalChecked += $result['checked'];
                $totalUpdated += $result['updated'];
                $totalErrors += $result['errors'];

                if ($totalChecked >= $limit) {
                    $this->line("Reached limit of {$limit} SMS.");
                    break;
                }
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info("Done! Checked: {$totalChecked}, Updated: {$totalUpdated}, Errors: {$totalErrors}");

        return Command::SUCCESS;
    }

    /**
     * @return array{checked: int, updated: int, errors: int}
     */
    protected function processTenant(Tenant $tenant, int $hours, int $limit, bool $dryRun): array
    {
        $cutoffTime = now()->subHours($hours);

        // Find SMS stuck in "Sent" status
        $smsLogs = SmsLog::where('status', SmsStatus::Sent)
            ->where('sent_at', '<', $cutoffTime)
            ->whereNotNull('provider_message_id')
            ->limit($limit)
            ->get();

        if ($smsLogs->isEmpty()) {
            return ['checked' => 0, 'updated' => 0, 'errors' => 0];
        }

        $this->line("Processing tenant: {$tenant->name} ({$smsLogs->count()} SMS to check)");

        $checked = 0;
        $updated = 0;
        $errors = 0;

        // Group by branch to use correct SMS credentials
        $smsByBranch = $smsLogs->groupBy('branch_id');

        foreach ($smsByBranch as $branchId => $branchSmsLogs) {
            $branch = Branch::find($branchId);

            if (! $branch) {
                $this->warn("  Branch {$branchId} not found, skipping {$branchSmsLogs->count()} SMS");
                $errors += $branchSmsLogs->count();

                continue;
            }

            $service = TextTangoService::forBranch($branch);

            if (! $service->isConfigured()) {
                $this->warn("  Branch {$branch->name}: SMS not configured, skipping");

                continue;
            }

            foreach ($branchSmsLogs as $smsLog) {
                $checked++;

                if ($dryRun) {
                    $this->line("    Would check: {$smsLog->phone_number} (ID: {$smsLog->provider_message_id})");

                    continue;
                }

                $result = $this->checkAndUpdateStatus($smsLog, $service);

                if ($result === 'updated') {
                    $updated++;
                } elseif ($result === 'error') {
                    $errors++;
                }
            }
        }

        return ['checked' => $checked, 'updated' => $updated, 'errors' => $errors];
    }

    protected function checkAndUpdateStatus(SmsLog $smsLog, TextTangoService $service): string
    {
        $result = $service->trackSingleMessage($smsLog->provider_message_id);

        if (! $result['success']) {
            Log::warning('SMS delivery status check failed', [
                'sms_log_id' => $smsLog->id,
                'provider_message_id' => $smsLog->provider_message_id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return 'error';
        }

        $data = $result['data'] ?? [];
        $status = $data['status'] ?? null;

        if (! $status) {
            return 'unchanged';
        }

        $newStatus = $this->mapStatus($status);

        // Only update if status changed
        if ($newStatus === $smsLog->status) {
            return 'unchanged';
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === SmsStatus::Delivered) {
            $updateData['delivered_at'] = now();
            $this->line("    Updated: {$smsLog->phone_number} → Delivered");
        } elseif ($newStatus === SmsStatus::Failed) {
            $updateData['error_message'] = $data['error_message'] ?? $data['reason'] ?? 'Delivery failed';
            $this->line("    Updated: {$smsLog->phone_number} → Failed");
        }

        $smsLog->update($updateData);

        return 'updated';
    }

    protected function mapStatus(string $status): SmsStatus
    {
        return match (strtolower($status)) {
            'delivered', 'success' => SmsStatus::Delivered,
            'failed', 'rejected', 'expired', 'undeliverable' => SmsStatus::Failed,
            'sent', 'submitted', 'accepted' => SmsStatus::Sent,
            default => SmsStatus::Pending,
        };
    }
}
