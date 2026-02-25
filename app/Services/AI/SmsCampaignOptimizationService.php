<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\PlanModule;
use App\Enums\SmsEngagementLevel;
use App\Enums\SmsStatus;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Services\AI\DTOs\CampaignOptimizationResult;
use App\Services\AI\DTOs\SmsEngagementProfile;
use App\Services\PlanAccessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SmsCampaignOptimizationService
{
    public function __construct(
        protected AiService $aiService,
    ) {}

    /**
     * Check if SMS optimization is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.sms_optimization.enabled', true)
            && app(PlanAccessService::class)->hasModule(PlanModule::AiInsights);
    }

    /**
     * Calculate engagement score for a member based on SMS history.
     */
    public function calculateEngagementScore(Member $member): SmsEngagementProfile
    {
        $config = config('ai.scoring.sms_engagement', []);
        $baseScore = $config['base_score'] ?? 50;
        $factors = [];
        $recommendations = [];

        // Check opt-out status
        if ($member->sms_opt_out) {
            return new SmsEngagementProfile(
                memberId: (string) $member->id,
                engagementScore: 0,
                engagementLevel: SmsEngagementLevel::Inactive,
                optimalSendHour: null,
                optimalSendDay: null,
                responseRate: 0,
                factors: ['opt_out_penalty' => -($config['opt_out_penalty'] ?? 50)],
                recommendations: ['Member has opted out of SMS communications'],
            );
        }

        // Get SMS history for the last 90 days
        $smsHistory = SmsLog::query()
            ->where('member_id', $member->id)
            ->where('sent_at', '>=', now()->subDays(90))
            ->get();

        $totalReceived = $smsHistory->count();
        $totalDelivered = $smsHistory->where('status', SmsStatus::Delivered)->count();
        $totalFailed = $smsHistory->where('status', SmsStatus::Failed)->count();

        // 1. Delivery rate factor
        $deliveryRate = $totalReceived > 0
            ? ($totalDelivered / $totalReceived) * 100
            : 50; // Assume 50% if no history
        $deliveryBonus = ($deliveryRate / 100) * ($config['delivery_weight'] ?? 15);
        $factors['delivery_rate'] = round($deliveryBonus, 2);

        // 2. Response indicator (we don't track responses directly, so use delivery as proxy)
        $responseBonus = 0;
        if ($totalDelivered > 5) {
            // Active receiver - give response bonus
            $responseBonus = ($config['response_weight'] ?? 30) * 0.5;
        }
        $factors['response_indicator'] = round($responseBonus, 2);

        // 3. Recency bonus - recent engagement matters
        $lastEngagement = $smsHistory->where('status', SmsStatus::Delivered)->max('delivered_at');
        $recencyBonus = 0;
        if ($lastEngagement) {
            $daysSinceEngagement = Carbon::parse($lastEngagement)->diffInDays(now());
            if ($daysSinceEngagement <= 7) {
                $recencyBonus = $config['recency_max_bonus'] ?? 20;
            } elseif ($daysSinceEngagement <= 30) {
                $recencyBonus = ($config['recency_max_bonus'] ?? 20) * 0.5;
            } elseif ($daysSinceEngagement <= 60) {
                $recencyBonus = ($config['recency_max_bonus'] ?? 20) * 0.25;
            }
        }
        $factors['recency'] = round($recencyBonus, 2);

        // 4. Consistency bonus - regular engagement pattern
        $consistencyBonus = 0;
        if ($totalDelivered >= 3) {
            // Calculate variance in delivery times
            $consistencyBonus = min($config['consistency_max_bonus'] ?? 15, $totalDelivered * 2);
        }
        $factors['consistency'] = round($consistencyBonus, 2);

        // 5. Inactivity decay
        $inactivityDecay = 0;
        $thresholdDays = config('ai.features.sms_optimization.inactivity_threshold_days', 60);
        if ($lastEngagement) {
            $daysSinceEngagement = Carbon::parse($lastEngagement)->diffInDays(now());
            if ($daysSinceEngagement > $thresholdDays) {
                $weeksInactive = floor(($daysSinceEngagement - $thresholdDays) / 7);
                $inactivityDecay = -($weeksInactive * ($config['inactivity_decay_per_week'] ?? 2));
            }
        } elseif ($totalReceived > 0) {
            // Has received but never delivered - apply decay
            $inactivityDecay = -10;
        }
        $factors['inactivity_decay'] = round($inactivityDecay, 2);

        // Calculate total score
        $totalScore = $baseScore + array_sum($factors);
        $totalScore = max(0, min(100, $totalScore));

        // Determine engagement level from score
        $engagementLevel = SmsEngagementLevel::fromScore($totalScore);

        // Calculate optimal send time
        $optimalTime = $this->predictOptimalSendTime($member, $smsHistory);

        // Calculate response rate (using delivery as proxy)
        $responseRate = $totalReceived > 0
            ? ($totalDelivered / $totalReceived) * 100
            : 0;

        // Generate recommendations
        if ($engagementLevel->shouldReduceFrequency()) {
            $recommendations[] = 'Consider reducing message frequency';
        }
        if ($totalFailed > $totalDelivered) {
            $recommendations[] = 'High failure rate - verify phone number';
        }
        if ($totalReceived === 0) {
            $recommendations[] = 'No SMS history - consider initial outreach';
        }

        return new SmsEngagementProfile(
            memberId: (string) $member->id,
            engagementScore: $totalScore,
            engagementLevel: $engagementLevel,
            optimalSendHour: $optimalTime['hour'],
            optimalSendDay: $optimalTime['day'],
            responseRate: round($responseRate, 2),
            factors: $factors,
            recommendations: $recommendations,
        );
    }

    /**
     * Predict optimal send time based on historical delivery patterns.
     *
     * @return array{hour: int|null, day: int|null, confidence: float}
     */
    public function predictOptimalSendTime(Member $member, ?Collection $smsHistory = null): array
    {
        if (! $smsHistory instanceof \Illuminate\Support\Collection) {
            $smsHistory = SmsLog::query()
                ->where('member_id', $member->id)
                ->where('status', SmsStatus::Delivered)
                ->where('delivered_at', '>=', now()->subDays(90))
                ->get();
        } else {
            $smsHistory = $smsHistory->where('status', SmsStatus::Delivered);
        }

        if ($smsHistory->count() < 3) {
            // Not enough data - return default optimal time (9 AM, any day)
            return ['hour' => 9, 'day' => null, 'confidence' => 30];
        }

        // Group by hour of day
        $hourCounts = [];
        $dayCounts = [];

        foreach ($smsHistory as $sms) {
            if ($sms->delivered_at) {
                $deliveredAt = Carbon::parse($sms->delivered_at);
                $hour = $deliveredAt->hour;
                $day = $deliveredAt->dayOfWeek;

                $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
                $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
            }
        }

        // Find optimal hour (most deliveries)
        $optimalHour = 9;
        $maxHourCount = 0;
        foreach ($hourCounts as $hour => $count) {
            if ($count > $maxHourCount) {
                $maxHourCount = $count;
                $optimalHour = $hour;
            }
        }

        // Find optimal day
        $optimalDay = null;
        $maxDayCount = 0;
        foreach ($dayCounts as $day => $count) {
            if ($count > $maxDayCount) {
                $maxDayCount = $count;
                $optimalDay = $day;
            }
        }

        // Calculate confidence based on data volume
        $confidence = min(90, 30 + ($smsHistory->count() * 5));

        return [
            'hour' => $optimalHour,
            'day' => $optimalDay,
            'confidence' => $confidence,
        ];
    }

    /**
     * Segment recipients by engagement level.
     *
     * @return array<string, array>
     */
    public function segmentRecipients(Collection $members): array
    {
        $segments = [
            'high' => [],
            'medium' => [],
            'low' => [],
            'inactive' => [],
        ];

        foreach ($members as $member) {
            $level = $member->sms_engagement_level ?? 'inactive';
            if (isset($segments[$level])) {
                $segments[$level][] = [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'phone' => $member->phone_number,
                    'engagement_score' => $member->sms_engagement_score,
                    'optimal_hour' => $member->sms_optimal_send_hour,
                ];
            }
        }

        return $segments;
    }

    /**
     * Optimize a campaign for a set of recipients.
     */
    public function optimizeCampaign(Collection $members): CampaignOptimizationResult
    {
        $segmentedRecipients = $this->segmentRecipients($members);

        $segmentCounts = [
            'high' => count($segmentedRecipients['high']),
            'medium' => count($segmentedRecipients['medium']),
            'low' => count($segmentedRecipients['low']),
            'inactive' => count($segmentedRecipients['inactive']),
        ];

        $totalRecipients = array_sum($segmentCounts);

        // Calculate optimal send times per segment
        $optimalSendTimes = [];
        foreach (SmsEngagementLevel::cases() as $level) {
            $segmentMembers = collect($segmentedRecipients[$level->value] ?? []);

            if ($segmentMembers->isNotEmpty()) {
                // Aggregate optimal hours
                $hours = $segmentMembers->pluck('optimal_hour')->filter()->all();
                $avgHour = count($hours) > 0 ? (int) round(array_sum($hours) / count($hours)) : 9;

                $optimalSendTimes[$level->value] = [
                    'hour' => $avgHour,
                    'time_slot' => $this->getTimeSlot($avgHour),
                ];
            }
        }

        // Calculate predicted engagement rate
        $engagedCount = $segmentCounts['high'] + $segmentCounts['medium'];
        $predictedEngagementRate = $totalRecipients > 0
            ? ($engagedCount / $totalRecipients) * 100 * 0.7 // Assume 70% of engaged actually engage
            : 0;

        // Generate recommendations
        $recommendations = [];

        if ($segmentCounts['inactive'] > $totalRecipients * 0.3) {
            $recommendations[] = 'Over 30% of recipients are inactive - consider targeted re-engagement';
        }

        if ($segmentCounts['low'] > $segmentCounts['high']) {
            $recommendations[] = 'More low-engagement than high-engagement recipients - consider segmented messaging';
        }

        if ($totalRecipients > 100 && $optimalSendTimes === []) {
            $recommendations[] = 'Large campaign - consider scheduling at optimal times per segment';
        }

        if ($predictedEngagementRate < 30) {
            $recommendations[] = 'Low predicted engagement - consider reviewing message content';
        }

        return new CampaignOptimizationResult(
            segmentedRecipients: $segmentedRecipients,
            optimalSendTimes: $optimalSendTimes,
            recommendations: $recommendations,
            predictedEngagementRate: round($predictedEngagementRate, 2),
            totalRecipients: $totalRecipients,
            segmentCounts: $segmentCounts,
        );
    }

    /**
     * Batch update engagement scores for all members in a branch.
     */
    public function batchUpdateEngagementScores(string $branchId): int
    {
        $members = Member::where('primary_branch_id', $branchId)
            ->where('status', 'active')
            ->whereNotNull('phone_number')
            ->get();

        if ($members->isEmpty()) {
            return 0;
        }

        // Pre-load all SMS logs to prevent N+1 queries
        $memberIds = $members->pluck('id');
        $smsLogsByMember = SmsLog::whereIn('member_id', $memberIds)->get()->groupBy('member_id');

        $updated = 0;

        foreach ($members as $member) {
            $profile = $this->calculateEngagementScore($member);

            $member->update([
                'sms_engagement_score' => $profile->engagementScore,
                'sms_engagement_level' => $profile->engagementLevel->value,
                'sms_optimal_send_hour' => $profile->optimalSendHour,
                'sms_optimal_send_day' => $profile->optimalSendDay,
                'sms_response_rate' => $profile->responseRate,
                'sms_engagement_calculated_at' => now(),
            ]);

            // Update counters using pre-loaded SMS history
            $smsHistory = $smsLogsByMember->get($member->id, collect());
            $member->update([
                'sms_total_received' => $smsHistory->count(),
                'sms_total_delivered' => $smsHistory->where('status', SmsStatus::Delivered)->count(),
                'sms_last_engaged_at' => $smsHistory->where('status', SmsStatus::Delivered)->max('delivered_at'),
            ]);

            $updated++;
        }

        return $updated;
    }

    /**
     * Get time slot from hour.
     */
    protected function getTimeSlot(int $hour): string
    {
        return match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 21 => 'evening',
            default => 'night',
        };
    }
}
