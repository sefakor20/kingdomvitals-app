<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ChatbotIntent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id',
        'direction',
        'content',
        'intent',
        'extracted_entities',
        'confidence_score',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'intent' => ChatbotIntent::class,
            'extracted_entities' => 'array',
            'confidence_score' => 'decimal:2',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<ChatbotMessage>  $query
     * @return Builder<ChatbotMessage>
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * @param  Builder<ChatbotMessage>  $query
     * @return Builder<ChatbotMessage>
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * @param  Builder<ChatbotMessage>  $query
     * @return Builder<ChatbotMessage>
     */
    public function scopeWithIntent(Builder $query, ChatbotIntent $intent): Builder
    {
        return $query->where('intent', $intent);
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getIsInboundAttribute(): bool
    {
        return $this->direction === 'inbound';
    }

    public function getIsOutboundAttribute(): bool
    {
        return $this->direction === 'outbound';
    }

    public function getHasHighConfidenceAttribute(): bool
    {
        return $this->confidence_score !== null && $this->confidence_score >= 80;
    }
}
