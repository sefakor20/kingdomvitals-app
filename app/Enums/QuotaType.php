<?php

declare(strict_types=1);

namespace App\Enums;

enum QuotaType: string
{
    case Members = 'members';
    case Branches = 'branches';
    case Sms = 'sms';
    case Storage = 'storage';
    case Households = 'households';
    case Clusters = 'clusters';
    case Visitors = 'visitors';
    case Equipment = 'equipment';

    /**
     * Get the plan field name that stores the limit for this quota type.
     */
    public function limitField(): string
    {
        return match ($this) {
            self::Members => 'max_members',
            self::Branches => 'max_branches',
            self::Sms => 'sms_credits_monthly',
            self::Storage => 'storage_quota_gb',
            self::Households => 'max_households',
            self::Clusters => 'max_clusters',
            self::Visitors => 'max_visitors',
            self::Equipment => 'max_equipment',
        };
    }

    /**
     * Get the cache key suffix for this quota type's count.
     */
    public function cacheKey(): string
    {
        return match ($this) {
            self::Sms => 'sms_sent_'.now()->format('Y-m'),
            self::Storage => 'storage_used',
            self::Members => 'member_count',
            self::Branches => 'branch_count',
            self::Households => 'household_count',
            self::Clusters => 'cluster_count',
            self::Visitors => 'visitor_count',
            self::Equipment => 'equipment_count',
        };
    }

    /**
     * Get the key name for the "current" value in quota arrays.
     */
    public function currentKey(): string
    {
        return match ($this) {
            self::Sms => 'sent',
            self::Storage => 'used',
            default => 'current',
        };
    }

    /**
     * Get the method name on SubscriptionPlan that checks if this quota is unlimited.
     */
    public function unlimitedMethodName(): string
    {
        return match ($this) {
            self::Members => 'hasUnlimitedMembers',
            self::Branches => 'hasUnlimitedBranches',
            self::Sms => 'hasUnlimitedSms',
            self::Storage => 'hasUnlimitedStorage',
            self::Households => 'hasUnlimitedHouseholds',
            self::Clusters => 'hasUnlimitedClusters',
            self::Visitors => 'hasUnlimitedVisitors',
            self::Equipment => 'hasUnlimitedEquipment',
        };
    }

    /**
     * Get the human-readable label for this quota type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Members => 'Members',
            self::Branches => 'Branches',
            self::Sms => 'SMS Credits',
            self::Storage => 'Storage',
            self::Households => 'Households',
            self::Clusters => 'Clusters',
            self::Visitors => 'Visitors',
            self::Equipment => 'Equipment',
        };
    }
}
