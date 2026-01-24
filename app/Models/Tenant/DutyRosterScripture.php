<?php

namespace App\Models\Tenant;

use App\Enums\ScriptureReadingType;
use Database\Factories\Tenant\DutyRosterScriptureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutyRosterScripture extends Model
{
    /** @use HasFactory<DutyRosterScriptureFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): DutyRosterScriptureFactory
    {
        return DutyRosterScriptureFactory::new();
    }

    protected $fillable = [
        'duty_roster_id',
        'reference',
        'reading_type',
        'reader_id',
        'reader_name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'reading_type' => ScriptureReadingType::class,
            'sort_order' => 'integer',
        ];
    }

    public function dutyRoster(): BelongsTo
    {
        return $this->belongsTo(DutyRoster::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reader_id');
    }

    /**
     * Get display name for reader (member name or external name).
     */
    public function getReaderDisplayNameAttribute(): ?string
    {
        if ($this->reader) {
            return $this->reader->fullName();
        }

        return $this->reader_name;
    }
}
