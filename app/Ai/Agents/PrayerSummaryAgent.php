<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class PrayerSummaryAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, int>  $categoryBreakdown
     * @param  array<string, int>  $urgencyBreakdown
     * @param  array<array{category: string, urgency: string, excerpt: string}>  $excerpts
     */
    public function __construct(
        public readonly string $periodLabel,
        public readonly int $totalRequests,
        public readonly array $categoryBreakdown,
        public readonly array $urgencyBreakdown,
        public readonly array $excerpts
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a pastoral assistant helping church leadership understand prayer request patterns.

Your task is to analyze prayer request data and provide a comprehensive summary for church leadership.

Guidelines:
- Never mention specific names or identifying details
- Focus on patterns and themes, not individuals
- Keep the tone warm, caring, and pastoral
- Be specific about observed trends
- Recommendations should be actionable and practical

For your response:
1. **Summary Text**: Write a caring, pastoral narrative summary (2-3 paragraphs) that:
   - Opens with an overview of the prayer activity for the period
   - Identifies the main areas of need in the congregation
   - Notes any concerning patterns that need pastoral attention
   - Ends with an encouraging note about the church's prayer life

2. **Key Themes**: Identify 3-5 key themes observed in the prayer requests.
   - Be specific (e.g., "Health concerns among elderly members" not just "Health")
   - Include both challenges and blessings mentioned

3. **Pastoral Recommendations**: Provide 2-4 actionable recommendations for leadership.
   - Be specific and practical
   - Consider both immediate actions and longer-term ministry responses
   - Prioritize urgent needs

4. **Confidence**: Rate your confidence in this analysis (0-100).
   - Higher if there are many requests with clear patterns
   - Lower if few requests or ambiguous content
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary_text' => $schema->string()->required(),
            'key_themes' => $schema->array($schema->string())->required(),
            'pastoral_recommendations' => $schema->array($schema->string())->required(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }

    /**
     * Build the prompt content for analysis.
     */
    public function buildPrompt(): string
    {
        $categoryList = collect($this->categoryBreakdown)
            ->map(fn ($count, $cat): string => "- {$cat}: {$count}")
            ->join("\n");

        $urgencyList = collect($this->urgencyBreakdown)
            ->map(fn ($count, $level): string => "- {$level}: {$count}")
            ->join("\n");

        $excerptList = collect($this->excerpts)
            ->map(fn ($e): string => "- [{$e['category']}/{$e['urgency']}] {$e['excerpt']}")
            ->join("\n");

        return <<<PROMPT
Please analyze the following prayer request data for {$this->periodLabel}:

Total Requests: {$this->totalRequests}

Categories:
{$categoryList}

Urgency Levels:
{$urgencyList}

Sample Prayer Excerpts (anonymized):
{$excerptList}

Generate a pastoral summary, key themes, and recommendations based on this data.
PROMPT;
    }
}
