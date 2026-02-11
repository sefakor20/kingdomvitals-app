<?php

declare(strict_types=1);

namespace App\Enums;

enum LifecycleStage: string
{
    case Prospect = 'prospect';
    case NewMember = 'new_member';
    case Growing = 'growing';
    case Engaged = 'engaged';
    case Disengaging = 'disengaging';
    case AtRisk = 'at_risk';
    case Dormant = 'dormant';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::NewMember => 'New Member',
            self::Growing => 'Growing',
            self::Engaged => 'Engaged',
            self::Disengaging => 'Disengaging',
            self::AtRisk => 'At Risk',
            self::Dormant => 'Dormant',
            self::Inactive => 'Inactive',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Prospect => 'Visitor who has not yet converted to member',
            self::NewMember => 'Recently joined within the last 90 days',
            self::Growing => 'Regular attendance for 3-6 months',
            self::Engaged => 'Regular attendance 6+ months with active giving',
            self::Disengaging => 'Declining attendance or giving patterns',
            self::AtRisk => 'High churn risk or attendance anomaly detected',
            self::Dormant => 'No attendance in 90+ days',
            self::Inactive => 'Membership status is inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prospect => 'sky',
            self::NewMember => 'blue',
            self::Growing => 'cyan',
            self::Engaged => 'green',
            self::Disengaging => 'yellow',
            self::AtRisk => 'amber',
            self::Dormant => 'orange',
            self::Inactive => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Prospect => 'user-plus',
            self::NewMember => 'sparkles',
            self::Growing => 'arrow-trending-up',
            self::Engaged => 'star',
            self::Disengaging => 'arrow-trending-down',
            self::AtRisk => 'exclamation-triangle',
            self::Dormant => 'moon',
            self::Inactive => 'x-circle',
        };
    }

    /**
     * Get the priority for sorting (higher = more urgent attention needed).
     */
    public function priority(): int
    {
        return match ($this) {
            self::AtRisk => 100,
            self::Disengaging => 80,
            self::Dormant => 70,
            self::Prospect => 60,
            self::NewMember => 50,
            self::Growing => 30,
            self::Engaged => 10,
            self::Inactive => 0,
        };
    }

    /**
     * Check if this stage requires pastoral attention.
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::AtRisk, self::Disengaging, self::Dormant], true);
    }

    /**
     * Check if this is an active engagement stage.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::NewMember, self::Growing, self::Engaged], true);
    }

    /**
     * Check if this stage represents a potential member.
     */
    public function isPotentialMember(): bool
    {
        return $this === self::Prospect;
    }

    /**
     * Get recommended follow-up frequency in days.
     */
    public function followUpFrequencyDays(): int
    {
        return match ($this) {
            self::AtRisk => 3,
            self::Disengaging => 7,
            self::Dormant => 14,
            self::Prospect => 7,
            self::NewMember => 14,
            self::Growing => 30,
            self::Engaged => 60,
            self::Inactive => 90,
        };
    }

    /**
     * Get all stages that indicate declining engagement.
     *
     * @return array<self>
     */
    public static function decliningStages(): array
    {
        return [self::Disengaging, self::AtRisk, self::Dormant];
    }

    /**
     * Get all stages that indicate positive engagement.
     *
     * @return array<self>
     */
    public static function engagedStages(): array
    {
        return [self::NewMember, self::Growing, self::Engaged];
    }
}
