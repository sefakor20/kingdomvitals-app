<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Ai\Agents\SupportChatAgent;
use App\Enums\BranchRole;
use App\Models\SupportConversation;
use App\Services\AI\AiService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class SupportChat extends Component
{
    public bool $isOpen = false;

    public string $newMessage = '';

    public bool $isLoading = false;

    public bool $isStreaming = false;

    public ?string $errorMessage = null;

    public string $streamingContent = '';

    /**
     * In-memory conversation history for this session.
     *
     * @var array<int, array{role: string, content: string}>
     */
    public array $messages = [];

    /**
     * @var array<int, array{index: int, rating: string}>
     */
    public array $feedback = [];

    private const MAX_MESSAGE_LENGTH = 1000;

    private const RATE_LIMIT_MAX = 20;

    private const RATE_LIMIT_DECAY_SECONDS = 600;

    /**
     * @var array{name: string, role: string, branch: string, permissions: string[], current_plan: string|null}|null
     */
    public ?array $userContext = null;

    private ?string $conversationId = null;

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $branch = tenant() ? $user->primaryBranch() : null;
        $role = $branch ? $user->getBranchRole($branch->id) : null;

        $this->userContext = [
            'name' => $user->name,
            'role' => $role instanceof BranchRole ? $role->value : 'staff',
            'branch' => $branch?->name ?? 'Main Branch',
            'permissions' => [],
            'current_plan' => tenant()?->subscriptionPlan?->name,
        ];

        $this->loadConversation($user->id);
    }

    private function loadConversation(string $userId): void
    {
        if (! tenant()) {
            return;
        }

        $conversation = SupportConversation::where('user_id', $userId)
            ->latest('updated_at')
            ->first();

        if ($conversation && ! empty($conversation->messages)) {
            $this->messages = $conversation->messages;
            $this->conversationId = $conversation->id;
        }
    }

    private function persistConversation(): void
    {
        if (! tenant()) {
            return;
        }

        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        if ($this->conversationId) {
            SupportConversation::where('id', $this->conversationId)->update([
                'messages' => $this->messages,
                'last_message_at' => now(),
            ]);
        } else {
            $conversation = SupportConversation::create([
                'user_id' => $userId,
                'messages' => $this->messages,
                'last_message_at' => now(),
            ]);
            $this->conversationId = $conversation->id;
        }
    }

    public function toggleOpen(): void
    {
        $this->isOpen = ! $this->isOpen;
        $this->errorMessage = null;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function clearConversation(): void
    {
        $this->messages = [];
        $this->feedback = [];
        $this->errorMessage = null;
        $this->conversationId = null;

        if (tenant()) {
            $userId = auth()->id();
            if ($userId) {
                SupportConversation::where('user_id', $userId)->delete();
            }
        }
    }

    public function rateLimitKey(): string
    {
        return 'support-chat:'.auth()->id();
    }

    public function sendMessage(): void
    {
        $userMessage = trim($this->newMessage);

        if ($userMessage === '' || $this->isLoading || $this->isStreaming) {
            return;
        }

        if (mb_strlen($userMessage) > self::MAX_MESSAGE_LENGTH) {
            $this->errorMessage = __('Message is too long. Please keep it under :max characters.', [
                'max' => self::MAX_MESSAGE_LENGTH,
            ]);

            return;
        }

        if (RateLimiter::tooManyAttempts($this->rateLimitKey(), self::RATE_LIMIT_MAX)) {
            $seconds = RateLimiter::availableIn($this->rateLimitKey());
            $this->errorMessage = __("You've sent too many messages. Please wait :seconds seconds before trying again.", [
                'seconds' => $seconds,
            ]);

            return;
        }

        RateLimiter::hit($this->rateLimitKey(), self::RATE_LIMIT_DECAY_SECONDS);

        $this->messages[] = ['role' => 'user', 'content' => $userMessage];
        $this->newMessage = '';
        $this->isStreaming = true;
        $this->streamingContent = '';
        $this->errorMessage = null;

        try {
            $history = array_slice($this->messages, 0, -1);
            $aiService = app(AiService::class);
            $agent = new SupportChatAgent(
                conversationHistory: $history,
                userContext: $this->userContext,
            );

            $response = $agent->stream(
                $userMessage,
                provider: $aiService->getProvider(),
                model: $aiService->getModel(),
            );

            foreach ($response as $event) {
                if ($event instanceof TextDelta) {
                    $this->streamingContent .= $event->delta;
                    $this->stream($this->streamingContent, to: 'streamingContent');
                }
            }

            $this->messages[] = ['role' => 'assistant', 'content' => $this->streamingContent];
            $this->streamingContent = '';
            $this->persistConversation();
        } catch (Throwable $e) {
            array_pop($this->messages);
            $this->errorMessage = __('Something went wrong. Please try again or contact support.');
            report($e);
        } finally {
            $this->isStreaming = false;
        }
    }

    public function submitFeedback(int $messageIndex, string $rating): void
    {
        if (! in_array($rating, ['up', 'down'])) {
            return;
        }

        $existing = collect($this->feedback)->firstWhere('index', $messageIndex);

        if ($existing) {
            $this->feedback = collect($this->feedback)
                ->map(fn ($f) => $f['index'] === $messageIndex ? ['index' => $messageIndex, 'rating' => $rating] : $f)
                ->values()
                ->toArray();
        } else {
            $this->feedback[] = ['index' => $messageIndex, 'rating' => $rating];
        }
    }

    public function getFeedbackFor(int $index): ?string
    {
        return collect($this->feedback)->firstWhere('index', $index)['rating'] ?? null;
    }

    /**
     * Returns messages with assistant content pre-rendered as safe HTML.
     *
     * @return array<int, array{role: string, content: string}>
     */
    #[Computed]
    public function renderedMessages(): array
    {
        return array_map(function (array $message): array {
            if ($message['role'] === 'assistant') {
                $message['content'] = $this->renderMarkdown($message['content']);
            }

            return $message;
        }, $this->messages);
    }

    public function renderMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $text = preg_replace('/^---$/m', '<hr class="my-2 border-zinc-200 dark:border-zinc-700">', $text);

        $text = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);

        $text = preg_replace(
            '/`([^`]+)`/',
            '<code class="rounded bg-zinc-200 px-1 py-0.5 text-xs font-mono dark:bg-zinc-700">$1</code>',
            $text
        );

        $text = preg_replace_callback('/(?:^\d+\. .+$\n?)+/m', function (array $m): string {
            $items = implode('', array_map(
                fn (string $l): string => '<li>'.preg_replace('/^\d+\. /', '', $l).'</li>',
                array_filter(explode("\n", trim($m[0])))
            ));

            return '<ol class="list-decimal pl-5 space-y-0.5 my-1.5">'.$items.'</ol>';
        }, $text);

        $text = preg_replace_callback('/(?:^[-*] .+$\n?)+/m', function (array $m): string {
            $items = implode('', array_map(
                fn (string $l): string => '<li>'.preg_replace('/^[-*] /', '', $l).'</li>',
                array_filter(explode("\n", trim($m[0])))
            ));

            return '<ul class="list-disc pl-5 space-y-0.5 my-1.5">'.$items.'</ul>';
        }, $text);

        $text = preg_replace('/\n{2,}/', '<br><br>', $text);
        $text = str_replace("\n", '<br>', $text);

        return $text;
    }

    public function render(): View
    {
        return view('livewire.support.support-chat');
    }
}
