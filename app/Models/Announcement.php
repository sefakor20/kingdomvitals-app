<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetAudience;
use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'super_admin_id',
        'title',
        'content',
        'target_audience',
        'specific_tenant_ids',
        'priority',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'successful_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'target_audience' => AnnouncementTargetAudience::class,
            'priority' => AnnouncementPriority::class,
            'status' => AnnouncementStatus::class,
            'specific_tenant_ids' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function isDraft(): bool
    {
        return $this->status === AnnouncementStatus::Draft;
    }

    public function isScheduled(): bool
    {
        return $this->status === AnnouncementStatus::Scheduled;
    }

    public function isSending(): bool
    {
        return $this->status === AnnouncementStatus::Sending;
    }

    public function isSent(): bool
    {
        return $this->status === AnnouncementStatus::Sent;
    }

    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    public function canBeSent(): bool
    {
        return $this->status->canBeSent();
    }

    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    public function getDeliveryPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->successful_count / $this->total_recipients) * 100, 1);
    }

    public function getFailedRecipients(): HasMany
    {
        return $this->recipients()->where('delivery_status', DeliveryStatus::Failed);
    }

    public function getPendingRecipients(): HasMany
    {
        return $this->recipients()->where('delivery_status', DeliveryStatus::Pending);
    }

    public function hasFailedRecipients(): bool
    {
        return $this->failed_count > 0;
    }

    public function getStatusSummary(): string
    {
        if ($this->isDraft()) {
            return 'Not sent yet';
        }

        if ($this->isSending()) {
            return "Sending: {$this->successful_count}/{$this->total_recipients}";
        }

        if ($this->failed_count > 0) {
            return "{$this->successful_count} sent, {$this->failed_count} failed";
        }

        return "{$this->successful_count} sent";
    }
}
