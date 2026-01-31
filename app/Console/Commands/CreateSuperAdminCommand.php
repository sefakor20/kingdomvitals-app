<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superadmin:create
                            {email : The email address for the super admin}
                            {--name= : The name for the super admin}
                            {--role=owner : The role (owner, admin, support)}
                            {--password= : Optional password (random if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new super admin account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (SuperAdmin::where('email', $email)->exists()) {
            $this->warn("Super admin with email '{$email}' already exists. Skipping.");

            return Command::SUCCESS;
        }

        $name = $this->option('name') ?? Str::before($email, '@');
        $roleValue = $this->option('role');
        $role = SuperAdminRole::tryFrom($roleValue) ?? SuperAdminRole::Owner;

        $password = $this->option('password') ?? Str::password(16);

        $superAdmin = SuperAdmin::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);

        $superAdmin->email_verified_at = now();
        $superAdmin->save();

        $this->info('Super admin created successfully!');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $superAdmin->id],
                ['Name', $superAdmin->name],
                ['Email', $superAdmin->email],
                ['Role', $role->label()],
                ['Password', $this->option('password') ? '[provided]' : $password],
            ]
        );

        if (! $this->option('password')) {
            $this->warn('Please save this password securely. It will not be shown again.');
            $this->warn('The user should change this password after first login.');
        }

        return Command::SUCCESS;
    }
}
