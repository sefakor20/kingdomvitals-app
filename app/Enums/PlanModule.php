<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanModule: string
{
    case Members = 'members';
    case Children = 'children';
    case Households = 'households';
    case Clusters = 'clusters';
    case Services = 'services';
    case Attendance = 'attendance';
    case Visitors = 'visitors';
    case Donations = 'donations';
    case Expenses = 'expenses';
    case Pledges = 'pledges';
    case Budgets = 'budgets';
    case Sms = 'sms';
    case Equipment = 'equipment';
    case PrayerRequests = 'prayer_requests';
    case Reports = 'reports';
    case DutyRoster = 'duty_roster';
    case AiInsights = 'ai_insights';

    public function label(): string
    {
        return match ($this) {
            self::Members => 'Members',
            self::Children => "Children's Ministry",
            self::Households => 'Households',
            self::Clusters => 'Clusters/Groups',
            self::Services => 'Services',
            self::Attendance => 'Attendance',
            self::Visitors => 'Visitors',
            self::Donations => 'Donations',
            self::Expenses => 'Expenses',
            self::Pledges => 'Pledges & Campaigns',
            self::Budgets => 'Budgets',
            self::Sms => 'SMS Messaging',
            self::Equipment => 'Equipment',
            self::PrayerRequests => 'Prayer Requests',
            self::Reports => 'Reports',
            self::DutyRoster => 'Duty Roster',
            self::AiInsights => 'AI Insights',
        };
    }

    /**
     * Map route prefixes to modules for middleware auto-detection.
     */
    public static function fromRouteName(string $routeName): ?self
    {
        $prefix = explode('.', $routeName)[0] ?? '';

        return match ($prefix) {
            'members' => self::Members,
            'children' => self::Children,
            'households' => self::Households,
            'clusters' => self::Clusters,
            'services' => self::Services,
            'attendance' => self::Attendance,
            'visitors' => self::Visitors,
            'donations', 'offerings', 'finance', 'giving' => self::Donations,
            'expenses' => self::Expenses,
            'pledges', 'campaigns' => self::Pledges,
            'budgets' => self::Budgets,
            'sms' => self::Sms,
            'equipment' => self::Equipment,
            'prayer-requests' => self::PrayerRequests,
            'reports' => self::Reports,
            'duty-rosters' => self::DutyRoster,
            'ai', 'ai-insights' => self::AiInsights,
            default => null,
        };
    }

    /**
     * Get all module values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
