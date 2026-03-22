<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\EmailEngagementLevel;
use App\Enums\PlanModule;
use App\Models\Tenant\EmailLog;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\EmailEngagementProfile;
use App\Services\PlanAccessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmailEngagementService
{
    /**
     * Check if email engagement optimization is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.email_optimization.enabled', true)
            && app(PlanAccessService::class)->hasModule(PlanModule::AiInsights);
    }

    /**
     * Calculate engagement score for a member based on email history.
     */
    public function calculateEngagementScore(Member $member): EmailEngagementProfile
    {
        $config = config('ai.scoring.email_engagement', []);
        $baseScore = $config['base_score'] ?? 50;
        $factors = [];
        $recommendations = [];

        // Get email history for the last 90 days
        $emailHistory = EmailLog::query()
            ->where('member_id', $member->id)
            ->where('sent_at', '>=', now()->subDays(90))
            ->get();

        $totalSent = $emailHistory->count();
        $totalOpened = $emailHistory->whereNotNull('opened_at')->count();
        $totalClicked = $emailHistory->whereNotNull('clicked_at')->count();

        // If no email history, return default profile
        if ($totalSent === 0) {
            return new EmailEngagementProfile(
                memberId: (string) $member->id,
                engagementScore: $baseScore,
                engagementLevel: EmailEngagementLevel::fromScore($baseScore),
                optimalSendHour: 9,
                optimalSendDay: null,
                openRate: 0,
                clickRate: 0,
                factors: ['no_history' => 0],
                recommendations: ['No email history - consider initial outreach'],
            );
        }

        // 1. Open rate factor (30 weight)
        $openRate = ($totalOpened / $totalSent) * 100;
        $openWeight = $config['open_weight'] ?? 30;
        $openBonus = ($openRate / 100) * $openWeight;
        $factors['open_rate'] = round($openBonus, 2);

        // 2. Click rate factor (25 weight)
        $clickRate = ($totalClicked / $totalSent) * 100;
        $clickWeight = $config['click_weight'] ?? 25;
        $clickBonus = ($clickRate / 100) * $clickWeight;
        $factors['click_rate'] = round($clickBonus, 2);

        // 3. Recency bonus (20 weight)
        $lastEngagement = $emailHistory->whereNotNull('opened_at')->max('opened_at')
            ?? $emailHistory->whereNotNull('clicked_at')->max('clicked_at');
        $recencyBonus = 0;
        if ($lastEngagement) {
            $daysSinceEngagement = Carbon::parse($lastEngagement)->diffInDays(now());
            $recencyMaxBonus = $config['recency_max_bonus'] ?? 20;
            if ($daysSinceEngagement <= 7) {
                $recencyBonus = $recencyMaxBonus;
            } elseif ($daysSinceEngagement <= 30) {
                $recencyBonus = $recencyMaxBonus * 0.5;
            } elseif ($daysSinceEngagement <= 60) {
                $recencyBonus = $recencyMaxBonus * 0.25;
            }
        }
        $factors['recency'] = round($recencyBonus, 2);

        // 4. Consistency bonus (15 weight)
        $consistencyBonus = 0;
        if ($totalOpened >= 3) {
            $consistencyMaxBonus = $config['consistency_max_bonus'] ?? 15;
            $consistencyBonus = min($consistencyMaxBonus, $totalOpened * 2);
        }
        $factors['consistency'] = round($consistencyBonus, 2);

        // 5. Inactivity decay
        $inactivityDecay = 0;
        $thresholdDays = config('ai.features.email_optimization.inactivity_threshold_days', 60);
        if ($lastEngagement) {
            $daysSinceEngagement = Carbon::parse($lastEngagement)->diffInDays(now());
            if ($daysSinceEngagement > $thresholdDays) {
                $weeksInactive = floor(($daysSinceEngagement - $thresholdDays) / 7);
                $decayPerWeek = $config['inactivity_decay_per_week'] ?? 2;
                $inactivityDecay = -($weeksInactive * $decayPerWeek);
            }
        } elseif ($totalSent > 0) {
            // Has received emails but never opened/clicked - apply decay
            $inactivityDecay = -10;
        }
        $factors['inactivity_decay'] = round($inactivityDecay, 2);

        // Calculate total score
        $totalScore = $baseScore + array_sum($factors);
        $totalScore = max(0, min(100, $totalScore));

        // Determine engagement level from score
        $engagementLevel = EmailEngagementLevel::fromScore($totalScore);

        // Calculate optimal send time
        $optimalTime = $this->predictOptimalSendTime($member, $emailHistory);

        // Generate recommendations
        if ($engagementLevel->shouldReduceFrequency()) {
            $recommendations[] = 'Consider reducing email frequency';
        }
        if ($openRate < 20) {
            $recommendations[] = 'Low open rate - consider reviewing subject lines';
        }
        if ($clickRate < 5 && $openRate > 30) {
            $recommendations[] = 'Opens but no clicks - review email content/CTAs';
        }
        if ($totalSent > 10 && $totalOpened === 0) {
            $recommendations[] = 'No opens - verify email address or check spam';
        }

        return new EmailEngagementProfile(
            memberId: (string) $member->id,
            engagementScore: $totalScore,
            engagementLevel: $engagementLevel,
            optimalSendHour: $optimalTime['hour'],
            optimalSendDay: $optimalTime['day'],
            openRate: round($openRate, 2),
            clickRate: round($clickRate, 2),
            factors: $factors,
            recommendations: $recommendations,
        );
    }

    /**
     * Predict optimal send time based on historical open/click patterns.
     *
     * @return array{hour: int|null, day: int|null, confidence: float}
     */
    public function predictOptimalSendTime(Member $member, ?Collection $emailHistory = null): array
    {
        if (! $emailHistory instanceof Collection) {
            $emailHistory = EmailLog::query()
                ->where('member_id', $member->id)
                ->whereNotNull('opened_at')
                ->where('opened_at', '>=', now()->subDays(90))
                ->get();
        } else {
            $emailHistory = $emailHistory->whereNotNull('opened_at');
        }

        if ($emailHistory->count() < 3) {
            // Not enough data - return default optimal time (9 AM, any day)
            return ['hour' => 9, 'day' => null, 'confidence' => 30];
        }

        // Group by hour of day
        $hourCounts = [];
        $dayCounts = [];

        foreach ($emailHistory as $email) {
            if ($email->opened_at) {
                $openedAt = Carbon::parse($email->opened_at);
                $hour = $openedAt->hour;
                $day = $openedAt->dayOfWeek;

                $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
                $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
            }
        }

        // Find optimal hour (most opens)
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
        $confidence = min(90, 30 + ($emailHistory->count() * 5));

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
            $level = $member->email_engagement_level ?? 'inactive';
            if (isset($segments[$level])) {
                $segments[$level][] = [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'email' => $member->email,
                    'engagement_score' => $member->email_engagement_score,
                    'optimal_hour' => $member->email_optimal_send_hour,
                    'open_rate' => $member->email_open_rate,
                    'click_rate' => $member->email_click_rate,
                ];
            }
        }

        return $segments;
    }

    /**
     * Batch update engagement scores for all members in a branch.
     */
    public function batchUpdateEngagementScores(string $branchId): int
    {
        $members = Member::where('primary_branch_id', $branchId)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        if ($members->isEmpty()) {
            return 0;
        }

        // Pre-load all email logs to prevent N+1 queries
        $memberIds = $members->pluck('id');
        $emailLogsByMember = EmailLog::whereIn('member_id', $memberIds)->get()->groupBy('member_id');

        $updated = 0;

        foreach ($members as $member) {
            $profile = $this->calculateEngagementScore($member);

            $member->update([
                'email_engagement_score' => $profile->engagementScore,
                'email_engagement_level' => $profile->engagementLevel->value,
                'email_optimal_send_hour' => $profile->optimalSendHour,
                'email_optimal_send_day' => $profile->optimalSendDay,
                'email_open_rate' => $profile->openRate,
                'email_click_rate' => $profile->clickRate,
                'email_engagement_calculated_at' => now(),
            ]);

            // Update counters using pre-loaded email history
            $emailHistory = $emailLogsByMember->get($member->id, collect());
            $member->update([
                'email_total_sent' => $emailHistory->count(),
                'email_total_opened' => $emailHistory->whereNotNull('opened_at')->count(),
                'email_total_clicked' => $emailHistory->whereNotNull('clicked_at')->count(),
                'email_last_engaged_at' => $emailHistory->whereNotNull('opened_at')->max('opened_at')
                    ?? $emailHistory->whereNotNull('clicked_at')->max('clicked_at'),
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
