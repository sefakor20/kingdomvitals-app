<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

readonly class ClusterRecommendation
{
    /**
     * @param  array<string>  $matchReasons  Human-readable reasons for the recommendation
     */
    public function __construct(
        public string $clusterId,
        public string $clusterName,
        public string $clusterType,
        public float $overallScore,
        public float $locationScore,
        public float $demographicsScore,
        public float $capacityScore,
        public float $healthScore,
        public float $lifecycleScore,
        public array $matchReasons,
        public int $currentMembers,
        public ?int $capacity,
        public ?string $meetingDay,
        public ?string $meetingTime,
        public ?string $meetingLocation,
    ) {}

    /**
     * Get the overall score as a percentage integer.
     */
    public function scorePercentage(): int
    {
        return (int) round($this->overallScore);
    }

    /**
     * Get the top match reasons.
     *
     * @return array<string>
     */
    public function topMatchReasons(int $limit = 3): array
    {
        return array_slice($this->matchReasons, 0, $limit);
    }

    /**
     * Check if the cluster has capacity for new members.
     */
    public function hasCapacity(): bool
    {
        if ($this->capacity === null) {
            return true;
        }

        return $this->currentMembers < $this->capacity;
    }

    /**
     * Get capacity as a formatted string.
     */
    public function capacityLabel(): string
    {
        if ($this->capacity === null) {
            return (string) $this->currentMembers;
        }

        return "{$this->currentMembers}/{$this->capacity}";
    }

    /**
     * Get capacity percentage.
     */
    public function capacityPercentage(): ?int
    {
        if ($this->capacity === null || $this->capacity === 0) {
            return null;
        }

        return (int) round(($this->currentMembers / $this->capacity) * 100);
    }

    /**
     * Get the badge color based on the score.
     */
    public function scoreBadgeColor(): string
    {
        return match (true) {
            $this->overallScore >= 80 => 'green',
            $this->overallScore >= 60 => 'amber',
            $this->overallScore >= 40 => 'zinc',
            default => 'red',
        };
    }

    /**
     * Get formatted meeting info.
     */
    public function meetingInfo(): ?string
    {
        $parts = array_filter([
            $this->meetingDay,
            $this->meetingTime,
        ]);

        return empty($parts) ? null : implode(' at ', $parts);
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cluster_id' => $this->clusterId,
            'cluster_name' => $this->clusterName,
            'cluster_type' => $this->clusterType,
            'overall_score' => $this->overallScore,
            'location_score' => $this->locationScore,
            'demographics_score' => $this->demographicsScore,
            'capacity_score' => $this->capacityScore,
            'health_score' => $this->healthScore,
            'lifecycle_score' => $this->lifecycleScore,
            'match_reasons' => $this->matchReasons,
            'current_members' => $this->currentMembers,
            'capacity' => $this->capacity,
            'meeting_day' => $this->meetingDay,
            'meeting_time' => $this->meetingTime,
            'meeting_location' => $this->meetingLocation,
            'score_percentage' => $this->scorePercentage(),
            'has_capacity' => $this->hasCapacity(),
            'capacity_label' => $this->capacityLabel(),
        ];
    }

    /**
     * Create from stored array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'],
            clusterName: $data['cluster_name'] ?? '',
            clusterType: $data['cluster_type'] ?? '',
            overallScore: (float) ($data['overall_score'] ?? 0),
            locationScore: (float) ($data['location_score'] ?? 0),
            demographicsScore: (float) ($data['demographics_score'] ?? 0),
            capacityScore: (float) ($data['capacity_score'] ?? 0),
            healthScore: (float) ($data['health_score'] ?? 0),
            lifecycleScore: (float) ($data['lifecycle_score'] ?? 0),
            matchReasons: $data['match_reasons'] ?? [],
            currentMembers: (int) ($data['current_members'] ?? 0),
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
            meetingDay: $data['meeting_day'] ?? null,
            meetingTime: $data['meeting_time'] ?? null,
            meetingLocation: $data['meeting_location'] ?? null,
        );
    }
}
