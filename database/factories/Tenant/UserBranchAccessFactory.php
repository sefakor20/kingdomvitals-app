<?php

namespace Database\Factories\Tenant;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\UserBranchAccess>
 */
class UserBranchAccessFactory extends Factory
{
    protected $model = UserBranchAccess::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'role' => BranchRole::Staff,
            'is_primary' => false,
            'permissions' => [],
        ];
    }

    /**
     * Indicate that this is the user's primary branch.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the user has admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BranchRole::Admin,
        ]);
    }

    /**
     * Indicate that the user has manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BranchRole::Manager,
        ]);
    }

    /**
     * Indicate that the user has staff role.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BranchRole::Staff,
        ]);
    }

    /**
     * Indicate that the user has volunteer role.
     */
    public function volunteer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BranchRole::Volunteer,
        ]);
    }
}
