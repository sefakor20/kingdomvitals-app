<?php

namespace App\Models\Tenant;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Models\User;
use Database\Factories\Tenant\EmailLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'member_id',
        'email_address',
        'subject',
        'body',
        'message_type',
        'status',
        'provider',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'error_message',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'message_type' => EmailType::class,
            'status' => EmailStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
