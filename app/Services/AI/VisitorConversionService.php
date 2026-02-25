<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Tenant\Visitor;
use App\Services\AI\DTOs\ConversionPrediction;
use Illuminate\Support\Carbon;

class VisitorConversionService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Calculate conversion score for a visitor.
     */
    public function calculateScore(Visitor $visitor): ConversionPrediction
    {
        // Use heuristic scoring (AI-enhanced scoring can be added later)
        return $this->calculateHeuristicScore($visitor);
    }

    /**
     * Calculate score using heuristic algorithm.
     */
    protected function calculateHeuristicScore(Visitor $visitor): ConversionPrediction
    {
        $config = config('ai.scoring.conversion');
        $score = $config['base_score'];
        $factors = [];

        // Factor 1: Visit count (attendance records)
        $visitCount = $visitor->attendance()->count();
        if ($visitCount > 0) {
            $visitBonus = min($visitCount * $config['return_visit_bonus'], 45); // Cap at 45 points
            $score += $visitBonus;
            $factors['visit_count'] = [
                'value' => $visitCount,
                'impact' => $visitBonus,
                'description' => "{$visitCount} visit(s) recorded",
            ];
        }

        // Factor 2: Referral source
        $referralSource = strtolower($visitor->how_did_you_hear ?? '');
        if (str_contains($referralSource, 'member') || str_contains($referralSource, 'friend') || str_contains($referralSource, 'family')) {
            $score += $config['member_referral_bonus'];
            $factors['referral_source'] = [
                'value' => $visitor->how_did_you_hear,
                'impact' => $config['member_referral_bonus'],
                'description' => 'Referred by existing connection',
            ];
        }

        // Factor 3: Follow-up outcomes
        $successfulFollowUps = $visitor->followUps()
            ->whereIn('outcome', ['successful', 'callback', 'rescheduled'])
            ->count();
        $failedFollowUps = $visitor->followUps()
            ->whereIn('outcome', ['not_interested', 'wrong_number', 'no_answer'])
            ->count();

        if ($successfulFollowUps > 0) {
            $followUpBonus = $successfulFollowUps * $config['successful_followup_bonus'];
            $score += $followUpBonus;
            $factors['successful_followups'] = [
                'value' => $successfulFollowUps,
                'impact' => $followUpBonus,
                'description' => "{$successfulFollowUps} successful follow-up(s)",
            ];
        }

        if ($failedFollowUps > 0) {
            $followUpPenalty = min($failedFollowUps * $config['failed_followup_penalty'], 30); // Cap penalty
            $score -= $followUpPenalty;
            $factors['failed_followups'] = [
                'value' => $failedFollowUps,
                'impact' => -$followUpPenalty,
                'description' => "{$failedFollowUps} unsuccessful follow-up(s)",
            ];
        }

        // Factor 4: Recency of attendance
        $lastAttendance = $visitor->attendance()->latest('date')->first();
        if ($lastAttendance) {
            $daysSinceAttendance = Carbon::parse($lastAttendance->date)->diffInDays(now());

            if ($daysSinceAttendance <= 7) {
                $score += $config['recent_attendance_bonus'];
                $factors['recent_attendance'] = [
                    'value' => $daysSinceAttendance,
                    'impact' => $config['recent_attendance_bonus'],
                    'description' => "Attended {$daysSinceAttendance} day(s) ago",
                ];
            } else {
                $weeksPenalty = (int) floor($daysSinceAttendance / 7);
                $timePenalty = min($weeksPenalty * $config['weeks_inactive_penalty'], 25);
                $score -= $timePenalty;
                $factors['time_since_attendance'] = [
                    'value' => $daysSinceAttendance,
                    'impact' => -$timePenalty,
                    'description' => "{$daysSinceAttendance} days since last attendance",
                ];
            }
        } else {
            // No attendance recorded yet - check visit date
            $daysSinceVisit = Carbon::parse($visitor->visit_date)->diffInDays(now());
            if ($daysSinceVisit > 14) {
                $weeksPenalty = (int) floor($daysSinceVisit / 7);
                $timePenalty = min($weeksPenalty * $config['weeks_inactive_penalty'], 20);
                $score -= $timePenalty;
                $factors['time_since_visit'] = [
                    'value' => $daysSinceVisit,
                    'impact' => -$timePenalty,
                    'description' => "{$daysSinceVisit} days since first visit",
                ];
            }
        }

        // Factor 5: Contact information completeness
        $hasEmail = ! empty($visitor->email);
        $hasPhone = ! empty($visitor->phone);
        if ($hasEmail && $hasPhone) {
            $score += 5;
            $factors['contact_complete'] = [
                'value' => true,
                'impact' => 5,
                'description' => 'Complete contact information',
            ];
        }

        // Ensure score stays within 0-100 range
        $score = max(0, min(100, $score));

        return new ConversionPrediction(
            score: round($score, 2),
            factors: $factors,
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Bulk calculate scores for multiple visitors.
     *
     * @param  \Illuminate\Support\Collection<Visitor>  $visitors
     * @return \Illuminate\Support\Collection<ConversionPrediction>
     */
    public function calculateScoresForMany($visitors): \Illuminate\Support\Collection
    {
        // Eager load relationships to prevent N+1 queries
        $visitors->load(['attendance', 'followUps']);

        return $visitors->map(fn (Visitor $visitor): array => [
            'visitor_id' => $visitor->id,
            'prediction' => $this->calculateScore($visitor),
        ]);
    }
}
