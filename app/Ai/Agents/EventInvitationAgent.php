<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class EventInvitationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, mixed>  $memberContext
     * @param  array<string, mixed>  $eventContext
     */
    public function __construct(
        public readonly array $memberContext,
        public readonly array $eventContext,
        public readonly string $channel = 'sms'
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $charLimit = $this->channel === 'sms' ? 160 : 500;

        return <<<PROMPT
You are a friendly church communications assistant crafting personalized event invitations.

Your task is to generate a warm, personal invitation message for a church member.

Guidelines:
- Keep the tone warm, inviting, and personal (not corporate or formal)
- Use the member's first name naturally
- Reference any relevant context (their engagement level, past attendance)
- Include the event name and key details (date/time if provided)
- Include a clear call-to-action
- Keep the message under {$charLimit} characters (this is for {$this->channel})
- Do NOT use placeholder text like [Event Name] - use actual values provided
- Do NOT include URLs or links unless specifically provided
- Use natural language, not bullet points

For SMS messages:
- Be concise and direct
- Focus on the invitation, not detailed descriptions
- End with a simple action phrase

For email messages:
- Can be slightly more detailed
- Include more context about the event
- May include a warm greeting and sign-off
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->required(),
            'subject' => $schema->string()->optional(), // For email only
            'personalization_used' => $schema->array($schema->string())->required(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }

    /**
     * Build the prompt content for generation.
     */
    public function buildPrompt(): string
    {
        $memberInfo = collect($this->memberContext)
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->join("\n");

        $eventInfo = collect($this->eventContext)
            ->map(fn ($value, $key) => "- {$key}: {$value}")
            ->join("\n");

        return <<<PROMPT
Generate a personalized {$this->channel} invitation for this member:

Member Information:
{$memberInfo}

Event Information:
{$eventInfo}

Create a warm, personal invitation message.
PROMPT;
    }
}
