<?php

declare(strict_types=1);

namespace App\Enums;

enum AiAlertType: string
{
    case ChurnRisk = 'churn_risk';
    case AttendanceAnomaly = 'attendance_anomaly';
    case LifecycleChange = 'lifecycle_change';
    case CriticalPrayer = 'critical_prayer';
    case ClusterHealth = 'cluster_health';
    case HouseholdDisengagement = 'household_disengagement';

    public function label(): string
    {
        return match ($this) {
            self::ChurnRisk => 'Churn Risk Alert',
            self::AttendanceAnomaly => 'Attendance Anomaly',
            self::LifecycleChange => 'Lifecycle Transition',
            self::CriticalPrayer => 'Critical Prayer Request',
            self::ClusterHealth => 'Cluster Health Alert',
            self::HouseholdDisengagement => 'Household Disengagement',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ChurnRisk => 'Member churn risk score has exceeded threshold',
            self::AttendanceAnomaly => 'Significant attendance pattern change detected',
            self::LifecycleChange => 'Member has transitioned to an at-risk lifecycle stage',
            self::CriticalPrayer => 'A critical or high-urgency prayer request requires attention',
            self::ClusterHealth => 'A cluster/small group health has declined significantly',
            self::HouseholdDisengagement => 'Household engagement has dropped to disengaged level',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ChurnRisk => 'exclamation-triangle',
            self::AttendanceAnomaly => 'arrow-trending-down',
            self::LifecycleChange => 'user-minus',
            self::CriticalPrayer => 'hand-raised',
            self::ClusterHealth => 'user-group',
            self::HouseholdDisengagement => 'home',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ChurnRisk => 'red',
            self::AttendanceAnomaly => 'amber',
            self::LifecycleChange => 'orange',
            self::CriticalPrayer => 'purple',
            self::ClusterHealth => 'yellow',
            self::HouseholdDisengagement => 'blue',
        };
    }

    /**
     * Get the default threshold value for this alert type.
     */
    public function defaultThreshold(): ?int
    {
        return match ($this) {
            self::ChurnRisk => 70,
            self::AttendanceAnomaly => 50,
            self::LifecycleChange => null,
            self::CriticalPrayer => null,
            self::ClusterHealth => 50,
            self::HouseholdDisengagement => null,
        };
    }

    /**
     * Get the default severity for this alert type.
     */
    public function defaultSeverity(): AlertSeverity
    {
        return match ($this) {
            self::ChurnRisk => AlertSeverity::High,
            self::AttendanceAnomaly => AlertSeverity::Medium,
            self::LifecycleChange => AlertSeverity::High,
            self::CriticalPrayer => AlertSeverity::Critical,
            self::ClusterHealth => AlertSeverity::High,
            self::HouseholdDisengagement => AlertSeverity::Medium,
        };
    }

    /**
     * Get the default cooldown hours for this alert type.
     */
    public function defaultCooldownHours(): int
    {
        return match ($this) {
            self::ChurnRisk => 168, // 7 days
            self::AttendanceAnomaly => 168, // 7 days
            self::LifecycleChange => 168, // 7 days
            self::CriticalPrayer => 0, // Immediate, no cooldown
            self::ClusterHealth => 168, // 7 days
            self::HouseholdDisengagement => 168, // 7 days
        };
    }

    /**
     * Get all alert types as options for forms.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
