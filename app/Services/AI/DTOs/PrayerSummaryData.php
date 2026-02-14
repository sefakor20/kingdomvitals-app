<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

use Carbon\Carbon;

readonly class PrayerSummaryData
{
    /**
     * @param  array<string, int>  $categoryBreakdown
     * @param  array<string, int>  $urgencyBreakdown
     * @param  array<string>  $keyThemes
     * @param  array<string>  $pastoralRecommendations
     */
    public function __construct(
        public string $periodType,
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public array $categoryBreakdown,
        public array $urgencyBreakdown,
        public string $summaryText,
        public array $keyThemes,
        public array $pastoralRecommendations,
        public int $totalRequests,
        public int $answeredRequests,
        public int $criticalRequests,
        public string $provider = 'heuristic',
        public string $model = 'v1',
    ) {}

    /**
     * Get answer rate as percentage.
     */
    public function answerRate(): float
    {
        if ($this->totalRequests === 0) {
            return 0.0;
        }

        return round(($this->answeredRequests / $this->totalRequests) * 100, 1);
    }

    /**
     * Get the top category by count.
     */
    public function topCategory(): ?string
    {
        if (empty($this->categoryBreakdown)) {
            return null;
        }

        $sorted = $this->categoryBreakdown;
        arsort($sorted);

        return array_key_first($sorted);
    }

    /**
     * Get the dominant urgency level.
     */
    public function dominantUrgency(): ?string
    {
        if (empty($this->urgencyBreakdown)) {
            return null;
        }

        $sorted = $this->urgencyBreakdown;
        arsort($sorted);

        return array_key_first($sorted);
    }

    /**
     * Check if there are critical requests that need attention.
     */
    public function hasCriticalRequests(): bool
    {
        return $this->criticalRequests > 0;
    }

    /**
     * Get period label for display.
     */
    public function periodLabel(): string
    {
        if ($this->periodType === 'weekly') {
            return $this->periodStart->format('M j').' - '.$this->periodEnd->format('M j, Y');
        }

        return $this->periodStart->format('F Y');
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'period_type' => $this->periodType,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'category_breakdown' => $this->categoryBreakdown,
            'urgency_breakdown' => $this->urgencyBreakdown,
            'summary_text' => $this->summaryText,
            'key_themes' => $this->keyThemes,
            'pastoral_recommendations' => $this->pastoralRecommendations,
            'total_requests' => $this->totalRequests,
            'answered_requests' => $this->answeredRequests,
            'critical_requests' => $this->criticalRequests,
            'provider' => $this->provider,
            'model' => $this->model,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create from stored array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            periodType: $data['period_type'],
            periodStart: Carbon::parse($data['period_start']),
            periodEnd: Carbon::parse($data['period_end']),
            categoryBreakdown: $data['category_breakdown'] ?? [],
            urgencyBreakdown: $data['urgency_breakdown'] ?? [],
            summaryText: $data['summary_text'] ?? '',
            keyThemes: $data['key_themes'] ?? [],
            pastoralRecommendations: $data['pastoral_recommendations'] ?? [],
            totalRequests: (int) ($data['total_requests'] ?? 0),
            answeredRequests: (int) ($data['answered_requests'] ?? 0),
            criticalRequests: (int) ($data['critical_requests'] ?? 0),
            provider: $data['provider'] ?? 'heuristic',
            model: $data['model'] ?? 'v1',
        );
    }
}
