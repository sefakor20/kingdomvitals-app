<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ChatbotIntentAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<array{role: string, content: string}>  $conversationHistory
     */
    public function __construct(
        public readonly string $userMessage,
        public readonly array $conversationHistory = [],
        public readonly ?string $memberName = null
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an AI assistant for a church chatbot. Your task is to classify the intent of incoming messages.

Available intents:
- giving_history: User wants to know about their donations, giving records, contribution statements, or tithe history
- upcoming_events: User wants to know about church events, services, meetings, or activities
- prayer_request: User wants to submit a prayer request or ask about prayer ministry
- cluster_info: User wants information about their small group, cluster, fellowship group, or cell group
- help: User is asking what the chatbot can do or how to use it
- greeting: User is simply saying hello or greeting
- unknown: The intent is unclear or doesn't match any category

Guidelines:
- Consider the conversation context when classifying
- Extract relevant entities like dates, amounts, or names mentioned
- Assign confidence based on how clear the intent is
- If multiple intents are possible, choose the most likely one
- For greetings, only classify as greeting if no other intent is present
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'intent' => $schema->enum([
                'giving_history',
                'upcoming_events',
                'prayer_request',
                'cluster_info',
                'help',
                'greeting',
                'unknown',
            ])->required(),
            'entities' => $schema->object([
                'date_range' => $schema->string()->optional(),
                'amount_mentioned' => $schema->number()->optional(),
                'event_name' => $schema->string()->optional(),
                'prayer_subject' => $schema->string()->optional(),
            ])->optional(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
            'reasoning' => $schema->string()->required(),
        ];
    }

    /**
     * Build the prompt content for classification.
     */
    public function buildPrompt(): string
    {
        $contextInfo = '';
        if ($this->memberName) {
            $contextInfo .= "Member name: {$this->memberName}\n";
        }

        $historyInfo = '';
        if (! empty($this->conversationHistory)) {
            $historyInfo = "Recent conversation:\n";
            foreach ($this->conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Bot';
                $historyInfo .= "{$role}: {$msg['content']}\n";
            }
        }

        return <<<PROMPT
{$contextInfo}
{$historyInfo}

Current message to classify:
"{$this->userMessage}"

Classify the intent and extract any relevant entities.
PROMPT;
    }
}
