<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\AiAlertType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAlertSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'alert_type',
        'is_enabled',
        'threshold_value',
        'notification_channels',
        'recipient_roles',
        'cooldown_hours',
        'last_triggered_at',
    ];

    protected $attributes = [
        'notification_channels' => '["database", "mail"]',
        'recipient_roles' => '["admin", "pastor"]',
    ];

    protected function casts(): array
    {
        return [
            'alert_type' => AiAlertType::class,
            'is_enabled' => 'boolean',
            'threshold_value' => 'integer',
            'notification_channels' => 'array',
            'recipient_roles' => 'array',
            'cooldown_hours' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter enabled settings.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to filter by alert type.
     */
    public function scopeOfType(Builder $query, AiAlertType $type): Builder
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Check if the cooldown period has passed since last trigger.
     */
    public function canTrigger(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->last_triggered_at === null) {
            return true;
        }

        if ($this->cooldown_hours === 0) {
            return true;
        }

        return $this->last_triggered_at->addHours($this->cooldown_hours)->isPast();
    }

    /**
     * Mark the setting as triggered.
     */
    public function markTriggered(): bool
    {
        return $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Get the threshold value or default for the alert type.
     */
    public function getEffectiveThreshold(): ?int
    {
        return $this->threshold_value ?? $this->alert_type->defaultThreshold();
    }

    /**
     * Check if a value exceeds the threshold.
     */
    public function exceedsThreshold(int|float $value): bool
    {
        $threshold = $this->getEffectiveThreshold();

        if ($threshold === null) {
            return true; // No threshold means always trigger
        }

        return $value >= $threshold;
    }

    /**
     * Get or create setting for a branch and alert type.
     */
    public static function getOrCreateForBranch(string $branchId, AiAlertType $alertType): self
    {
        return self::firstOrCreate(
            [
                'branch_id' => $branchId,
                'alert_type' => $alertType,
            ],
            [
                'is_enabled' => true,
                'threshold_value' => $alertType->defaultThreshold(),
                'notification_channels' => ['database', 'mail'],
                'recipient_roles' => ['admin', 'pastor'],
                'cooldown_hours' => $alertType->defaultCooldownHours(),
            ]
        );
    }

    /**
     * Initialize all alert settings for a branch with defaults.
     */
    public static function initializeForBranch(string $branchId): void
    {
        foreach (AiAlertType::cases() as $alertType) {
            self::getOrCreateForBranch($branchId, $alertType);
        }
    }
}
