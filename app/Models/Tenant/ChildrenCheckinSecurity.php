<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildrenCheckinSecurity extends Model
{
    use HasUuids;

    protected $table = 'children_checkin_security';

    protected $fillable = [
        'attendance_id',
        'child_member_id',
        'guardian_member_id',
        'security_code',
        'is_checked_out',
        'checked_out_at',
        'checked_out_by',
    ];

    protected function casts(): array
    {
        return [
            'is_checked_out' => 'boolean',
            'checked_out_at' => 'datetime',
        ];
    }

    public static function generateSecurityCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'child_member_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guardian_member_id');
    }

    public function checkedOutByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'checked_out_by');
    }

    public function checkOut(?Member $checkedOutBy = null): void
    {
        $this->update([
            'is_checked_out' => true,
            'checked_out_at' => now(),
            'checked_out_by' => $checkedOutBy?->id,
        ]);
    }
}
