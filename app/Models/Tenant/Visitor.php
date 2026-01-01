<?php

namespace App\Models\Tenant;

use App\Enums\VisitorStatus;
use Database\Factories\Tenant\VisitorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): VisitorFactory
    {
        return VisitorFactory::new();
    }

    protected $fillable = [
        'branch_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'visit_date',
        'status',
        'how_did_you_hear',
        'notes',
        'assigned_to',
        'is_converted',
        'converted_member_id',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'is_converted' => 'boolean',
            'status' => VisitorStatus::class,
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_to');
    }

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_member_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
