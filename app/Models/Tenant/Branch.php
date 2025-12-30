<?php

namespace App\Models\Tenant;

use App\Enums\BranchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, HasUuids;

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
}
