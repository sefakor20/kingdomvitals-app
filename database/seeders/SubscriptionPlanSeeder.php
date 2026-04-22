<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Essential tools for small churches to manage members, communication, and records—simple, reliable, and affordable.',
                'price_monthly' => 90.00,
                'price_annual' => 972.00,
                'price_monthly_usd' => 6.00,
                'price_annual_usd' => 64.80,
                'max_members' => 200,
                'max_branches' => 1,
                'storage_quota_gb' => 5,
                'sms_credits_monthly' => null,
                'max_households' => null,
                'max_clusters' => null,
                'max_visitors' => null,
                'max_equipment' => null,
                'enabled_modules' => [
                    'members', 'children', 'households', 'clusters',
                    'services', 'attendance', 'donations', 'expenses',
                    'reports', 'events',
                ],
                'features' => [
                    'Members management',
                    'Household tracking',
                    'Services & attendance',
                    'Donations & expenses',
                    'Events',
                ],
                'support_level' => SupportLevel::Priority,
                'is_active' => true,
                'is_default' => true,
                'is_featured' => false,
                'display_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'More capacity and messaging for growing churches that need flexibility as they scale.',
                'price_monthly' => 126.00,
                'price_annual' => 1360.80,
                'price_monthly_usd' => 8.40,
                'price_annual_usd' => 90.72,
                'max_members' => 500,
                'max_branches' => 1,
                'storage_quota_gb' => 8,
                'sms_credits_monthly' => null,
                'max_households' => null,
                'max_clusters' => null,
                'max_visitors' => null,
                'max_equipment' => null,
                'enabled_modules' => [
                    'members', 'children', 'households', 'clusters',
                    'services', 'attendance', 'donations', 'expenses',
                    'reports', 'events', 'visitors', 'pledges', 'budgets',
                    'sms', 'email', 'prayer_requests', 'duty_roster', 'equipment',
                ],
                'features' => [
                    'Everything in Basic',
                    'Visitor management & follow-up',
                    'Pledges & campaigns',
                    'Budgets & reports',
                    'SMS & email communication',
                    'Duty rosters',
                ],
                'support_level' => SupportLevel::Priority,
                'is_active' => true,
                'is_default' => false,
                'is_featured' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Advanced features, multi-branch support, and priority assistance for large and complex organizations.',
                'price_monthly' => 250.00,
                'price_annual' => 2700.00,
                'price_monthly_usd' => 16.67,
                'price_annual_usd' => 180.00,
                'max_members' => null,
                'max_branches' => null,
                'storage_quota_gb' => 25,
                'sms_credits_monthly' => null,
                'max_households' => null,
                'max_clusters' => null,
                'max_visitors' => null,
                'max_equipment' => null,
                'enabled_modules' => null,
                'features' => [
                    'Everything in Professional',
                    'Unlimited members & branches',
                    '25 GB storage',
                    'AI Insights & Giving Intelligence',
                    'Priority support',
                ],
                'support_level' => SupportLevel::Priority,
                'is_active' => true,
                'is_default' => false,
                'is_featured' => false,
                'display_order' => 3,
            ],
        ];

        foreach ($plans as $data) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
