<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\PredictionTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendancePrediction extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'event_id',
        'member_id',
        'attendance_probability',
        'prediction_tier',
        'factors',
        'invitation_sent',
        'invitation_sent_at',
        'invitation_channel',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'attendance_probability' => 'decimal:2',
            'prediction_tier' => PredictionTier::class,
            'factors' => 'array',
            'invitation_sent' => 'boolean',
            'invitation_sent_at' => 'datetime',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * @param  Builder<EventAttendancePrediction>  $query
     * @return Builder<EventAttendancePrediction>
     */
    public function scopeHighProbability(Builder $query): Builder
    {
        return $query->where('prediction_tier', PredictionTier::High);
    }

    /**
     * @param  Builder<EventAttendancePrediction>  $query
     * @return Builder<EventAttendancePrediction>
     */
    public function scopeNotInvited(Builder $query): Builder
    {
        return $query->where('invitation_sent', false);
    }

    /**
     * @param  Builder<EventAttendancePrediction>  $query
     * @return Builder<EventAttendancePrediction>
     */
    public function scopeInvited(Builder $query): Builder
    {
        return $query->where('invitation_sent', true);
    }

    /**
     * @param  Builder<EventAttendancePrediction>  $query
     * @return Builder<EventAttendancePrediction>
     */
    public function scopeAboveThreshold(Builder $query, float $threshold = 50): Builder
    {
        return $query->where('attendance_probability', '>=', $threshold);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function markAsInvited(string $channel = 'sms'): void
    {
        $this->update([
            'invitation_sent' => true,
            'invitation_sent_at' => now(),
            'invitation_channel' => $channel,
        ]);
    }

    /**
     * Check if the prediction suggests sending an invitation.
     */
    public function shouldSendInvitation(): bool
    {
        if ($this->invitation_sent) {
            return false;
        }

        return $this->prediction_tier->shouldSendInvitation();
    }

    /**
     * Get a human-readable summary of the prediction.
     */
    public function getSummary(): string
    {
        return sprintf(
            '%s has a %s%% probability of attending %s',
            $this->member->fullName(),
            number_format($this->attendance_probability, 0),
            $this->event->name
        );
    }
}
