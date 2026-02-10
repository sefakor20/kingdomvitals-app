<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

final readonly class GeneratedMessage
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $content,
        public string $messageType,
        public string $channel,
        public array $context,
        public string $provider,
        public string $model,
        public ?int $tokensUsed = null,
    ) {}

    /**
     * Get the character count of the message.
     */
    public function characterCount(): int
    {
        return mb_strlen($this->content);
    }

    /**
     * Check if the message fits within SMS limits.
     */
    public function fitsInSms(): bool
    {
        return $this->characterCount() <= 160;
    }

    /**
     * Get SMS segment count (for multi-part SMS).
     */
    public function smsSegmentCount(): int
    {
        $length = $this->characterCount();

        if ($length <= 160) {
            return 1;
        }

        return (int) ceil($length / 153); // 153 chars per segment for multi-part
    }

    /**
     * Convert to array for JSON storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'message_type' => $this->messageType,
            'channel' => $this->channel,
            'context' => $this->context,
            'provider' => $this->provider,
            'model' => $this->model,
            'tokens_used' => $this->tokensUsed,
            'character_count' => $this->characterCount(),
        ];
    }
}
