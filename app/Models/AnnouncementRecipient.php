<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRecipient extends Model
{
    use HasUuids;

    protected $fillable = [
        'announcement_id',
        'tenant_id',
        'email',
        'delivery_status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_status' => DeliveryStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return $this->delivery_status === DeliveryStatus::Pending;
    }

    public function isSent(): bool
    {
        return $this->delivery_status === DeliveryStatus::Sent;
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === DeliveryStatus::Failed;
    }

    public function markAsSent(): void
    {
        $this->update([
            'delivery_status' => DeliveryStatus::Sent,
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'delivery_status' => DeliveryStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    public function resetForRetry(): void
    {
        $this->update([
            'delivery_status' => DeliveryStatus::Pending,
            'error_message' => null,
            'sent_at' => null,
        ]);
    }
}
