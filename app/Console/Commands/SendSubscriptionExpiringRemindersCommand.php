<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiringRemindersCommand extends Command
{
    protected $signature = 'subscriptions:send-expiring-reminders
                            {--days=3 : Days before expiry to send the reminder}';

    protected $description = 'Send reminder emails to tenants whose cancelled subscription expires soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $tenants = Tenant::whereNotNull('cancelled_at')
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', $targetDate)
            ->whereNotNull('contact_email')
            ->get();

        $count = 0;

        foreach ($tenants as $tenant) {
            try {
                Mail::to($tenant->contact_email)->queue(new SubscriptionExpiringMail($tenant));
                $count++;

                Log::info('Subscription expiring reminder sent', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'expires_at' => $tenant->subscription_ends_at,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send subscription expiring reminder', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            $this->info("Sent {$count} expiring subscription reminder(s).");
        } else {
            $this->info('No expiring subscriptions to remind today.');
        }

        return Command::SUCCESS;
    }
}
