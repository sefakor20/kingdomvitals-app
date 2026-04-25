<?php

declare(strict_types=1);

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const FLAG = 'member_import';

    private const PAID_SLUGS = ['professional', 'enterprise'];

    public function up(): void
    {
        SubscriptionPlan::whereIn('slug', self::PAID_SLUGS)
            ->get()
            ->each(function (SubscriptionPlan $plan): void {
                $features = is_array($plan->features) ? $plan->features : [];

                if (in_array(self::FLAG, $features, true)) {
                    return;
                }

                $features[] = self::FLAG;
                $plan->features = $features;
                $plan->save();
            });
    }

    public function down(): void
    {
        SubscriptionPlan::whereIn('slug', self::PAID_SLUGS)
            ->get()
            ->each(function (SubscriptionPlan $plan): void {
                $features = is_array($plan->features) ? $plan->features : [];

                if (! in_array(self::FLAG, $features, true)) {
                    return;
                }

                $plan->features = array_values(array_filter(
                    $features,
                    fn (string $feature): bool => $feature !== self::FLAG,
                ));
                $plan->save();
            });
    }
};
