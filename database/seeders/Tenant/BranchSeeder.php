<?php

namespace Database\Seeders\Tenant;

use App\Enums\BranchRole;
use App\Enums\BranchStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main branch if it doesn't exist
        $mainBranch = Branch::firstOrCreate(
            ['is_main' => true],
            [
                'name' => 'Main Campus',
                'slug' => 'main-campus',
                'is_main' => true,
                'timezone' => 'Africa/Accra',
                'status' => BranchStatus::Active,
                'country' => 'Ghana',
            ]
        );

        // Grant the first user admin access to the main branch
        $firstUser = User::first();

        if ($firstUser) {
            UserBranchAccess::firstOrCreate(
                [
                    'user_id' => $firstUser->id,
                    'branch_id' => $mainBranch->id,
                ],
                [
                    'role' => BranchRole::Admin,
                    'is_primary' => true,
                    'permissions' => [],
                ]
            );
        }
    }
}
