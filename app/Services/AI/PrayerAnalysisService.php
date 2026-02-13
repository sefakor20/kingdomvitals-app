<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PrayerRequest;
use App\Services\AI\DTOs\PrayerAnalysis;
use App\Services\AI\DTOs\PrayerSummaryData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

    /**
     * Generate a summary of prayer requests for a branch and period.
     */
    public function generateSummary(
        Branch $branch,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): PrayerSummaryData {
        // Get prayer requests for the period
        $prayers = PrayerRequest::where('branch_id', $branch->id)
            ->whereBetween('submitted_at', [$periodStart->startOfDay(), $periodEnd->endOfDay()])
            ->with('member')
            ->get();

        // Build breakdowns
        $categoryBreakdown = $this->buildCategoryBreakdown($prayers);
        $urgencyBreakdown = $this->buildUrgencyBreakdown($prayers);

        // Calculate stats
        $totalRequests = $prayers->count();
        $answeredRequests = $prayers->where('status', 'answered')->count();
        $criticalRequests = $prayers->whereIn('urgency_level', [
            PrayerUrgencyLevel::Critical,
            PrayerUrgencyLevel::High,
        ])->count();

        // Generate AI summary if we have requests
        $summaryText = '';
        $keyThemes = [];
        $pastoralRecommendations = [];
        $provider = 'heuristic';
        $model = 'v1';

        if ($totalRequests > 0) {
            $aiResult = $this->generateAiSummary(
                $prayers,
                $categoryBreakdown,
                $urgencyBreakdown,
                $periodType,
                $periodStart,
                $periodEnd
            );

            $summaryText = $aiResult['summary_text'];
            $keyThemes = $aiResult['key_themes'];
            $pastoralRecommendations = $aiResult['pastoral_recommendations'];
            $provider = $aiResult['provider'];
            $model = $aiResult['model'];
        } else {
            $summaryText = $this->generateEmptyPeriodSummary($periodType, $periodStart, $periodEnd);
        }

        return new PrayerSummaryData(
            periodType: $periodType,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            categoryBreakdown: $categoryBreakdown,
            urgencyBreakdown: $urgencyBreakdown,
            summaryText: $summaryText,
            keyThemes: $keyThemes,
            pastoralRecommendations: $pastoralRecommendations,
            totalRequests: $totalRequests,
            answeredRequests: $answeredRequests,
            criticalRequests: $criticalRequests,
            provider: $provider,
            model: $model,
        );
    }

    /**
     * Build category breakdown from prayers.
     *
     * @return array<string, int>
     */
    protected function buildCategoryBreakdown(Collection $prayers): array
    {
        $breakdown = [];

        foreach (PrayerRequestCategory::cases() as $category) {
            $count = $prayers->where('category', $category)->count();
            if ($count > 0) {
                $breakdown[$category->value] = $count;
            }
        }

        arsort($breakdown);

        return $breakdown;
    }

    /**
     * Build urgency breakdown from prayers.
     *
     * @return array<string, int>
     */
    protected function buildUrgencyBreakdown(Collection $prayers): array
    {
        $breakdown = [];

        foreach (PrayerUrgencyLevel::cases() as $level) {
            $count = $prayers->where('urgency_level', $level)->count();
            if ($count > 0) {
                $breakdown[$level->value] = $count;
            }
        }

        return $breakdown;
    }

    /**
     * Generate AI-powered summary using the LLM.
     *
     * @return array{summary_text: string, key_themes: array<string>, pastoral_recommendations: array<string>, provider: string, model: string}
     */
    protected function generateAiSummary(
        Collection $prayers,
        array $categoryBreakdown,
        array $urgencyBreakdown,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // Build context for the AI
        $periodLabel = $periodType === 'weekly'
            ? $periodStart->format('M j').' - '.$periodEnd->format('M j, Y')
            : $periodStart->format('F Y');

        // Get anonymized excerpts (remove names, limit content)
        $excerpts = $prayers->take(10)->map(function (PrayerRequest $prayer) {
            return [
                'category' => $prayer->category?->value ?? 'other',
                'urgency' => $prayer->urgency_level?->value ?? 'normal',
                'excerpt' => $this->anonymizeContent($prayer->description ?? $prayer->title),
            ];
        })->toArray();

        $systemPrompt = $this->getSummarySystemPrompt();
        $userPrompt = $this->buildSummaryUserPrompt(
            $periodLabel,
            $categoryBreakdown,
            $urgencyBreakdown,
            $excerpts,
            $prayers->count()
        );

        try {
            $response = $this->aiService->generateWithFallback($userPrompt, $systemPrompt);

            $parsed = $this->parseSummaryResponse($response->text);

            return [
                'summary_text' => $parsed['summary_text'],
                'key_themes' => $parsed['key_themes'],
                'pastoral_recommendations' => $parsed['pastoral_recommendations'],
                'provider' => $this->aiService->getProvider(),
                'model' => $this->aiService->getModel(),
            ];
        } catch (\Throwable $e) {
            Log::warning('PrayerAnalysisService: AI summary generation failed, using heuristic', [
                'error' => $e->getMessage(),
            ]);

            return $this->generateHeuristicSummary(
                $categoryBreakdown,
                $urgencyBreakdown,
                $prayers->count(),
                $periodLabel
            );
        }
    }

    /**
     * Get system prompt for summary generation.
     */
    protected function getSummarySystemPrompt(): string
    {
        return <<<'PROMPT'
You are a pastoral assistant helping church leadership understand prayer request patterns.

Your task is to analyze prayer request data and provide:
1. A caring, pastoral narrative summary (2-3 paragraphs)
2. Key themes observed (3-5 bullet points)
3. Pastoral recommendations for leadership (2-3 action items)

Guidelines:
- Never mention specific names or identifying details
- Focus on patterns and themes, not individuals
- Keep the tone warm, caring, and pastoral
- Be specific about observed trends
- Recommendations should be actionable

IMPORTANT: Return your response in this exact format:
[SUMMARY]
Your narrative summary here...

[THEMES]
- Theme 1
- Theme 2
- Theme 3

[RECOMMENDATIONS]
- Recommendation 1
- Recommendation 2
- Recommendation 3
PROMPT;
    }

    /**
     * Build user prompt for summary generation.
     */
    protected function buildSummaryUserPrompt(
        string $periodLabel,
        array $categoryBreakdown,
        array $urgencyBreakdown,
        array $excerpts,
        int $totalCount
    ): string {
        $categoryList = collect($categoryBreakdown)
            ->map(fn ($count, $cat) => "- {$cat}: {$count}")
            ->join("\n");

        $urgencyList = collect($urgencyBreakdown)
            ->map(fn ($count, $level) => "- {$level}: {$count}")
            ->join("\n");

        $excerptList = collect($excerpts)
            ->map(fn ($e) => "- [{$e['category']}/{$e['urgency']}] {$e['excerpt']}")
            ->join("\n");

        return <<<PROMPT
Please analyze the following prayer request data for {$periodLabel}:

Total Requests: {$totalCount}

Categories:
{$categoryList}

Urgency Levels:
{$urgencyList}

Sample Prayer Excerpts (anonymized):
{$excerptList}

Generate a pastoral summary, key themes, and recommendations.
PROMPT;
    }

    /**
     * Parse the AI response into structured data.
     *
     * @return array{summary_text: string, key_themes: array<string>, pastoral_recommendations: array<string>}
     */
    protected function parseSummaryResponse(string $response): array
    {
        $summaryText = '';
        $keyThemes = [];
        $pastoralRecommendations = [];

        // Extract summary section
        if (preg_match('/\[SUMMARY\]\s*(.*?)(?=\[THEMES\]|\[RECOMMENDATIONS\]|$)/s', $response, $matches)) {
            $summaryText = trim($matches[1]);
        }

        // Extract themes section
        if (preg_match('/\[THEMES\]\s*(.*?)(?=\[RECOMMENDATIONS\]|$)/s', $response, $matches)) {
            $themesText = trim($matches[1]);
            preg_match_all('/^[-*]\s*(.+)$/m', $themesText, $themeMatches);
            $keyThemes = array_map('trim', $themeMatches[1] ?? []);
        }

        // Extract recommendations section
        if (preg_match('/\[RECOMMENDATIONS\]\s*(.*?)$/s', $response, $matches)) {
            $recsText = trim($matches[1]);
            preg_match_all('/^[-*]\s*(.+)$/m', $recsText, $recMatches);
            $pastoralRecommendations = array_map('trim', $recMatches[1] ?? []);
        }

        // Fallback if parsing failed
        if (empty($summaryText)) {
            $summaryText = $response;
        }

        return [
            'summary_text' => $summaryText,
            'key_themes' => array_slice($keyThemes, 0, 5),
            'pastoral_recommendations' => array_slice($pastoralRecommendations, 0, 3),
        ];
    }

    /**
     * Generate heuristic summary when AI is unavailable.
     *
     * @return array{summary_text: string, key_themes: array<string>, pastoral_recommendations: array<string>, provider: string, model: string}
     */
    protected function generateHeuristicSummary(
        array $categoryBreakdown,
        array $urgencyBreakdown,
        int $totalCount,
        string $periodLabel
    ): array {
        $topCategories = array_slice(array_keys($categoryBreakdown), 0, 3);
        $hasUrgent = isset($urgencyBreakdown['critical']) || isset($urgencyBreakdown['high']);

        $summaryParts = [];
        $summaryParts[] = "During {$periodLabel}, the congregation submitted {$totalCount} prayer request".($totalCount !== 1 ? 's' : '').'.';

        if (! empty($topCategories)) {
            $categoryLabels = array_map(fn ($c) => ucfirst($c), $topCategories);
            $summaryParts[] = 'The most common themes were '.$this->formatList($categoryLabels).'.';
        }

        if ($hasUrgent) {
            $urgentCount = ($urgencyBreakdown['critical'] ?? 0) + ($urgencyBreakdown['high'] ?? 0);
            $summaryParts[] = 'There '.($urgentCount === 1 ? 'was' : 'were')." {$urgentCount} urgent request".($urgentCount !== 1 ? 's' : '').' requiring pastoral attention.';
        }

        $keyThemes = array_map(fn ($c) => 'Prayer needs related to '.strtolower($c), $topCategories);

        $recommendations = [];
        if ($hasUrgent) {
            $recommendations[] = 'Review and follow up on urgent prayer requests promptly.';
        }
        if (in_array('health', $topCategories)) {
            $recommendations[] = 'Consider organizing a healing prayer service.';
        }
        if (in_array('family', $topCategories)) {
            $recommendations[] = 'Plan family-focused ministry activities.';
        }
        if (empty($recommendations)) {
            $recommendations[] = 'Continue regular prayer ministry activities.';
        }

        return [
            'summary_text' => implode(' ', $summaryParts),
            'key_themes' => $keyThemes,
            'pastoral_recommendations' => $recommendations,
            'provider' => 'heuristic',
            'model' => 'v1',
        ];
    }

    /**
     * Generate summary text for empty period.
     */
    protected function generateEmptyPeriodSummary(string $periodType, Carbon $periodStart, Carbon $periodEnd): string
    {
        $periodLabel = $periodType === 'weekly'
            ? $periodStart->format('M j').' - '.$periodEnd->format('M j, Y')
            : $periodStart->format('F Y');

        return "No prayer requests were submitted during {$periodLabel}. Consider encouraging the congregation to share their prayer needs.";
    }

    /**
     * Anonymize prayer content by removing potential identifying information.
     */
    protected function anonymizeContent(string $content): string
    {
        // Truncate to reasonable length
        $content = mb_substr($content, 0, 150);

        // Remove common name patterns (Mr., Mrs., names followed by 's)
        $content = preg_replace('/\b(Mr\.|Mrs\.|Ms\.|Dr\.)\s*\w+/i', '[person]', $content);
        $content = preg_replace('/\b[A-Z][a-z]+\'s\b/', "[person]'s", $content);

        // Remove potential phone numbers
        $content = preg_replace('/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[phone]', $content);

        // Remove email-like patterns
        $content = preg_replace('/\b[\w.+-]+@[\w.-]+\.\w+\b/', '[email]', $content);

        return trim($content).(mb_strlen($content) >= 150 ? '...' : '');
    }

    /**
     * Format a list of items with proper grammar.
     */
    protected function formatList(array $items): string
    {
        if (count($items) === 0) {
            return '';
        }
        if (count($items) === 1) {
            return $items[0];
        }
        if (count($items) === 2) {
            return $items[0].' and '.$items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items).', and '.$last;
    }
}
