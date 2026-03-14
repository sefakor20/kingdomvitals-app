<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\EventType;
use App\Enums\LifecycleStage;
use App\Enums\PredictionTier;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventAttendancePrediction as EventAttendancePredictionModel;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\EventAttendancePrediction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EventPredictionService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Predict attendance probability for a member at an event.
     */
    public function predictForMember(Member $member, Event $event): EventAttendancePrediction
    {
        $config = config('ai.features.event_prediction', []);
        $score = 50; // Base score
        $factors = [];

        // Factor 1: Historical attendance for same event type (+0-30)
        $historicalBonus = $this->calculateHistoricalAttendanceBonus($member, $event);
        $score += $historicalBonus['score'];
        if ($historicalBonus['score'] !== 0) {
            $factors['historical_attendance'] = $historicalBonus;
        }

        // Factor 2: Lifecycle stage adjustment (-10 to +15)
        $lifecycleAdjustment = $this->calculateLifecycleAdjustment($member);
        $score += $lifecycleAdjustment['score'];
        if ($lifecycleAdjustment['score'] !== 0) {
            $factors['lifecycle_stage'] = $lifecycleAdjustment;
        }

        // Factor 3: Location proximity (+0-10)
        $proximityBonus = $this->calculateProximityBonus($member, $event);
        $score += $proximityBonus['score'];
        if ($proximityBonus['score'] !== 0) {
            $factors['location_proximity'] = $proximityBonus;
        }

        // Factor 4: Cluster membership for cluster events (+0-15)
        $clusterBonus = $this->calculateClusterBonus($member, $event);
        $score += $clusterBonus['score'];
        if ($clusterBonus['score'] !== 0) {
            $factors['cluster_membership'] = $clusterBonus;
        }

        // Factor 5: Recency penalty (0-20)
        $recencyPenalty = $this->calculateRecencyPenalty($member);
        $score -= $recencyPenalty['score'];
        if ($recencyPenalty['score'] !== 0) {
            $factors['recency'] = $recencyPenalty;
        }

        // Factor 6: Already registered check
        $isRegistered = $this->isAlreadyRegistered($member, $event);
        if ($isRegistered) {
            $score = 95; // Very high probability
            $factors['already_registered'] = [
                'score' => 45,
                'description' => 'Already registered for event',
            ];
        }

        // Ensure score stays within 0-100 range
        $score = max(0, min(100, $score));

        $tier = PredictionTier::fromProbability($score);

        return new EventAttendancePrediction(
            memberId: $member->id,
            eventId: $event->id,
            probability: round($score, 2),
            tier: $tier,
            factors: $factors,
            provider: 'heuristic',
        );
    }

    /**
     * Calculate historical attendance bonus based on same event type.
     *
     * @return array{score: int, description: string, value?: mixed}
     */
    protected function calculateHistoricalAttendanceBonus(Member $member, Event $event): array
    {
        // Get past events of the same type that this member attended
        $pastAttendance = EventRegistration::query()
            ->where('member_id', $member->id)
            ->where('status', 'attended')
            ->whereHas('event', function ($query) use ($event): void {
                $query->where('event_type', $event->event_type)
                    ->where('starts_at', '<', now());
            })
            ->count();

        // Get total past events of this type
        $totalPastEvents = Event::query()
            ->where('branch_id', $event->branch_id)
            ->where('event_type', $event->event_type)
            ->where('starts_at', '<', now())
            ->count();

        if ($totalPastEvents === 0) {
            return ['score' => 0, 'description' => 'No historical events of this type'];
        }

        $attendanceRate = $pastAttendance / $totalPastEvents;
        $bonus = (int) round($attendanceRate * 30);

        return [
            'score' => $bonus,
            'value' => [
                'attended' => $pastAttendance,
                'total' => $totalPastEvents,
                'rate' => round($attendanceRate * 100, 1),
            ],
            'description' => sprintf(
                'Attended %d of %d similar events (%.0f%%)',
                $pastAttendance,
                $totalPastEvents,
                $attendanceRate * 100
            ),
        ];
    }

    /**
     * Calculate lifecycle stage adjustment.
     *
     * @return array{score: int, description: string, value?: mixed}
     */
    protected function calculateLifecycleAdjustment(Member $member): array
    {
        $stage = $member->lifecycle_stage ?? LifecycleStage::Growing;

        $adjustment = match ($stage) {
            LifecycleStage::Engaged => 15,
            LifecycleStage::Growing => 10,
            LifecycleStage::NewMember => 5,
            LifecycleStage::Prospect => 0,
            LifecycleStage::Disengaging => -5,
            LifecycleStage::AtRisk => -10,
            LifecycleStage::Dormant, LifecycleStage::Inactive => -10,
        };

        return [
            'score' => $adjustment,
            'value' => $stage->value,
            'description' => sprintf('Lifecycle stage: %s', $stage->label()),
        ];
    }

    /**
     * Calculate proximity bonus based on location match.
     *
     * @return array{score: int, description: string, value?: mixed}
     */
    protected function calculateProximityBonus(Member $member, Event $event): array
    {
        if (empty($event->city) || empty($member->city)) {
            return ['score' => 0, 'description' => 'Location data unavailable'];
        }

        $memberCity = strtolower(trim($member->city));
        $eventCity = strtolower(trim($event->city));

        if ($memberCity === $eventCity) {
            return [
                'score' => 10,
                'value' => $member->city,
                'description' => sprintf('Same city as event (%s)', $member->city),
            ];
        }

        // Check state/region match
        if (! empty($member->state) && ! empty($event->country)) {
            $memberState = strtolower(trim($member->state ?? ''));
            // Assume event might have state in address
            if (! empty($memberState)) {
                return [
                    'score' => 5,
                    'value' => $member->state,
                    'description' => 'Same region as event',
                ];
            }
        }

        return ['score' => 0, 'description' => 'Different location than event'];
    }

    /**
     * Calculate cluster membership bonus for cluster-related events.
     *
     * @return array{score: int, description: string, value?: mixed}
     */
    protected function calculateClusterBonus(Member $member, Event $event): array
    {
        // Check if this is a cluster-related event
        $eventName = strtolower($event->name);
        $isClusterEvent = str_contains($eventName, 'cluster') ||
            str_contains($eventName, 'small group') ||
            str_contains($eventName, 'fellowship') ||
            $event->event_type === EventType::Social;

        if (! $isClusterEvent) {
            return ['score' => 0, 'description' => 'Not a cluster event'];
        }

        // Check if member belongs to a cluster
        $cluster = $member->clusters()->first();
        if ($cluster === null) {
            return [
                'score' => -5,
                'description' => 'Member not in a cluster',
            ];
        }

        // Active cluster member gets bonus
        return [
            'score' => 15,
            'value' => $cluster->name,
            'description' => sprintf('Active member of %s', $cluster->name),
        ];
    }

    /**
     * Calculate recency penalty based on days since last attendance.
     *
     * @return array{score: int, description: string, value?: mixed}
     */
    protected function calculateRecencyPenalty(Member $member): array
    {
        $lastAttendance = $member->attendance()
            ->latest('date')
            ->first();

        if (! $lastAttendance) {
            return [
                'score' => 15,
                'description' => 'No attendance records found',
            ];
        }

        $daysSince = $lastAttendance->date->diffInDays(now());

        if ($daysSince <= 7) {
            return ['score' => 0, 'description' => 'Attended recently'];
        }

        if ($daysSince <= 14) {
            return [
                'score' => 5,
                'value' => $daysSince,
                'description' => sprintf('%d days since last attendance', $daysSince),
            ];
        }

        if ($daysSince <= 30) {
            return [
                'score' => 10,
                'value' => $daysSince,
                'description' => sprintf('%d days since last attendance', $daysSince),
            ];
        }

        return [
            'score' => 20,
            'value' => $daysSince,
            'description' => sprintf('%d days since last attendance', $daysSince),
        ];
    }

    /**
     * Check if member is already registered for the event.
     */
    protected function isAlreadyRegistered(Member $member, Event $event): bool
    {
        return EventRegistration::query()
            ->where('member_id', $member->id)
            ->where('event_id', $event->id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    /**
     * Predict attendance for all active members of a branch for an event.
     *
     * @return Collection<EventAttendancePrediction>
     */
    public function predictForEvent(Event $event): Collection
    {
        $members = Member::query()
            ->where('primary_branch_id', $event->branch_id)
            ->whereIn('status', ['active', 'inactive']) // Include recently inactive
            ->whereNotIn('lifecycle_stage', [LifecycleStage::Inactive])
            ->get();

        return $members->map(fn (Member $member) => $this->predictForMember($member, $event));
    }

    /**
     * Save predictions to the database.
     *
     * @param  Collection<EventAttendancePrediction>  $predictions
     */
    public function savePredictions(Collection $predictions, Event $event): int
    {
        $saved = 0;

        foreach ($predictions as $prediction) {
            try {
                EventAttendancePredictionModel::updateOrCreate(
                    [
                        'event_id' => $prediction->eventId,
                        'member_id' => $prediction->memberId,
                    ],
                    [
                        'branch_id' => $event->branch_id,
                        'attendance_probability' => $prediction->probability,
                        'prediction_tier' => $prediction->tier,
                        'factors' => $prediction->factors,
                        'provider' => $prediction->provider,
                    ]
                );
                $saved++;
            } catch (\Throwable $e) {
                Log::warning('EventPredictionService: Failed to save prediction', [
                    'member_id' => $prediction->memberId,
                    'event_id' => $prediction->eventId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $saved;
    }

    /**
     * Get members who are likely to attend but haven't been invited.
     *
     * @return Collection<EventAttendancePredictionModel>
     */
    public function getHighProbabilityNotInvited(Event $event, int $limit = 50): Collection
    {
        return EventAttendancePredictionModel::query()
            ->where('event_id', $event->id)
            ->whereIn('prediction_tier', [PredictionTier::High, PredictionTier::Medium])
            ->where('invitation_sent', false)
            ->orderByDesc('attendance_probability')
            ->limit($limit)
            ->with('member')
            ->get();
    }

    /**
     * Check if the feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.event_prediction.enabled', false);
    }
}
