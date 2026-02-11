<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\PrayerRequest;
use App\Services\AI\DTOs\PrayerAnalysis;
use Illuminate\Support\Collection;

class PrayerAnalysisService
{
    /**
     * Critical urgency patterns (immediate intervention needed).
     */
    protected array $criticalPatterns = [
        '/\b(suicide|suicidal|kill myself|end my life|want to die)\b/i',
        '/\bdon\'t want to (live|be here|go on)\b/i',
        '/\b(self[- ]?harm|hurt myself|cutting)\b/i',
        '/\b(overdose|ending it all)\b/i',
    ];

    /**
     * High urgency patterns (significant crisis).
     */
    protected array $highPatterns = [
        '/\b(hospital|hospitalized|icu|intensive care|emergency room|er visit)\b/i',
        '/\b(cancer|tumor|terminal|dying|passed away|death)\b/i',
        '/\b(divorce|separated|custody|abuse|domestic violence)\b/i',
        '/\b(accident|crash|severely injured|critical condition)\b/i',
        '/\b(heart attack|stroke|seizure|coma)\b/i',
    ];

    /**
     * Elevated urgency patterns (concerning situations).
     */
    protected array $elevatedPatterns = [
        '/\b(depression|depressed|anxious|anxiety|struggling|overwhelmed)\b/i',
        '/\b(job loss|unemployed|laid off|fired|evicted)\b/i',
        '/\b(surgery|procedure|treatment|therapy|counseling)\b/i',
        '/\b(miscarriage|infertility|stillborn)\b/i',
        '/\b(addiction|relapse|alcoholic|drug)\b/i',
        '/\b(illness|sick|disease|diagnosis)\b/i',
    ];

    /**
     * Category keyword mappings.
     */
    protected array $categoryKeywords = [
        'health' => ['sick', 'illness', 'hospital', 'surgery', 'doctor', 'cancer', 'pain', 'treatment', 'diagnosis', 'medical', 'health', 'disease', 'recovery', 'healing'],
        'family' => ['family', 'marriage', 'children', 'spouse', 'parents', 'kids', 'husband', 'wife', 'son', 'daughter', 'mother', 'father', 'sibling', 'brother', 'sister'],
        'finances' => ['job', 'money', 'bills', 'rent', 'mortgage', 'unemployed', 'debt', 'financial', 'income', 'salary', 'afford', 'budget', 'payment'],
        'grief' => ['passed', 'death', 'died', 'loss', 'funeral', 'grieving', 'mourning', 'memorial', 'deceased', 'heaven', 'lost'],
        'guidance' => ['decision', 'direction', 'wisdom', 'discernment', 'path', 'future', 'choices', 'guidance', 'purpose', 'calling', 'unclear'],
        'relationships' => ['relationship', 'friend', 'conflict', 'forgiveness', 'reconciliation', 'broken', 'trust', 'friendship', 'dating', 'loneliness'],
        'spiritual' => ['faith', 'closer to god', 'spiritual', 'prayer life', 'growth', 'salvation', 'believe', 'doubt', 'church', 'worship', 'bible'],
        'work' => ['work', 'career', 'boss', 'coworker', 'promotion', 'business', 'workplace', 'office', 'colleagues', 'employment'],
        'thanksgiving' => ['thank', 'grateful', 'praise', 'answered', 'blessing', 'thankful', 'gratitude', 'appreciate', 'joyful', 'celebration'],
    ];

    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Analyze a prayer request and return full analysis.
     */
    public function analyze(PrayerRequest $prayer): PrayerAnalysis
    {
        $content = $this->getAnalyzableContent($prayer);

        // Detect urgency
        $urgencyResult = $this->detectUrgency($content);

        // Suggest category
        $categoryResult = $this->suggestCategory($content);

        // Calculate priority
        $priorityScore = $this->calculatePriority($prayer, $urgencyResult['level']);

        // Build factors
        $factors = $this->buildFactors($urgencyResult, $categoryResult, $prayer);

        return new PrayerAnalysis(
            urgencyLevel: $urgencyResult['level'],
            priorityScore: $priorityScore,
            suggestedCategory: $categoryResult['category'],
            categoryConfidence: $categoryResult['confidence'],
            detectedKeywords: array_merge(
                $urgencyResult['keywords'],
                $categoryResult['keywords']
            ),
            factors: $factors,
        );
    }

    /**
     * Get combined content for analysis.
     */
    protected function getAnalyzableContent(PrayerRequest $prayer): string
    {
        return strtolower($prayer->title.' '.$prayer->description);
    }

    /**
     * Detect urgency level from content.
     */
    public function detectUrgency(string $content): array
    {
        $keywords = [];

        // Check critical patterns
        foreach ($this->criticalPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $keywords[] = $matches[0];

                return [
                    'level' => PrayerUrgencyLevel::Critical,
                    'keywords' => array_unique($keywords),
                    'matched_pattern' => 'critical',
                ];
            }
        }

        // Check high patterns
        foreach ($this->highPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $keywords[] = $matches[0];
            }
        }

        if (count($keywords) > 0) {
            return [
                'level' => PrayerUrgencyLevel::High,
                'keywords' => array_unique($keywords),
                'matched_pattern' => 'high',
            ];
        }

        // Check elevated patterns
        foreach ($this->elevatedPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $keywords[] = $matches[0];
            }
        }

        if (count($keywords) > 0) {
            return [
                'level' => PrayerUrgencyLevel::Elevated,
                'keywords' => array_unique($keywords),
                'matched_pattern' => 'elevated',
            ];
        }

        return [
            'level' => PrayerUrgencyLevel::Normal,
            'keywords' => [],
            'matched_pattern' => null,
        ];
    }

    /**
     * Suggest a category based on content.
     */
    public function suggestCategory(string $content): array
    {
        $scores = [];
        $matchedKeywords = [];

        foreach ($this->categoryKeywords as $category => $keywords) {
            $score = 0;
            $matched = [];

            foreach ($keywords as $keyword) {
                $count = substr_count($content, $keyword);
                if ($count > 0) {
                    $score += $count;
                    $matched[] = $keyword;
                }
            }

            if ($score > 0) {
                $scores[$category] = $score;
                $matchedKeywords[$category] = $matched;
            }
        }

        if (empty($scores)) {
            return [
                'category' => PrayerRequestCategory::Other,
                'confidence' => 0,
                'keywords' => [],
            ];
        }

        // Get highest scoring category
        arsort($scores);
        $topCategory = array_key_first($scores);
        $topScore = $scores[$topCategory];

        // Calculate confidence (normalize based on keyword matches)
        $totalScore = array_sum($scores);
        $confidence = min(95, ($topScore / max($totalScore, 1)) * 100);

        return [
            'category' => PrayerRequestCategory::from($topCategory),
            'confidence' => round($confidence, 1),
            'keywords' => $matchedKeywords[$topCategory] ?? [],
        ];
    }

    /**
     * Calculate priority score for a prayer request.
     */
    public function calculatePriority(PrayerRequest $prayer, ?PrayerUrgencyLevel $urgencyLevel = null): float
    {
        $config = config('ai.scoring.prayer', []);
        $score = $config['base_score'] ?? 50;

        $urgency = $urgencyLevel ?? $prayer->urgency_level ?? PrayerUrgencyLevel::Normal;

        // Urgency factor
        $score += $urgency->priorityWeight();

        // Recency factor (newer = slightly higher, max +10)
        $submittedAt = $prayer->submitted_at ?? $prayer->created_at;
        $daysSinceSubmitted = $submittedAt->diffInDays(now());
        $recencyBonus = max(0, 10 - $daysSinceSubmitted);
        $score += $recencyBonus;

        // Open duration factor (older open prayers without response = higher priority)
        if ($prayer->isOpen() && $prayer->updates->isEmpty()) {
            $openDurationBonus = min(15, $daysSinceSubmitted * 0.5);
            $score += $openDurationBonus;
        }

        // Member factor (non-anonymous prayers slightly higher)
        if (! $prayer->isAnonymous()) {
            $score += 2;
        }

        // Privacy factor (leaders-only may need more attention)
        if ($prayer->isLeadersOnly()) {
            $score += 3;
        }

        return min(100, max(0, round($score, 2)));
    }

    /**
     * Build factors explanation.
     */
    protected function buildFactors(array $urgencyResult, array $categoryResult, PrayerRequest $prayer): array
    {
        $factors = [];

        // Urgency factor
        if ($urgencyResult['level'] !== PrayerUrgencyLevel::Normal) {
            $factors['urgency'] = [
                'description' => sprintf(
                    '%s urgency detected',
                    $urgencyResult['level']->label()
                ),
                'value' => $urgencyResult['level']->priorityWeight(),
                'keywords' => $urgencyResult['keywords'],
            ];
        }

        // Category factor
        if ($categoryResult['confidence'] > 0) {
            $factors['category'] = [
                'description' => sprintf(
                    'Suggested: %s (%.0f%% confidence)',
                    $categoryResult['category']->value,
                    $categoryResult['confidence']
                ),
                'value' => $categoryResult['confidence'],
            ];
        }

        // Open duration factor
        if ($prayer->isOpen()) {
            $submittedAt = $prayer->submitted_at ?? $prayer->created_at;
            $daysSince = $submittedAt->diffInDays(now());

            if ($daysSince > 0) {
                $factors['duration'] = [
                    'description' => sprintf('Open for %d day(s)', $daysSince),
                    'value' => $daysSince,
                ];
            }
        }

        // Response factor
        if ($prayer->isOpen() && $prayer->updates->isEmpty()) {
            $factors['no_response'] = [
                'description' => 'No updates or responses yet',
                'value' => 5,
            ];
        }

        return $factors;
    }

    /**
     * Analyze multiple prayer requests in batch.
     */
    public function batchAnalyze(Collection $prayers): Collection
    {
        return $prayers->map(fn (PrayerRequest $prayer) => [
            'prayer' => $prayer,
            'analysis' => $this->analyze($prayer),
        ]);
    }

    /**
     * Update prayer request with analysis results.
     */
    public function updatePrayerWithAnalysis(PrayerRequest $prayer, PrayerAnalysis $analysis): bool
    {
        return $prayer->update([
            'urgency_level' => $analysis->urgencyLevel->value,
            'priority_score' => $analysis->priorityScore,
            'ai_classification' => $analysis->toArray(),
            'ai_analyzed_at' => now(),
        ]);
    }

    /**
     * Check if the feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.prayer_analysis.enabled', false);
    }

    /**
     * Check if auto-analysis is enabled.
     */
    public function isAutoAnalyzeEnabled(): bool
    {
        return $this->isEnabled() && config('ai.features.prayer_analysis.auto_analyze', true);
    }

    /**
     * Check if critical notifications are enabled.
     */
    public function shouldNotifyOnCritical(): bool
    {
        return $this->isEnabled() && config('ai.features.prayer_analysis.notify_on_critical', true);
    }
}
