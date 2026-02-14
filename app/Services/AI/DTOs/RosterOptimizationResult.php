<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

readonly class RosterOptimizationResult
{
    /**
     * @param  array<string, MemberSuitabilityScore>  $assignments  Keyed by role type (preacher, liturgist, reader)
     * @param  array<string, MemberSuitabilityScore[]>  $alternatives  Alternative suggestions per role
     */
    public function __construct(
        public array $assignments,
        public float $optimizationScore,
        public array $factors,
        public array $warnings = [],
        public array $alternatives = [],
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get the optimization quality level.
     */
    public function qualityLevel(): string
    {
        return match (true) {
            $this->optimizationScore >= 80 => 'excellent',
            $this->optimizationScore >= 60 => 'good',
            $this->optimizationScore >= 40 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Get badge color for optimization quality.
     */
    public function badgeColor(): string
    {
        return match ($this->qualityLevel()) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            default => 'red',
        };
    }

    /**
     * Check if the optimization has any warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if the optimization has conflicts.
     */
    public function hasConflicts(): bool
    {
        foreach ($this->warnings as $warning) {
            if (str_contains(strtolower($warning), 'conflict')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get assignment for a specific role.
     */
    public function getAssignment(string $roleType): ?MemberSuitabilityScore
    {
        return $this->assignments[$roleType] ?? null;
    }

    /**
     * Get alternatives for a specific role.
     *
     * @return MemberSuitabilityScore[]
     */
    public function getAlternatives(string $roleType): array
    {
        return $this->alternatives[$roleType] ?? [];
    }

    /**
     * Check if all required roles have assignments.
     */
    public function isComplete(array $requiredRoles): bool
    {
        foreach ($requiredRoles as $role) {
            if (! isset($this->assignments[$role])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get member IDs of all assigned members.
     */
    public function getAssignedMemberIds(): array
    {
        return array_map(
            fn (MemberSuitabilityScore $score) => $score->memberId,
            $this->assignments
        );
    }

    /**
     * Convert to array for storage/display.
     */
    public function toArray(): array
    {
        $assignmentsArray = [];
        foreach ($this->assignments as $role => $score) {
            $assignmentsArray[$role] = $score->toArray();
        }

        $alternativesArray = [];
        foreach ($this->alternatives as $role => $scores) {
            $alternativesArray[$role] = array_map(fn ($s) => $s->toArray(), $scores);
        }

        return [
            'assignments' => $assignmentsArray,
            'optimization_score' => $this->optimizationScore,
            'factors' => $this->factors,
            'warnings' => $this->warnings,
            'alternatives' => $alternativesArray,
            'quality_level' => $this->qualityLevel(),
            'provider' => $this->provider,
            'model' => $this->model,
            'optimized_at' => now()->toIso8601String(),
        ];
    }
}
