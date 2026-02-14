<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\ClusterHealthLevel;
use App\Enums\LifecycleStage;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\ClusterRecommendation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MemberRecommendationService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Get cluster recommendations for a member.
     *
     * @return array<ClusterRecommendation>
     */
    public function getRecommendations(Member $member, ?int $limit = null): array
    {
        if (! config('ai.features.member_recommendation.enabled', true)) {
            return [];
        }

        $limit ??= config('ai.features.member_recommendation.max_recommendations', 3);
        $minThreshold = config('ai.features.member_recommendation.min_score_threshold', 30);

        $eligibleClusters = $this->getEligibleClusters($member);

        if ($eligibleClusters->isEmpty()) {
            return [];
        }

        $recommendations = [];

        foreach ($eligibleClusters as $cluster) {
            $recommendation = $this->scoreCluster($member, $cluster);

            if ($recommendation->overallScore >= $minThreshold) {
                $recommendations[] = $recommendation;
            }
        }

        // Sort by overall score descending
        usort($recommendations, fn ($a, $b) => $b->overallScore <=> $a->overallScore);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get clusters eligible for recommendation (not already joined, active, has capacity).
     */
    protected function getEligibleClusters(Member $member): Collection
    {
        $existingClusterIds = $member->clusters()->pluck('clusters.id')->toArray();
        $branchId = $member->primary_branch_id;

        return Cluster::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNotIn('id', $existingClusterIds)
            ->withCount('members')
            ->get()
            ->filter(function (Cluster $cluster) {
                // Filter out full clusters
                if ($cluster->capacity === null) {
                    return true; // No capacity limit
                }

                return $cluster->members_count < $cluster->capacity;
            });
    }

    /**
     * Score a cluster for a member and create recommendation DTO.
     */
    protected function scoreCluster(Member $member, Cluster $cluster): ClusterRecommendation
    {
        $config = config('ai.scoring.member_recommendation', []);

        $locationWeight = $config['location_weight'] ?? 0.25;
        $demographicsWeight = $config['demographics_weight'] ?? 0.25;
        $capacityWeight = $config['capacity_weight'] ?? 0.20;
        $healthWeight = $config['health_weight'] ?? 0.15;
        $lifecycleWeight = $config['lifecycle_weight'] ?? 0.15;

        // Calculate component scores
        $locationScore = $this->calculateLocationScore($member, $cluster, $config);
        $demographicsScore = $this->calculateDemographicsScore($member, $cluster, $config);
        $capacityScore = $this->calculateCapacityScore($cluster);
        $healthScore = $this->calculateHealthScore($cluster);
        $lifecycleScore = $this->calculateLifecycleScore($member, $cluster);

        // Calculate weighted overall score
        $overallScore = (
            ($locationScore * $locationWeight) +
            ($demographicsScore * $demographicsWeight) +
            ($capacityScore * $capacityWeight) +
            ($healthScore * $healthWeight) +
            ($lifecycleScore * $lifecycleWeight)
        );

        // Generate match reasons
        $scores = [
            'location' => $locationScore,
            'demographics' => $demographicsScore,
            'capacity' => $capacityScore,
            'health' => $healthScore,
            'lifecycle' => $lifecycleScore,
        ];
        $matchReasons = $this->generateMatchReasons($scores, $member, $cluster, $config);

        return new ClusterRecommendation(
            clusterId: (string) $cluster->id,
            clusterName: $cluster->name,
            clusterType: $cluster->cluster_type?->value ?? 'cell_group',
            overallScore: round($overallScore, 2),
            locationScore: round($locationScore, 2),
            demographicsScore: round($demographicsScore, 2),
            capacityScore: round($capacityScore, 2),
            healthScore: round($healthScore, 2),
            lifecycleScore: round($lifecycleScore, 2),
            matchReasons: $matchReasons,
            currentMembers: $cluster->members_count ?? $cluster->members()->count(),
            capacity: $cluster->capacity,
            meetingDay: $cluster->meeting_day,
            meetingTime: $cluster->meeting_time,
            meetingLocation: $cluster->meeting_location,
        );
    }

    /**
     * Calculate location score based on city/state matching.
     */
    protected function calculateLocationScore(Member $member, Cluster $cluster, array $config): float
    {
        $sameCityScore = $config['same_city_score'] ?? 100;
        $sameStateScore = $config['same_state_score'] ?? 70;

        $memberCity = strtolower(trim($member->city ?? ''));
        $memberState = strtolower(trim($member->state ?? ''));
        $clusterLocation = strtolower(trim($cluster->meeting_location ?? ''));

        // If no location data available
        if (empty($memberCity) && empty($memberState)) {
            return 50; // Neutral score
        }

        if (empty($clusterLocation)) {
            return 50; // Neutral score
        }

        // Check for city match in meeting location
        if (! empty($memberCity) && str_contains($clusterLocation, $memberCity)) {
            return $sameCityScore;
        }

        // Check for state match in meeting location
        if (! empty($memberState) && str_contains($clusterLocation, $memberState)) {
            return $sameStateScore;
        }

        // Check cluster leader's location
        $leader = $cluster->leader;
        if ($leader) {
            $leaderCity = strtolower(trim($leader->city ?? ''));
            $leaderState = strtolower(trim($leader->state ?? ''));

            if (! empty($memberCity) && $memberCity === $leaderCity) {
                return $sameCityScore;
            }

            if (! empty($memberState) && $memberState === $leaderState) {
                return $sameStateScore;
            }
        }

        // Check other cluster members' locations
        $clusterMembers = $cluster->members()->limit(10)->get();
        $sameCityMembers = 0;
        $sameStateMembers = 0;

        foreach ($clusterMembers as $clusterMember) {
            $cmCity = strtolower(trim($clusterMember->city ?? ''));
            $cmState = strtolower(trim($clusterMember->state ?? ''));

            if (! empty($memberCity) && $memberCity === $cmCity) {
                $sameCityMembers++;
            } elseif (! empty($memberState) && $memberState === $cmState) {
                $sameStateMembers++;
            }
        }

        $totalChecked = $clusterMembers->count();
        if ($totalChecked > 0) {
            $cityRatio = $sameCityMembers / $totalChecked;
            $stateRatio = $sameStateMembers / $totalChecked;

            if ($cityRatio >= 0.3) {
                return $sameCityScore * $cityRatio + 30;
            }
            if ($stateRatio >= 0.3) {
                return $sameStateScore * $stateRatio + 20;
            }
        }

        return 40; // Low location match
    }

    /**
     * Calculate demographics score based on age, marital status, employment.
     */
    protected function calculateDemographicsScore(Member $member, Cluster $cluster, array $config): float
    {
        $ageBracketYears = $config['age_bracket_years'] ?? 10;
        $score = 50; // Base score

        $clusterMembers = $cluster->members()->limit(20)->get();

        if ($clusterMembers->isEmpty()) {
            return 50; // Neutral for empty cluster
        }

        // Age matching
        $memberAge = $member->date_of_birth
            ? Carbon::parse($member->date_of_birth)->age
            : null;

        if ($memberAge !== null) {
            $ageMatches = 0;
            $ageChecked = 0;

            foreach ($clusterMembers as $cm) {
                if ($cm->date_of_birth) {
                    $cmAge = Carbon::parse($cm->date_of_birth)->age;
                    $ageChecked++;

                    if (abs($memberAge - $cmAge) <= $ageBracketYears) {
                        $ageMatches++;
                    }
                }
            }

            if ($ageChecked > 0) {
                $ageMatchRatio = $ageMatches / $ageChecked;
                if ($ageMatchRatio >= 0.5) {
                    $score += 25; // Strong age match
                } elseif ($ageMatchRatio >= 0.25) {
                    $score += 15; // Moderate age match
                }
            }
        }

        // Marital status matching
        $memberMarital = $member->marital_status?->value;
        if ($memberMarital) {
            $maritalCounts = [];
            foreach ($clusterMembers as $cm) {
                $status = $cm->marital_status?->value;
                if ($status) {
                    $maritalCounts[$status] = ($maritalCounts[$status] ?? 0) + 1;
                }
            }

            $totalMarital = array_sum($maritalCounts);
            if ($totalMarital > 0 && isset($maritalCounts[$memberMarital])) {
                $maritalRatio = $maritalCounts[$memberMarital] / $totalMarital;
                if ($maritalRatio >= 0.4) {
                    $score += 15; // Strong marital match
                } elseif ($maritalRatio >= 0.2) {
                    $score += 8; // Moderate marital match
                }
            }
        }

        // Employment status matching
        $memberEmployment = $member->employment_status?->value;
        if ($memberEmployment) {
            $employmentCounts = [];
            foreach ($clusterMembers as $cm) {
                $status = $cm->employment_status?->value;
                if ($status) {
                    $employmentCounts[$status] = ($employmentCounts[$status] ?? 0) + 1;
                }
            }

            $totalEmployment = array_sum($employmentCounts);
            if ($totalEmployment > 0 && isset($employmentCounts[$memberEmployment])) {
                $employmentRatio = $employmentCounts[$memberEmployment] / $totalEmployment;
                if ($employmentRatio >= 0.3) {
                    $score += 10;
                }
            }
        }

        return min(100, $score);
    }

    /**
     * Calculate capacity score (clusters with more space score higher).
     */
    protected function calculateCapacityScore(Cluster $cluster): float
    {
        $currentMembers = $cluster->members_count ?? $cluster->members()->count();
        $capacity = $cluster->capacity;

        if ($capacity === null || $capacity === 0) {
            // No capacity limit, give good score
            return 80;
        }

        $usageRate = $currentMembers / $capacity;

        return match (true) {
            $usageRate >= 1.0 => 0, // Full
            $usageRate >= 0.9 => 25, // Nearly full
            $usageRate >= 0.75 => 50, // 75-90% full
            $usageRate >= 0.5 => 75, // 50-75% full
            default => 100, // Less than 50% full
        };
    }

    /**
     * Calculate health score from cluster's existing health data.
     */
    protected function calculateHealthScore(Cluster $cluster): float
    {
        // Use the cluster's pre-calculated health score
        if ($cluster->health_score !== null) {
            return (float) $cluster->health_score;
        }

        // Derive from health level if available
        if ($cluster->health_level !== null) {
            return match ($cluster->health_level) {
                ClusterHealthLevel::Thriving => 90,
                ClusterHealthLevel::Healthy => 75,
                ClusterHealthLevel::Stable => 55,
                ClusterHealthLevel::Struggling => 35,
                ClusterHealthLevel::Critical => 15,
                default => 50,
            };
        }

        // Default neutral score
        return 50;
    }

    /**
     * Calculate lifecycle compatibility score.
     */
    protected function calculateLifecycleScore(Member $member, Cluster $cluster): float
    {
        $memberStage = $member->lifecycle_stage;
        $clusterMembers = $cluster->members()->limit(20)->get();

        if ($clusterMembers->isEmpty()) {
            // Empty cluster - good for new members
            return $memberStage === LifecycleStage::NewMember ? 80 : 60;
        }

        // Count lifecycle stages in cluster
        $stageCounts = [];
        foreach ($clusterMembers as $cm) {
            $stage = $cm->lifecycle_stage?->value ?? 'unknown';
            $stageCounts[$stage] = ($stageCounts[$stage] ?? 0) + 1;
        }

        // Calculate cluster's overall engagement level
        $engagedCount = ($stageCounts[LifecycleStage::Engaged->value] ?? 0) +
                        ($stageCounts[LifecycleStage::Growing->value] ?? 0);
        $totalCount = $clusterMembers->count();
        $engagementRatio = $totalCount > 0 ? $engagedCount / $totalCount : 0;

        // Score based on member's stage and cluster composition
        return match ($memberStage) {
            LifecycleStage::NewMember => $engagementRatio >= 0.5 ? 90 : 70, // New members benefit from engaged clusters
            LifecycleStage::Growing => $engagementRatio >= 0.4 ? 85 : 65,
            LifecycleStage::Engaged => 80, // Engaged members fit anywhere
            LifecycleStage::Prospect => $engagementRatio >= 0.6 ? 85 : 60, // Prospects need welcoming clusters
            LifecycleStage::Disengaging => 70, // Could benefit from any supportive cluster
            LifecycleStage::AtRisk => 65, // Needs personal attention
            LifecycleStage::Dormant => 55,
            LifecycleStage::Inactive => 40,
            default => 50,
        };
    }

    /**
     * Generate human-readable match reasons based on scores.
     *
     * @return array<string>
     */
    protected function generateMatchReasons(array $scores, Member $member, Cluster $cluster, array $config): array
    {
        $reasons = [];

        // Location reasons
        if ($scores['location'] >= 80) {
            $reasons[] = 'Same City';
        } elseif ($scores['location'] >= 60) {
            $reasons[] = 'Same State';
        } elseif ($scores['location'] >= 50) {
            $reasons[] = 'Nearby';
        }

        // Demographics reasons
        if ($scores['demographics'] >= 80) {
            $reasons[] = 'Age Match';
        } elseif ($scores['demographics'] >= 65) {
            $reasons[] = 'Similar Demographics';
        }

        // Capacity reasons
        if ($scores['capacity'] >= 80) {
            $reasons[] = 'Has Space';
        } elseif ($scores['capacity'] >= 50) {
            $reasons[] = 'Room Available';
        }

        // Health reasons
        if ($scores['health'] >= 75) {
            $reasons[] = 'Healthy Group';
        } elseif ($scores['health'] >= 55) {
            $reasons[] = 'Active Group';
        }

        // Lifecycle reasons
        if ($scores['lifecycle'] >= 80) {
            $reasons[] = 'Good Fit';
        } elseif ($scores['lifecycle'] >= 65) {
            $reasons[] = 'Compatible Stage';
        }

        // Add meeting-related reasons
        if ($cluster->meeting_day && $cluster->meeting_time) {
            $reasons[] = 'Regular Meetings';
        }

        return array_slice($reasons, 0, 4);
    }

    /**
     * Get recommendations for multiple members (batch operation).
     *
     * @return array<string, array<ClusterRecommendation>>
     */
    public function getRecommendationsForMembers(Collection $members, int $limit = 3): array
    {
        $results = [];

        foreach ($members as $member) {
            $results[$member->id] = $this->getRecommendations($member, $limit);
        }

        return $results;
    }
}
