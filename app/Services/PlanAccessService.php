<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanModule;
use App\Enums\QuotaType;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Facades\Cache;

class PlanAccessService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const COUNT_CACHE_TTL = 60; // 1 minute

    public function __construct(
        private ?Tenant $tenant = null
    ) {
        // Don't eagerly resolve tenant() here because this service may be
        // instantiated before tenancy is initialized (e.g., in middleware DI).
        // Instead, we'll resolve it lazily in getTenant().
    }

    /**
     * Get the current tenant, resolving lazily if needed.
     */
    private function getTenant(): ?Tenant
    {
        // If a tenant was explicitly provided via constructor, use it
        if ($this->tenant instanceof \App\Models\Tenant) {
            return $this->tenant;
        }

        // Otherwise, resolve from tenancy context
        return tenant();
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
        $tenant = $this->getTenant();

        if (! $tenant instanceof \App\Models\Tenant) {
            return null;
        }

        $cacheKey = "tenant:{$tenant->id}:subscription_plan";

        return $this->cache()->remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return $tenant->subscriptionPlan;
        });
    }

    /**
     * Check if a module is enabled for the current plan.
     */
    public function hasModule(PlanModule|string $module): bool
    {
        $plan = $this->getPlan();

        if (! $plan instanceof \App\Models\SubscriptionPlan) {
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
     * Check if a specific AI feature is enabled.
     *
     * This checks both plan-level access (AiInsights module) and
     * feature-level configuration in config/ai.php.
     */
    public function hasAiFeature(string $feature): bool
    {
        if (! $this->hasModule(PlanModule::AiInsights)) {
            return false;
        }

        return config("ai.features.{$feature}.enabled", false);
    }

    // ============================================
    // GENERIC QUOTA METHODS
    // ============================================

    /**
     * Get quota usage information for a given quota type.
     *
     * @return array{current|sent|used: int|float, max: int|null, unlimited: bool, remaining: int|float|null, percent: float}
     */
    public function getQuota(QuotaType $type): array
    {
        $plan = $this->getPlan();
        $currentCount = $this->getCount($type);
        $currentKey = $type->currentKey();

        // Check if unlimited (no plan or null limit field)
        $limit = $plan?->{$type->limitField()};
        $isUnlimited = $limit === null;

        if (! $plan || $isUnlimited) {
            return [
                $currentKey => $type === QuotaType::Storage ? round($currentCount, 2) : $currentCount,
                'max' => null,
                'unlimited' => true,
                'remaining' => null,
                'percent' => 0,
            ];
        }

        $remaining = max(0, $limit - $currentCount);
        $percent = $limit > 0 ? round(($currentCount / $limit) * 100, 1) : 0;

        return [
            $currentKey => $type === QuotaType::Storage ? round($currentCount, 2) : $currentCount,
            'max' => $limit,
            'unlimited' => false,
            'remaining' => $type === QuotaType::Storage ? round($remaining, 2) : $remaining,
            'percent' => $percent,
        ];
    }

    /**
     * Check if more of the given resource can be created.
     */
    public function canCreate(QuotaType $type, int $count = 1): bool
    {
        $quota = $this->getQuota($type);

        return $quota['unlimited'] || ($quota['remaining'] !== null && $quota['remaining'] >= $count);
    }

    /**
     * Get the current count for a given quota type.
     */
    private function getCount(QuotaType $type): int|float
    {
        if (! $this->getTenant() instanceof \App\Models\Tenant) {
            return 0;
        }

        $cacheKey = "tenant:{$this->getTenant()->id}:{$type->cacheKey()}";

        return $this->cache()->remember($cacheKey, self::COUNT_CACHE_TTL, fn () => match ($type) {
            QuotaType::Members => Member::count(),
            QuotaType::Branches => Branch::count(),
            QuotaType::Sms => SmsLog::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            QuotaType::Storage => $this->calculateStorageUsed(),
            QuotaType::Households => Household::count(),
            QuotaType::Clusters => Cluster::count(),
            QuotaType::Visitors => Visitor::count(),
            QuotaType::Equipment => Equipment::count(),
        });
    }

    /**
     * Calculate storage used in GB.
     */
    private function calculateStorageUsed(): float
    {
        $tenantId = $this->getTenant()->id;
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

    // ============================================
    // BACKWARDS-COMPATIBLE WRAPPER METHODS
    // ============================================

    /**
     * Get quota usage information for members.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getMemberQuota(): array
    {
        return $this->getQuota(QuotaType::Members);
    }

    /**
     * Check if more members can be created.
     */
    public function canCreateMember(): bool
    {
        return $this->canCreate(QuotaType::Members);
    }

    /**
     * Get quota usage information for branches.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getBranchQuota(): array
    {
        return $this->getQuota(QuotaType::Branches);
    }

    /**
     * Check if more branches can be created.
     */
    public function canCreateBranch(): bool
    {
        return $this->canCreate(QuotaType::Branches);
    }

    /**
     * Get SMS quota information for current month.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getSmsQuota(): array
    {
        return $this->getQuota(QuotaType::Sms);
    }

    /**
     * Check if SMS can be sent (has remaining credits).
     */
    public function canSendSms(int $count = 1): bool
    {
        return $this->canCreate(QuotaType::Sms, $count);
    }

    /**
     * Get quota usage information for households.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getHouseholdQuota(): array
    {
        return $this->getQuota(QuotaType::Households);
    }

    /**
     * Check if more households can be created.
     */
    public function canCreateHousehold(): bool
    {
        return $this->canCreate(QuotaType::Households);
    }

    /**
     * Get quota usage information for clusters.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getClusterQuota(): array
    {
        return $this->getQuota(QuotaType::Clusters);
    }

    /**
     * Check if more clusters can be created.
     */
    public function canCreateCluster(): bool
    {
        return $this->canCreate(QuotaType::Clusters);
    }

    /**
     * Get quota usage information for visitors.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getVisitorQuota(): array
    {
        return $this->getQuota(QuotaType::Visitors);
    }

    /**
     * Check if more visitors can be created.
     */
    public function canCreateVisitor(): bool
    {
        return $this->canCreate(QuotaType::Visitors);
    }

    /**
     * Get quota usage information for equipment.
     *
     * @return array{current: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    public function getEquipmentQuota(): array
    {
        return $this->getQuota(QuotaType::Equipment);
    }

    /**
     * Check if more equipment can be created.
     */
    public function canCreateEquipment(): bool
    {
        return $this->canCreate(QuotaType::Equipment);
    }

    /**
     * Get storage quota information.
     *
     * @return array{used: float, max: int|null, unlimited: bool, remaining: float|null, percent: float}
     */
    public function getStorageQuota(): array
    {
        return $this->getQuota(QuotaType::Storage);
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

        $usedGb = $this->getCount(QuotaType::Storage);
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

    // ============================================
    // UTILITY METHODS
    // ============================================

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
     * Check if quota is approaching limit (above threshold percentage).
     */
    public function isQuotaWarning(QuotaType|string $quotaType, int $threshold = 80): bool
    {
        $type = $quotaType instanceof QuotaType
            ? $quotaType
            : QuotaType::tryFrom($quotaType);

        if (! $type) {
            return false;
        }

        $quota = $this->getQuota($type);

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
        if ($this->getTenant() instanceof \App\Models\Tenant) {
            $this->cache()->forget("tenant:{$this->getTenant()->id}:subscription_plan");

            foreach (QuotaType::cases() as $type) {
                $this->cache()->forget("tenant:{$this->getTenant()->id}:{$type->cacheKey()}");
            }
        }
    }

    /**
     * Invalidate count caches (call after creating/deleting resources).
     */
    public function invalidateCountCache(QuotaType|string $type): void
    {
        if (! $this->getTenant() instanceof \App\Models\Tenant) {
            return;
        }

        $quotaType = $type instanceof QuotaType
            ? $type
            : QuotaType::tryFrom($type);

        if ($quotaType) {
            $this->cache()->forget("tenant:{$this->getTenant()->id}:{$quotaType->cacheKey()}");
        }
    }
}
