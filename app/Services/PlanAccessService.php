<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanModule;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use Illuminate\Support\Facades\Cache;

class PlanAccessService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const COUNT_CACHE_TTL = 60; // 1 minute

    public function __construct(
        private ?Tenant $tenant = null
    ) {
        $this->tenant ??= tenant();
    }

    /**
     * Get the cache store directly to bypass tenancy's tagging wrapper.
     * The database cache driver doesn't support tags, so we access it directly.
     */
    private function cache(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store(config('cache.default'));
    }

    /**
     * Get the current subscription plan with caching.
     */
    public function getPlan(): ?SubscriptionPlan
    {
        if (! $this->tenant) {
            return null;
        }

        $cacheKey = "tenant:{$this->tenant->id}:subscription_plan";

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () {
            return $this->tenant->subscriptionPlan;
        });
    }

    /**
     * Check if a module is enabled for the current plan.
     */
    public function hasModule(PlanModule|string $module): bool
    {
        $plan = $this->getPlan();

        if (! $plan) {
            return false;
        }

        $moduleName = $module instanceof PlanModule ? $module->value : $module;

        return $plan->hasModule($moduleName);
    }

    /**
     * Check if a specific feature is enabled.
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->getPlan();

        if (! $plan || $plan->features === null) {
            return true; // null means all features enabled
        }

        return in_array($feature, $plan->features);
    }

    /**
     * Get quota usage information for members.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getMemberQuota(): array
    {
        $plan = $this->getPlan();
        $currentCount = $this->getMemberCount();

        if (! $plan || $plan->hasUnlimitedMembers()) {
            return [
                'current' => $currentCount,
                'max' => null,
                'unlimited' => true,
                'remaining' => null,
                'percent' => 0,
            ];
        }

        $max = $plan->max_members;
        $remaining = max(0, $max - $currentCount);

        return [
            'current' => $currentCount,
            'max' => $max,
            'unlimited' => false,
            'remaining' => $remaining,
            'percent' => $max > 0 ? round(($currentCount / $max) * 100, 1) : 0,
        ];
    }

    /**
     * Check if more members can be created.
     */
    public function canCreateMember(): bool
    {
        $quota = $this->getMemberQuota();

        return $quota['unlimited'] || $quota['remaining'] > 0;
    }

    /**
     * Get quota usage information for branches.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getBranchQuota(): array
    {
        $plan = $this->getPlan();
        $currentCount = $this->getBranchCount();

        if (! $plan || $plan->hasUnlimitedBranches()) {
            return [
                'current' => $currentCount,
                'max' => null,
                'unlimited' => true,
                'remaining' => null,
                'percent' => 0,
            ];
        }

        $max = $plan->max_branches;
        $remaining = max(0, $max - $currentCount);

        return [
            'current' => $currentCount,
            'max' => $max,
            'unlimited' => false,
            'remaining' => $remaining,
            'percent' => $max > 0 ? round(($currentCount / $max) * 100, 1) : 0,
        ];
    }

    /**
     * Check if more branches can be created.
     */
    public function canCreateBranch(): bool
    {
        $quota = $this->getBranchQuota();

        return $quota['unlimited'] || $quota['remaining'] > 0;
    }

    /**
     * Get SMS quota information for current month.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getSmsQuota(): array
    {
        $plan = $this->getPlan();
        $sentThisMonth = $this->getSmsSentThisMonth();

        if (! $plan || $plan->sms_credits_monthly === null) {
            return [
                'sent' => $sentThisMonth,
                'max' => null,
                'unlimited' => true,
                'remaining' => null,
                'percent' => 0,
            ];
        }

        $max = $plan->sms_credits_monthly;
        $remaining = max(0, $max - $sentThisMonth);

        return [
            'sent' => $sentThisMonth,
            'max' => $max,
            'unlimited' => false,
            'remaining' => $remaining,
            'percent' => $max > 0 ? round(($sentThisMonth / $max) * 100, 1) : 0,
        ];
    }

    /**
     * Check if SMS can be sent (has remaining credits).
     */
    public function canSendSms(int $count = 1): bool
    {
        $quota = $this->getSmsQuota();

        return $quota['unlimited'] || ($quota['remaining'] !== null && $quota['remaining'] >= $count);
    }

    /**
     * Get all enabled modules for the current plan.
     *
     * @return array<PlanModule>
     */
    public function getEnabledModules(): array
    {
        $plan = $this->getPlan();

        if (! $plan || $plan->enabled_modules === null) {
            return PlanModule::cases(); // All modules enabled
        }

        return collect($plan->enabled_modules)
            ->map(fn ($name) => PlanModule::tryFrom($name))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get storage quota information.
     *
     * @return array{used: float, max: int|null, unlimited: bool, remaining: float|null, percent: float}
     */
    public function getStorageQuota(): array
    {
        $plan = $this->getPlan();
        $usedGb = $this->getStorageUsedGb();

        if (! $plan || $plan->storage_quota_gb === null) {
            return [
                'used' => round($usedGb, 2),
                'max' => null,
                'unlimited' => true,
                'remaining' => null,
                'percent' => 0,
            ];
        }

        $max = $plan->storage_quota_gb;
        $remaining = max(0, $max - $usedGb);

        return [
            'used' => round($usedGb, 2),
            'max' => $max,
            'unlimited' => false,
            'remaining' => round($remaining, 2),
            'percent' => $max > 0 ? round(($usedGb / $max) * 100, 1) : 0,
        ];
    }

    /**
     * Check if a file of given size can be uploaded.
     */
    public function canUploadFile(int $fileSizeBytes): bool
    {
        $plan = $this->getPlan();

        if (! $plan || $plan->storage_quota_gb === null) {
            return true; // Unlimited
        }

        $usedGb = $this->getStorageUsedGb();
        $fileSizeGb = $fileSizeBytes / (1024 * 1024 * 1024);

        return ($usedGb + $fileSizeGb) <= $plan->storage_quota_gb;
    }

    /**
     * Check if the plan has unlimited storage.
     */
    public function hasUnlimitedStorage(): bool
    {
        $plan = $this->getPlan();

        return ! $plan || $plan->storage_quota_gb === null;
    }

    /**
     * Check if quota is approaching limit (above threshold percentage).
     */
    public function isQuotaWarning(string $quotaType, int $threshold = 80): bool
    {
        $quota = match ($quotaType) {
            'members' => $this->getMemberQuota(),
            'branches' => $this->getBranchQuota(),
            'sms' => $this->getSmsQuota(),
            'storage' => $this->getStorageQuota(),
            default => ['unlimited' => true, 'percent' => 0],
        };

        if ($quota['unlimited']) {
            return false;
        }

        return $quota['percent'] >= $threshold;
    }

    /**
     * Clear the cached plan data (call after plan changes).
     */
    public function clearCache(): void
    {
        if ($this->tenant) {
            $this->cache()->forget("tenant:{$this->tenant->id}:subscription_plan");
            $this->cache()->forget("tenant:{$this->tenant->id}:member_count");
            $this->cache()->forget("tenant:{$this->tenant->id}:branch_count");
            $this->cache()->forget("tenant:{$this->tenant->id}:sms_sent_".now()->format('Y-m'));
            $this->cache()->forget("tenant:{$this->tenant->id}:storage_used");
        }
    }

    /**
     * Invalidate count caches (call after creating/deleting resources).
     */
    public function invalidateCountCache(string $type): void
    {
        if (! $this->tenant) {
            return;
        }

        match ($type) {
            'members' => $this->cache()->forget("tenant:{$this->tenant->id}:member_count"),
            'branches' => $this->cache()->forget("tenant:{$this->tenant->id}:branch_count"),
            'sms' => $this->cache()->forget("tenant:{$this->tenant->id}:sms_sent_".now()->format('Y-m')),
            'storage' => $this->cache()->forget("tenant:{$this->tenant->id}:storage_used"),
            default => null,
        };
    }

    private function getMemberCount(): int
    {
        if (! $this->tenant) {
            return 0;
        }

        return $this->cache()->remember(
            "tenant:{$this->tenant->id}:member_count",
            self::COUNT_CACHE_TTL,
            fn () => Member::count()
        );
    }

    private function getBranchCount(): int
    {
        if (! $this->tenant) {
            return 0;
        }

        return $this->cache()->remember(
            "tenant:{$this->tenant->id}:branch_count",
            self::COUNT_CACHE_TTL,
            fn () => Branch::count()
        );
    }

    private function getSmsSentThisMonth(): int
    {
        if (! $this->tenant) {
            return 0;
        }

        return $this->cache()->remember(
            "tenant:{$this->tenant->id}:sms_sent_".now()->format('Y-m'),
            self::COUNT_CACHE_TTL,
            fn () => SmsLog::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count()
        );
    }

    private function getStorageUsedGb(): float
    {
        if (! $this->tenant) {
            return 0;
        }

        return $this->cache()->remember(
            "tenant:{$this->tenant->id}:storage_used",
            self::COUNT_CACHE_TTL,
            function () {
                // Calculate from actual filesystem
                $tenantId = $this->tenant->id;
                $storagePath = base_path("storage/app/public/members/{$tenantId}");

                if (! is_dir($storagePath)) {
                    return 0.0;
                }

                $bytes = 0;
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($storagePath, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $bytes += $file->getSize();
                    }
                }

                return $bytes / (1024 * 1024 * 1024); // Convert to GB
            }
        );
    }
}
