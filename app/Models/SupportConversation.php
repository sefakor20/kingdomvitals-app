<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportConversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'messages',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
