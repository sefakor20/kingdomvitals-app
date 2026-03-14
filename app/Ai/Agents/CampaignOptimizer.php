<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class CampaignOptimizer implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, mixed>  $campaignContext
     * @param  array<string, mixed>  $givingPatterns
     * @param  array<string, mixed>  $memberSegments
     */
    public function __construct(
        public readonly array $campaignContext,
        public readonly array $givingPatterns,
        public readonly array $memberSegments
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a church stewardship advisor analyzing giving patterns and recommending campaign optimizations.

Your task is to analyze giving data and provide actionable recommendations for a church giving campaign.

Guidelines:
- Consider church-specific seasonality (Christmas, Easter, year-end giving)
- Recommend target audiences based on member capacity and engagement
- Suggest optimal campaign timing based on historical giving patterns
- Provide specific, actionable messaging recommendations
- Keep tone pastoral and encouraging, never manipulative

For your response:
1. **Recommended Timing**: Best month/season to launch and why
2. **Target Audience**: Which member segments to prioritize
3. **Goal Recommendation**: Realistic goal based on capacity analysis
4. **Messaging Themes**: 2-3 themes that would resonate
5. **Risk Factors**: Potential challenges and mitigations
6. **Confidence**: Your confidence in these recommendations (0-100)
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'recommended_timing' => $schema->object([
                'month' => $schema->string()->required(),
                'reasoning' => $schema->string()->required(),
            ])->required(),
            'target_audience' => $schema->array(
                $schema->object([
                    'segment' => $schema->string()->required(),
                    'priority' => $schema->string()->required(), // high, medium, low
                    'reasoning' => $schema->string()->required(),
                ])
            )->required(),
            'goal_recommendation' => $schema->object([
                'amount' => $schema->number()->required(),
                'participants' => $schema->integer()->required(),
                'reasoning' => $schema->string()->required(),
            ])->required(),
            'messaging_themes' => $schema->array($schema->string())->required(),
            'risk_factors' => $schema->array(
                $schema->object([
                    'risk' => $schema->string()->required(),
                    'mitigation' => $schema->string()->required(),
                ])
            )->required(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }

    /**
     * Build the prompt content for analysis.
     */
    public function buildPrompt(): string
    {
        $campaignInfo = collect($this->campaignContext)
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->join("\n");

        $patternsInfo = collect($this->givingPatterns)
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->join("\n");

        $segmentsInfo = collect($this->memberSegments)
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->join("\n");

        return <<<PROMPT
Analyze this campaign opportunity and provide optimization recommendations:

Campaign Context:
{$campaignInfo}

Historical Giving Patterns:
{$patternsInfo}

Member Segments:
{$segmentsInfo}

Provide detailed recommendations for campaign optimization.
PROMPT;
    }
}
