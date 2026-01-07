<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Enums\SmsStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use App\Models\TenantUsageSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AggregateUsageAnalyticsCommand extends Command
{
    protected $signature = 'analytics:aggregate-usage
                            {--tenant= : Process a specific tenant by ID}
                            {--dry-run : Show what would be collected without saving}';

    protected $description = 'Aggregate usage analytics for all tenants into snapshots';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        $today = now()->toDateString();

        if ($dryRun) {
            $this->info('DRY RUN MODE - No data will be saved');
        }

        $this->info('Starting usage analytics aggregation...');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::whereIn('status', [TenantStatus::Active, TenantStatus::Trial])->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $snapshot = $this->collectTenantUsage($tenant, $today);

                if ($dryRun) {
                    $this->newLine();
                    $this->line("  Tenant: {$tenant->name}");
                    $this->line("    Members: {$snapshot['active_members']}/{$snapshot['total_members']}");
                    $this->line("    Branches: {$snapshot['total_branches']}");
                    $this->line("    SMS This Month: {$snapshot['sms_sent_this_month']}");
                    $this->line("    Donations This Month: {$snapshot['donations_this_month']}");
                    $this->line('    Active Modules: '.implode(', ', $snapshot['active_modules'] ?? []));
                } else {
                    TenantUsageSnapshot::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'snapshot_date' => $today],
                        $snapshot
                    );
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to aggregate usage for tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Error processing tenant {$tenant->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Aggregation complete: {$successCount} successful, {$errorCount} errors");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Collect usage data for a single tenant.
     *
     * @return array<string, mixed>
     */
    private function collectTenantUsage(Tenant $tenant, string $today): array
    {
        tenancy()->initialize($tenant);

        $startOfMonth = now()->startOfMonth();

        try {
            // Member metrics
            $totalMembers = Member::withTrashed()->count();
            $activeMembers = Member::where('status', MembershipStatus::Active)->count();

            // Branch metrics
            $totalBranches = Branch::count();

            // SMS metrics for this month
            $smsSentThisMonth = SmsLog::whereIn('status', [SmsStatus::Sent, SmsStatus::Delivered])
                ->where('sent_at', '>=', $startOfMonth)
                ->count();

            $smsCostThisMonth = SmsLog::where('sent_at', '>=', $startOfMonth)
                ->sum('cost') ?? 0;

            // Donation metrics for this month
            $donationsThisMonth = Donation::where('donation_date', '>=', $startOfMonth)
                ->sum('amount') ?? 0;

            $donationCountThisMonth = Donation::where('donation_date', '>=', $startOfMonth)->count();

            // Attendance metrics for this month
            $attendanceThisMonth = Attendance::where('date', '>=', $startOfMonth)->count();

            // Visitor metrics for this month
            $visitorsThisMonth = Visitor::where('visit_date', '>=', $startOfMonth)->count();
            $visitorConversionsThisMonth = Visitor::where('is_converted', true)
                ->where('updated_at', '>=', $startOfMonth)
                ->count();

            // Detect active modules
            $activeModules = $this->detectActiveModules();

            // Calculate quota usage percentages
            $quotaUsage = $this->calculateQuotaUsage($tenant, $totalMembers, $totalBranches, $smsSentThisMonth);

            $snapshot = [
                'tenant_id' => $tenant->id,
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'total_branches' => $totalBranches,
                'sms_sent_this_month' => $smsSentThisMonth,
                'sms_cost_this_month' => $smsCostThisMonth,
                'donations_this_month' => $donationsThisMonth,
                'donation_count_this_month' => $donationCountThisMonth,
                'attendance_this_month' => $attendanceThisMonth,
                'visitors_this_month' => $visitorsThisMonth,
                'visitor_conversions_this_month' => $visitorConversionsThisMonth,
                'active_modules' => $activeModules,
                'member_quota_usage_percent' => $quotaUsage['member'],
                'branch_quota_usage_percent' => $quotaUsage['branch'],
                'sms_quota_usage_percent' => $quotaUsage['sms'],
                'storage_quota_usage_percent' => null, // Would require file storage calculation
                'snapshot_date' => $today,
            ];
        } finally {
            tenancy()->end();
        }

        return $snapshot;
    }

    /**
     * Detect which modules are actively being used.
     *
     * @return array<int, string>
     */
    private function detectActiveModules(): array
    {
        $modules = [];

        if (Member::exists()) {
            $modules[] = 'members';
        }

        if (Donation::exists()) {
            $modules[] = 'donations';
        }

        if (Attendance::exists()) {
            $modules[] = 'attendance';
        }

        if (SmsLog::exists()) {
            $modules[] = 'sms';
        }

        if (Visitor::exists()) {
            $modules[] = 'visitors';
        }

        if (Expense::exists()) {
            $modules[] = 'expenses';
        }

        if (Pledge::exists()) {
            $modules[] = 'pledges';
        }

        if (Cluster::exists()) {
            $modules[] = 'clusters';
        }

        if (PrayerRequest::exists()) {
            $modules[] = 'prayer';
        }

        return $modules;
    }

    /**
     * Calculate quota usage percentages based on subscription plan limits.
     *
     * @return array{member: float|null, branch: float|null, sms: float|null}
     */
    private function calculateQuotaUsage(Tenant $tenant, int $totalMembers, int $totalBranches, int $smsSent): array
    {
        $plan = $tenant->subscriptionPlan;

        if (! $plan) {
            return ['member' => null, 'branch' => null, 'sms' => null];
        }

        return [
            'member' => $plan->max_members
                ? round(($totalMembers / $plan->max_members) * 100, 2)
                : null,
            'branch' => $plan->max_branches
                ? round(($totalBranches / $plan->max_branches) * 100, 2)
                : null,
            'sms' => $plan->sms_credits_monthly
                ? round(($smsSent / $plan->sms_credits_monthly) * 100, 2)
                : null,
        ];
    }
}
