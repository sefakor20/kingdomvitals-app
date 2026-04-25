<?php

declare(strict_types=1);

use App\Models\SubscriptionPlan;

it('hides snake_case feature flags from display_features', function (): void {
    $plan = new SubscriptionPlan([
        'features' => [
            'Everything in Basic',
            'Visitor management & follow-up',
            'member_import',
            'bulk_sms_scheduling',
        ],
    ]);

    expect($plan->display_features)
        ->toBe(['Everything in Basic', 'Visitor management & follow-up']);
});

it('returns an empty array when features is null', function (): void {
    $plan = new SubscriptionPlan(['features' => null]);

    expect($plan->display_features)->toBe([]);
});

it('returns an empty array when features only contains flag identifiers', function (): void {
    $plan = new SubscriptionPlan([
        'features' => ['member_import', 'bulk_sms_scheduling'],
    ]);

    expect($plan->display_features)->toBe([]);
});

it('preserves prose strings that contain underscores or numbers', function (): void {
    $plan = new SubscriptionPlan([
        'features' => [
            '25 GB storage',
            'Top-tier support',
            'AI Insights & Giving Intelligence',
        ],
    ]);

    expect($plan->display_features)->toBe([
        '25 GB storage',
        'Top-tier support',
        'AI Insights & Giving Intelligence',
    ]);
});
