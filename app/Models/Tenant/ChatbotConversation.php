<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ChatbotChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'member_id',
        'phone_number',
        'channel',
        'context',
        'last_message_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ChatbotChannel::class,
            'context' => 'array',
            'last_message_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<ChatbotConversation>  $query
     * @return Builder<ChatbotConversation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ChatbotConversation>  $query
     * @return Builder<ChatbotConversation>
     */
    public function scopeRecent(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('last_message_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * @param  Builder<ChatbotConversation>  $query
     * @return Builder<ChatbotConversation>
     */
    public function scopeForPhone(Builder $query, string $phoneNumber): Builder
    {
        return $query->where('phone_number', $phoneNumber);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Add a message to the conversation.
     */
    public function addMessage(string $content, string $direction, ?array $metadata = null): ChatbotMessage
    {
        $message = $this->messages()->create([
            'content' => $content,
            'direction' => $direction,
            'intent' => $metadata['intent'] ?? null,
            'extracted_entities' => $metadata['entities'] ?? null,
            'confidence_score' => $metadata['confidence'] ?? null,
            'provider' => $metadata['provider'] ?? 'heuristic',
        ]);

        $this->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * Get the last N messages in the conversation.
     *
     * @return Collection<ChatbotMessage>
     */
    public function getRecentMessages(int $limit = 10): Collection
    {
        return $this->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Update conversation context.
     *
     * @param  array<string, mixed>  $newContext
     */
    public function updateContext(array $newContext): void
    {
        $this->update([
            'context' => array_merge($this->context ?? [], $newContext),
        ]);
    }

    /**
     * Close the conversation.
     */
    public function close(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Find or create a conversation for a phone number.
     */
    public static function findOrCreateForPhone(
        string $branchId,
        string $phoneNumber,
        ChatbotChannel $channel,
        ?string $memberId = null
    ): self {
        return static::firstOrCreate(
            [
                'branch_id' => $branchId,
                'phone_number' => $phoneNumber,
                'is_active' => true,
            ],
            [
                'member_id' => $memberId,
                'channel' => $channel,
                'last_message_at' => now(),
            ]
        );
    }
}
