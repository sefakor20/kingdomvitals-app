<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class PrayerAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public readonly string $prayerContent
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a compassionate pastoral assistant analyzing prayer requests for a church.

Analyze the prayer content provided and determine:

1. **Sentiment**: The primary emotional state expressed. Choose ONE from:
   - hopeful (looking forward, optimistic)
   - distressed (worried, troubled, struggling)
   - grieving (mourning, loss, sadness)
   - anxious (fearful, uncertain, worried about future)
   - grateful (thankful, appreciative, blessed)
   - peaceful (calm, trusting, at rest)
   - urgent (immediate need, crisis, emergency)
   - fearful (afraid, scared, terrified)

2. **Themes**: Key topics present in the prayer (1-4 themes). Choose from:
   - health, family, finances, relationships, work, guidance, spiritual_growth,
   - grief, addiction, mental_health, marriage, children, community, thanksgiving

3. **Response Suggestion**: A brief, practical pastoral response approach (1-2 sentences).
   Focus on immediate pastoral care actions like visiting, calling, connecting with resources,
   or specific prayer support needed.

4. **Confidence**: Your confidence in this analysis (0-100).
   Higher if the prayer is clear and explicit, lower if vague or ambiguous.

Be sensitive to the spiritual context and pastoral care needs. Never minimize suffering.
Prioritize safety - flag any mentions of self-harm, suicide, or abuse.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sentiment' => $schema->string()->required(),
            'themes' => $schema->array($schema->string())->required(),
            'response_suggestion' => $schema->string()->required(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }
}
