<?php

namespace App\Models\Tenant;

use App\Enums\BranchStatus;
use Database\Factories\Tenant\BranchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'is_main',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'email',
        'capacity',
        'timezone',
        'status',
        'logo_url',
        'color_primary',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'capacity' => 'integer',
            'status' => BranchStatus::class,
            'settings' => 'array',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'primary_branch_id');
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function userAccess(): HasMany
    {
        return $this->hasMany(UserBranchAccess::class);
    }

    public function smsTemplates(): HasMany
    {
        return $this->hasMany(SmsTemplate::class);
    }

    public function dutyRosters(): HasMany
    {
        return $this->hasMany(DutyRoster::class);
    }

    /**
     * Get a setting value from the branch settings.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value in the branch settings.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Check if SMS is configured for this branch.
     */
    public function hasSmsConfigured(): bool
    {
        $apiKey = $this->getSetting('sms_api_key');
        $senderId = $this->getSetting('sms_sender_id');

        return ! empty($apiKey) && ! empty($senderId);
    }

    /**
     * Get the SMS sender ID for this branch.
     */
    public function getSmsSenderId(): ?string
    {
        return $this->getSetting('sms_sender_id');
    }

    /**
     * Check if Paystack is configured for this branch.
     */
    public function hasPaystackConfigured(): bool
    {
        $secretKey = $this->getSetting('paystack_secret_key');
        $publicKey = $this->getSetting('paystack_public_key');

        return ! empty($secretKey) && ! empty($publicKey);
    }

    /**
     * Get the Paystack public key for this branch.
     */
    public function getPaystackPublicKey(): ?string
    {
        return $this->getSetting('paystack_public_key');
    }
}
