<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create {name} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        $this->info("Creating tenant: {$name}");

        // Create the tenant
        $tenant = Tenant::create([
            'name' => $name,
        ]);

        $this->info("Tenant created with ID: {$tenant->id}");

        // Create the domain
        $tenant->domains()->create([
            'domain' => $domain.'.kingdomvitals-app.test',
        ]);

        $this->info("Domain created: {$domain}.kingdomvitals-app.test");
        $this->info("Database will be: tenant_{$tenant->id}");

        $this->newLine();
        $this->comment('Tenant created successfully!');
        $this->comment("Access at: http://{$domain}.kingdomvitals-app.test");
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment("1. Add '{$domain}.kingdomvitals-app.test' to your /etc/hosts file");
        $this->comment('2. Run: php artisan tenants:migrate');
        $this->comment('3. Visit the domain in your browser');

        return Command::SUCCESS;
    }
}
