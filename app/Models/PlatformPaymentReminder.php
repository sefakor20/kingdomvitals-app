<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPaymentReminder extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    public const TYPE_UPCOMING = 'upcoming';

    public const TYPE_OVERDUE_7 = 'overdue_7';

    public const TYPE_OVERDUE_14 = 'overdue_14';

    public const TYPE_OVERDUE_30 = 'overdue_30';

    public const TYPE_FINAL_NOTICE = 'final_notice';

    public const TYPE_INVOICE_SENT = 'invoice_sent';

    public const TYPE_PAYMENT_RECEIVED = 'payment_received';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'platform_invoice_id',
        'type',
        'channel',
        'sent_at',
        'recipient_email',
        'recipient_phone',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeViaEmail(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_EMAIL);
    }

    public function scopeViaSms(Builder $query): Builder
    {
        return $query->where('channel', self::CHANNEL_SMS);
    }

    public static function getReminderTypes(): array
    {
        return [
            self::TYPE_UPCOMING => 'Upcoming Due',
            self::TYPE_OVERDUE_7 => '7 Days Overdue',
            self::TYPE_OVERDUE_14 => '14 Days Overdue',
            self::TYPE_OVERDUE_30 => '30 Days Overdue',
            self::TYPE_FINAL_NOTICE => 'Final Notice',
            self::TYPE_INVOICE_SENT => 'Invoice Sent',
            self::TYPE_PAYMENT_RECEIVED => 'Payment Received',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getReminderTypes()[$this->type] ?? $this->type;
    }
}
