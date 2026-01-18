<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process cancelled subscriptions that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing expired subscriptions...');

        $expiredTenants = Tenant::whereNotNull('cancelled_at')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->whereIn('status', [TenantStatus::Active, TenantStatus::Trial])
            ->get();

        if ($expiredTenants->isEmpty()) {
            $this->info('No expired subscriptions to process.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredTenants as $tenant) {
            try {
                $tenant->update([
                    'status' => TenantStatus::Inactive,
                    'subscription_id' => null,
                ]);

                Log::info('Processed expired subscription', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'cancelled_at' => $tenant->cancelled_at,
                    'subscription_ends_at' => $tenant->subscription_ends_at,
                ]);

                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to process expired subscription', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to process tenant {$tenant->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$count} expired subscription(s).");

        return self::SUCCESS;
    }
}
