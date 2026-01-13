<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\ChildMedicalInfoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildMedicalInfo extends Model
{
    /** @use HasFactory<ChildMedicalInfoFactory> */
    use HasFactory, HasUuids;

    protected $table = 'child_medical_info';

    protected static function newFactory(): ChildMedicalInfoFactory
    {
        return ChildMedicalInfoFactory::new();
    }

    protected $fillable = [
        'member_id',
        'allergies',
        'medical_conditions',
        'medications',
        'special_needs',
        'dietary_restrictions',
        'blood_type',
        'doctor_name',
        'doctor_phone',
        'insurance_info',
        'emergency_instructions',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function hasAllergies(): bool
    {
        return ! empty($this->allergies);
    }

    public function hasMedicalConditions(): bool
    {
        return ! empty($this->medical_conditions);
    }

    public function hasSpecialNeeds(): bool
    {
        return ! empty($this->special_needs);
    }
}
