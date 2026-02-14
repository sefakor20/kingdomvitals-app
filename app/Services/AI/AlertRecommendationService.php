<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\LifecycleStage;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\AiAlert;
use App\Services\AI\DTOs\AlertRecommendation;

class AlertRecommendationService
{
    /**
     * Get recommendations for an alert based on its type and context.
     *
     * @return array<AlertRecommendation>
     */
    public function getRecommendationsForAlert(AiAlert $alert): array
    {
        if (! config('ai.features.recommendations.enabled', true)) {
            return [];
        }

        $recommendations = match ($alert->alert_type) {
            AiAlertType::ChurnRisk => $this->getChurnRiskRecommendations($alert),
            AiAlertType::AttendanceAnomaly => $this->getAttendanceAnomalyRecommendations($alert),
            AiAlertType::LifecycleChange => $this->getLifecycleChangeRecommendations($alert),
            AiAlertType::CriticalPrayer => $this->getCriticalPrayerRecommendations($alert),
            AiAlertType::ClusterHealth => $this->getClusterHealthRecommendations($alert),
            AiAlertType::HouseholdDisengagement => $this->getHouseholdDisengagementRecommendations($alert),
        };

        $maxRecommendations = config('ai.features.recommendations.max_per_alert', 3);

        return array_slice($recommendations, 0, $maxRecommendations);
    }

    /**
     * Get recommendations for churn risk alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getChurnRiskRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $churnScore = $alert->data['churn_score'] ?? 0;
        $factors = $alert->data['factors'] ?? [];

        // High churn scores need immediate attention
        if ($churnScore >= 85 || $alert->severity === AlertSeverity::Critical) {
            $recommendations[] = new AlertRecommendation(
                action: 'Schedule pastoral visit',
                description: 'Schedule an in-person visit within 48 hours to check on their wellbeing and spiritual health.',
                priority: 'immediate',
                assignTo: 'pastor',
                icon: 'home',
            );
        } else {
            $recommendations[] = new AlertRecommendation(
                action: 'Make a personal call',
                description: 'Reach out by phone to express care and check in on how they are doing.',
                priority: 'soon',
                assignTo: 'pastor',
                icon: 'phone',
            );
        }

        // Check for giving-related factors
        if (in_array('giving_decline', $factors, true) || in_array('no_recent_donations', $factors, true)) {
            $recommendations[] = new AlertRecommendation(
                action: 'Review giving history',
                description: 'Check their giving patterns for any life changes that may require pastoral support.',
                priority: 'soon',
                assignTo: 'pastor',
                icon: 'currency-dollar',
            );
        }

        $recommendations[] = new AlertRecommendation(
            action: 'Send personalized message',
            description: 'Send a caring message acknowledging their value to the church community.',
            priority: 'soon',
            assignTo: 'care_team',
            icon: 'chat-bubble-left-right',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Invite to fellowship event',
            description: 'Send a personal invitation to an upcoming church social or fellowship gathering.',
            priority: 'when_possible',
            assignTo: 'care_team',
            icon: 'calendar',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Connect with small group leader',
            description: 'Notify their cluster/small group leader to provide additional support and follow-up.',
            priority: 'soon',
            assignTo: 'leader',
            icon: 'user-group',
        );

        return $recommendations;
    }

    /**
     * Get recommendations for attendance anomaly alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getAttendanceAnomalyRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $anomalyScore = $alert->data['anomaly_score'] ?? 0;

        $recommendations[] = new AlertRecommendation(
            action: 'Check on wellbeing',
            description: 'Reach out to ensure they are healthy and doing well. Ask if anything is preventing their attendance.',
            priority: $anomalyScore >= 75 ? 'immediate' : 'soon',
            assignTo: 'care_team',
            icon: 'heart',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Review household attendance',
            description: 'Check if other family members are also showing attendance changes, which may indicate a family issue.',
            priority: 'soon',
            assignTo: 'admin',
            icon: 'home',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Check recent prayer requests',
            description: 'Review any recent prayer requests for context on life challenges they may be facing.',
            priority: 'soon',
            assignTo: 'prayer_team',
            icon: 'hand-raised',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Connect with cluster leader',
            description: 'Ask the cluster leader if they have any insight into the member\'s situation.',
            priority: 'soon',
            assignTo: 'leader',
            icon: 'user-group',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Consider home visit',
            description: 'If the pattern continues and calls go unanswered, schedule a pastoral home visit.',
            priority: 'when_possible',
            assignTo: 'pastor',
            icon: 'map-pin',
        );

        return $recommendations;
    }

    /**
     * Get recommendations for lifecycle transition alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getLifecycleChangeRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $currentStage = LifecycleStage::tryFrom($alert->data['current_stage'] ?? '');

        return match ($currentStage) {
            LifecycleStage::AtRisk => $this->getAtRiskLifecycleRecommendations($alert),
            LifecycleStage::Disengaging => $this->getDisengagingLifecycleRecommendations($alert),
            LifecycleStage::Dormant => $this->getDormantLifecycleRecommendations($alert),
            default => $this->getDefaultLifecycleRecommendations($alert),
        };
    }

    /**
     * Get recommendations for at-risk lifecycle stage.
     *
     * @return array<AlertRecommendation>
     */
    protected function getAtRiskLifecycleRecommendations(AiAlert $alert): array
    {
        return [
            new AlertRecommendation(
                action: 'Immediate pastoral contact',
                description: 'Schedule immediate contact to understand their current situation and offer support.',
                priority: 'immediate',
                assignTo: 'pastor',
                icon: 'phone',
            ),
            new AlertRecommendation(
                action: 'Review engagement history',
                description: 'Analyze their recent attendance, giving, and involvement to identify specific concerns.',
                priority: 'immediate',
                assignTo: 'admin',
                icon: 'chart-bar',
            ),
            new AlertRecommendation(
                action: 'Assign care team member',
                description: 'Assign a dedicated care team member for regular check-ins over the next month.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'user-plus',
            ),
            new AlertRecommendation(
                action: 'Personal event invitation',
                description: 'Send a personal invitation to a relevant upcoming ministry event.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'calendar',
            ),
        ];
    }

    /**
     * Get recommendations for disengaging lifecycle stage.
     *
     * @return array<AlertRecommendation>
     */
    protected function getDisengagingLifecycleRecommendations(AiAlert $alert): array
    {
        return [
            new AlertRecommendation(
                action: 'Schedule care conversation',
                description: 'Reach out for a caring conversation to understand what\'s happening in their life.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'chat-bubble-left-right',
            ),
            new AlertRecommendation(
                action: 'Assign regular check-ins',
                description: 'Set up weekly check-ins with a care team member for the next 4 weeks.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'calendar-days',
            ),
            new AlertRecommendation(
                action: 'Identify connection opportunities',
                description: 'Look for ministry or service opportunities that match their interests and gifts.',
                priority: 'when_possible',
                assignTo: 'leader',
                icon: 'puzzle-piece',
            ),
            new AlertRecommendation(
                action: 'Send encouraging content',
                description: 'Share relevant sermon clips, devotionals, or encouraging messages.',
                priority: 'when_possible',
                assignTo: 'care_team',
                icon: 'envelope',
            ),
        ];
    }

    /**
     * Get recommendations for dormant lifecycle stage.
     *
     * @return array<AlertRecommendation>
     */
    protected function getDormantLifecycleRecommendations(AiAlert $alert): array
    {
        return [
            new AlertRecommendation(
                action: 'Send re-engagement message',
                description: 'Send a warm, personal message expressing that they are missed and inviting them back.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'envelope',
            ),
            new AlertRecommendation(
                action: 'Personal invitation to special event',
                description: 'Invite them personally to a high-impact event like Easter, Christmas, or a special celebration.',
                priority: 'soon',
                assignTo: 'pastor',
                icon: 'calendar',
            ),
            new AlertRecommendation(
                action: 'Attempt phone contact',
                description: 'Make a personal call to check in and express care for them and their family.',
                priority: 'when_possible',
                assignTo: 'care_team',
                icon: 'phone',
            ),
            new AlertRecommendation(
                action: 'Consider home visit',
                description: 'If other attempts don\'t connect, consider a pastoral home visit.',
                priority: 'when_possible',
                assignTo: 'pastor',
                icon: 'home',
            ),
        ];
    }

    /**
     * Get default lifecycle recommendations.
     *
     * @return array<AlertRecommendation>
     */
    protected function getDefaultLifecycleRecommendations(AiAlert $alert): array
    {
        return [
            new AlertRecommendation(
                action: 'Review member status',
                description: 'Review the member\'s overall engagement and determine appropriate follow-up.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'clipboard-document-check',
            ),
            new AlertRecommendation(
                action: 'Schedule check-in',
                description: 'Reach out to check on their wellbeing and church connection.',
                priority: 'soon',
                assignTo: 'care_team',
                icon: 'phone',
            ),
        ];
    }

    /**
     * Get recommendations for critical prayer alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getCriticalPrayerRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $urgencyLevel = PrayerUrgencyLevel::tryFrom($alert->data['urgency_level'] ?? '');
        $category = $alert->data['category'] ?? null;

        if ($urgencyLevel === PrayerUrgencyLevel::Critical) {
            $recommendations[] = new AlertRecommendation(
                action: 'Immediate pastoral response',
                description: 'This requires immediate attention. Contact the person right away to offer support.',
                priority: 'immediate',
                assignTo: 'pastor',
                icon: 'exclamation-triangle',
            );

            $recommendations[] = new AlertRecommendation(
                action: 'Activate prayer team',
                description: 'Notify the prayer team immediately for urgent intercession (respecting confidentiality).',
                priority: 'immediate',
                assignTo: 'prayer_team',
                icon: 'hand-raised',
            );
        } else {
            $recommendations[] = new AlertRecommendation(
                action: 'Same-day follow-up',
                description: 'Reach out today to offer support and pray with them.',
                priority: 'immediate',
                assignTo: 'pastor',
                icon: 'phone',
            );

            $recommendations[] = new AlertRecommendation(
                action: 'Add to prayer list',
                description: 'Add to the church prayer list (with permission) for ongoing intercession.',
                priority: 'soon',
                assignTo: 'prayer_team',
                icon: 'hand-raised',
            );
        }

        // Health-related recommendations
        if ($category === 'health' || $category === 'medical') {
            $recommendations[] = new AlertRecommendation(
                action: 'Consider hospital/home visit',
                description: 'If hospitalized or homebound, schedule a pastoral visit to provide comfort.',
                priority: 'soon',
                assignTo: 'pastor',
                icon: 'home',
            );
        }

        // General support recommendations
        $recommendations[] = new AlertRecommendation(
            action: 'Connect with care resources',
            description: 'If appropriate, connect them with professional counseling or community resources.',
            priority: 'soon',
            assignTo: 'care_team',
            icon: 'lifebuoy',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Schedule follow-up check-in',
            description: 'Set a reminder to follow up on the prayer request in 3-5 days.',
            priority: 'when_possible',
            assignTo: 'care_team',
            icon: 'calendar',
        );

        return $recommendations;
    }

    /**
     * Get recommendations for cluster health alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getClusterHealthRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $healthScore = $alert->data['health_score'] ?? 0;
        $memberCount = $alert->data['member_count'] ?? 0;

        if ($healthScore < 30 || $alert->severity === AlertSeverity::Critical) {
            $recommendations[] = new AlertRecommendation(
                action: 'Meet with cluster leader',
                description: 'Schedule an urgent meeting with the cluster leader to understand challenges and provide support.',
                priority: 'immediate',
                assignTo: 'pastor',
                icon: 'user-group',
            );
        } else {
            $recommendations[] = new AlertRecommendation(
                action: 'Connect with cluster leader',
                description: 'Reach out to the cluster leader to discuss the health decline and offer support.',
                priority: 'soon',
                assignTo: 'pastor',
                icon: 'user-group',
            );
        }

        $recommendations[] = new AlertRecommendation(
            action: 'Review member participation',
            description: 'Analyze individual member attendance and participation trends within the cluster.',
            priority: 'soon',
            assignTo: 'admin',
            icon: 'chart-bar',
        );

        if ($memberCount > 12) {
            $recommendations[] = new AlertRecommendation(
                action: 'Consider cluster division',
                description: 'The cluster may be too large. Consider splitting into smaller, more manageable groups.',
                priority: 'when_possible',
                assignTo: 'pastor',
                icon: 'scissors',
            );
        } elseif ($memberCount < 4) {
            $recommendations[] = new AlertRecommendation(
                action: 'Consider cluster merger',
                description: 'The cluster may be too small to be sustainable. Consider merging with another cluster.',
                priority: 'when_possible',
                assignTo: 'pastor',
                icon: 'arrows-pointing-in',
            );
        }

        $recommendations[] = new AlertRecommendation(
            action: 'Plan fellowship activity',
            description: 'Organize a special fellowship event to strengthen community bonds.',
            priority: 'when_possible',
            assignTo: 'leader',
            icon: 'calendar',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Evaluate leader support',
            description: 'Assess if the cluster leader needs additional training, resources, or co-leadership.',
            priority: 'when_possible',
            assignTo: 'pastor',
            icon: 'academic-cap',
        );

        return $recommendations;
    }

    /**
     * Get recommendations for household disengagement alerts.
     *
     * @return array<AlertRecommendation>
     */
    public function getHouseholdDisengagementRecommendations(AiAlert $alert): array
    {
        $recommendations = [];
        $memberCount = $alert->data['member_count'] ?? 0;
        $engagementScore = $alert->data['engagement_score'] ?? 0;

        $recommendations[] = new AlertRecommendation(
            action: 'Schedule family visit',
            description: 'Plan a pastoral visit to connect with the entire household and understand their situation.',
            priority: 'soon',
            assignTo: 'pastor',
            icon: 'home',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Identify primary contact',
            description: 'Determine who is the best contact for the household and reach out to them first.',
            priority: 'soon',
            assignTo: 'care_team',
            icon: 'user',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Review individual engagement',
            description: 'Check each household member\'s engagement separately to identify specific concerns.',
            priority: 'soon',
            assignTo: 'admin',
            icon: 'users',
        );

        $recommendations[] = new AlertRecommendation(
            action: 'Invite to family event',
            description: 'Send a personal invitation to a family-oriented church event or activity.',
            priority: 'when_possible',
            assignTo: 'care_team',
            icon: 'calendar',
        );

        // If household has children
        if ($memberCount >= 3) {
            $recommendations[] = new AlertRecommendation(
                action: 'Connect with children\'s ministry',
                description: 'Check if children are still engaged and if children\'s ministry can help re-engage the family.',
                priority: 'when_possible',
                assignTo: 'leader',
                icon: 'face-smile',
            );
        }

        return $recommendations;
    }

    /**
     * Convert recommendations array to storable format.
     *
     * @param  array<AlertRecommendation>  $recommendations
     */
    public function toStorableFormat(array $recommendations): array
    {
        return array_map(fn (AlertRecommendation $r) => $r->toArray(), $recommendations);
    }

    /**
     * Restore recommendations from stored format.
     *
     * @return array<AlertRecommendation>
     */
    public function fromStoredFormat(array $data): array
    {
        return array_map(fn (array $r) => AlertRecommendation::fromArray($r), $data);
    }
}
