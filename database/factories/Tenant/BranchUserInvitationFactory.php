<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchUserInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BranchUserInvitation>
 */
class BranchUserInvitationFactory extends Factory
{
    protected $model = BranchUserInvitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => BranchRole::Staff,
            'token' => Str::random(64),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate that the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the invitation has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_at' => now(),
        ]);
    }

    /**
     * Set the invitation role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => BranchRole::Admin,
        ]);
    }

    /**
     * Set the invitation role to manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => BranchRole::Manager,
        ]);
    }

    /**
     * Set the invitation role to volunteer.
     */
    public function volunteer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => BranchRole::Volunteer,
        ]);
    }
}
