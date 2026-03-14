<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Ai\Agents\ChatbotIntentAgent;
use App\Enums\ChatbotChannel;
use App\Enums\ChatbotIntent;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChatbotConversation;
use App\Models\Tenant\ChatbotMessage;
use App\Models\Tenant\Member;
use App\Services\AI\Chatbot\ClusterInfoHandler;
use App\Services\AI\Chatbot\EventsHandler;
use App\Services\AI\Chatbot\GivingHistoryHandler;
use App\Services\AI\Chatbot\PrayerRequestHandler;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function __construct(
        protected AiService $aiService,
        protected GivingHistoryHandler $givingHandler,
        protected EventsHandler $eventsHandler,
        protected PrayerRequestHandler $prayerHandler,
        protected ClusterInfoHandler $clusterHandler
    ) {}

    /**
     * Process an inbound message and generate a response.
     *
     * @return array{response: string, intent: ChatbotIntent, confidence: float, conversation_id: string}
     */
    public function processMessage(
        Branch $branch,
        string $phoneNumber,
        string $message,
        ChatbotChannel $channel = ChatbotChannel::Sms
    ): array {
        // Find or create conversation
        $member = $this->findMemberByPhone($branch, $phoneNumber);
        $conversation = ChatbotConversation::findOrCreateForPhone(
            $branch->id,
            $phoneNumber,
            $channel,
            $member?->id
        );

        // Store inbound message
        $conversation->addMessage($message, 'inbound');

        // If no member found, send identification response
        if (! $member && config('ai.features.chatbot.require_member_phone_match', true)) {
            $response = $this->getUnidentifiedResponse($channel);

            $conversation->addMessage($response, 'outbound', [
                'intent' => ChatbotIntent::Unknown->value,
                'confidence' => 100,
                'provider' => 'system',
            ]);

            return [
                'response' => $response,
                'intent' => ChatbotIntent::Unknown,
                'confidence' => 100,
                'conversation_id' => $conversation->id,
            ];
        }

        // Classify intent
        $classification = $this->classifyIntent($conversation, $message, $member);

        // Generate response based on intent
        $response = $this->generateResponse(
            $classification['intent'],
            $member,
            $branch,
            $channel,
            $classification['entities'] ?? []
        );

        // Store outbound message
        $conversation->addMessage($response, 'outbound', [
            'intent' => $classification['intent']->value,
            'confidence' => $classification['confidence'],
            'entities' => $classification['entities'] ?? null,
            'provider' => $classification['provider'],
        ]);

        return [
            'response' => $response,
            'intent' => $classification['intent'],
            'confidence' => $classification['confidence'],
            'conversation_id' => $conversation->id,
        ];
    }

    /**
     * Classify the intent of a message.
     *
     * @return array{intent: ChatbotIntent, confidence: float, entities: ?array, provider: string}
     */
    protected function classifyIntent(
        ChatbotConversation $conversation,
        string $message,
        ?Member $member
    ): array {
        // Try AI classification first
        if (config('ai.features.chatbot.use_ai_classification', true)) {
            try {
                return $this->classifyWithAi($conversation, $message, $member);
            } catch (\Throwable $e) {
                Log::warning('ChatbotService: AI classification failed, using heuristic', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to heuristic classification
        return $this->classifyWithHeuristic($message);
    }

    /**
     * Classify intent using AI agent.
     *
     * @return array{intent: ChatbotIntent, confidence: float, entities: ?array, provider: string}
     */
    protected function classifyWithAi(
        ChatbotConversation $conversation,
        string $message,
        ?Member $member
    ): array {
        $recentMessages = $conversation->getRecentMessages(5)
            ->map(fn (ChatbotMessage $m) => [
                'role' => $m->is_inbound ? 'user' : 'assistant',
                'content' => $m->content,
            ])
            ->toArray();

        $agent = new ChatbotIntentAgent(
            userMessage: $message,
            conversationHistory: $recentMessages,
            memberName: $member?->first_name
        );

        $result = $agent->prompt($agent->buildPrompt());

        return [
            'intent' => ChatbotIntent::from($result['intent']),
            'confidence' => (float) $result['confidence'],
            'entities' => $result['entities'] ?? null,
            'provider' => 'ai-agent',
        ];
    }

    /**
     * Classify intent using heuristic pattern matching.
     *
     * @return array{intent: ChatbotIntent, confidence: float, entities: ?array, provider: string}
     */
    protected function classifyWithHeuristic(string $message): array
    {
        $message = strtolower(trim($message));

        // Check each intent's sample phrases
        foreach (ChatbotIntent::cases() as $intent) {
            foreach ($intent->samplePhrases() as $phrase) {
                if (str_contains($message, strtolower($phrase))) {
                    return [
                        'intent' => $intent,
                        'confidence' => 75.0,
                        'entities' => null,
                        'provider' => 'heuristic',
                    ];
                }
            }
        }

        // Default to unknown
        return [
            'intent' => ChatbotIntent::Unknown,
            'confidence' => 50.0,
            'entities' => null,
            'provider' => 'heuristic',
        ];
    }

    /**
     * Generate response based on classified intent.
     *
     * @param  array<string, mixed>  $entities
     */
    protected function generateResponse(
        ChatbotIntent $intent,
        ?Member $member,
        Branch $branch,
        ChatbotChannel $channel,
        array $entities = []
    ): string {
        $maxLength = $channel->maxMessageLength();

        $response = match ($intent) {
            ChatbotIntent::GivingHistory => $this->givingHandler->handle($member, $branch, $entities),
            ChatbotIntent::UpcomingEvents => $this->eventsHandler->handle($branch, $entities),
            ChatbotIntent::PrayerRequest => $this->prayerHandler->handle($member, $branch, $entities),
            ChatbotIntent::ClusterInfo => $this->clusterHandler->handle($member, $branch, $entities),
            ChatbotIntent::Help => $this->getHelpResponse($channel),
            ChatbotIntent::Greeting => $this->getGreetingResponse($member),
            ChatbotIntent::Unknown => $this->getUnknownResponse($channel),
        };

        // Truncate if necessary
        if (strlen($response) > $maxLength) {
            $response = substr($response, 0, $maxLength - 3).'...';
        }

        return $response;
    }

    /**
     * Find member by phone number.
     */
    protected function findMemberByPhone(Branch $branch, string $phoneNumber): ?Member
    {
        // Normalize phone number (remove spaces, dashes, etc.)
        $normalized = preg_replace('/[^0-9+]/', '', $phoneNumber);

        return Member::where('primary_branch_id', $branch->id)
            ->where(function ($query) use ($normalized, $phoneNumber): void {
                $query->where('phone', $normalized)
                    ->orWhere('phone', $phoneNumber)
                    ->orWhere('phone', 'LIKE', '%'.substr($normalized, -10));
            })
            ->first();
    }

    /**
     * Get help response with available commands.
     */
    protected function getHelpResponse(ChatbotChannel $channel): string
    {
        if ($channel === ChatbotChannel::Sms) {
            return "I can help with:\n- Giving: your donation history\n- Events: upcoming church events\n- Prayer: submit prayer request\n- Cluster: your small group info";
        }

        return "Hello! I'm your church assistant. Here's what I can help with:\n\n".
            "📊 *Giving* - View your donation history\n".
            "📅 *Events* - See upcoming church events\n".
            "🙏 *Prayer* - Submit a prayer request\n".
            "👥 *Cluster* - Your small group information\n\n".
            'Just type what you need!';
    }

    /**
     * Get greeting response.
     */
    protected function getGreetingResponse(?Member $member): string
    {
        $name = $member?->first_name ?? 'there';

        return "Hello {$name}! How can I help you today? Type 'help' to see what I can do.";
    }

    /**
     * Get unknown intent response.
     */
    protected function getUnknownResponse(ChatbotChannel $channel): string
    {
        return "I'm not sure what you're asking for. Type 'help' to see what I can assist with.";
    }

    /**
     * Get response for unidentified phone numbers.
     */
    protected function getUnidentifiedResponse(ChatbotChannel $channel): string
    {
        return "Sorry, I couldn't find a member profile linked to this number. Please contact the church office for assistance.";
    }

    /**
     * Check if the feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.chatbot.enabled', false);
    }
}
