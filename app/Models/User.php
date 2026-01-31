<?php

namespace App\Models;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's branch access records.
     */
    public function branchAccess(): HasMany
    {
        return $this->hasMany(UserBranchAccess::class);
    }

    /**
     * Get branches accessible to this user.
     */
    public function accessibleBranches(): Builder
    {
        return Branch::whereIn('id', $this->branchAccess()->pluck('branch_id'));
    }

    /**
     * Check if the user has access to a specific branch.
     */
    public function hasAccessToBranch(string $branchId): bool
    {
        return $this->branchAccess()
            ->where('branch_id', $branchId)
            ->exists();
    }

    /**
     * Get the user's role for a specific branch.
     */
    public function getBranchRole(string $branchId): ?BranchRole
    {
        $access = $this->branchAccess()
            ->where('branch_id', $branchId)
            ->first();

        return $access?->role;
    }

    /**
     * Get the user's primary branch.
     */
    public function primaryBranch(): ?Branch
    {
        $access = $this->branchAccess()
            ->where('is_primary', true)
            ->first();

        return $access ? Branch::find($access->branch_id) : null;
    }
}
