<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantAdminInvitationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class TenantCreationService
{
    /**
     * Create a new tenant with an admin user and send invitation email.
     *
     * @param  array{name: string, domain: string, contact_email: ?string, contact_phone: ?string, address: ?string, trial_days: int}  $tenantData
     * @param  array{name: string, email: string}  $adminData
     */
    public function createTenantWithAdmin(array $tenantData, array $adminData): Tenant
    {
        $tenantId = Str::slug($tenantData['name']).'-'.Str::random(6);

        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $tenantData['name'],
            'status' => TenantStatus::Trial,
            'contact_email' => $tenantData['contact_email'] ?? null,
            'contact_phone' => $tenantData['contact_phone'] ?? null,
            'address' => $tenantData['address'] ?? null,
            'trial_ends_at' => now()->addDays($tenantData['trial_days']),
        ]);

        // Initialize onboarding data for the new tenant
        $tenant->initializeOnboarding();

        $tenant->domains()->create([
            'domain' => $tenantData['domain'],
        ]);

        // Run in tenant context to create the admin user
        $tenant->run(function () use ($adminData, $tenant): void {
            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            // Generate password reset token
            $token = Password::broker('users')->createToken($user);

            // Build reset URL for tenant domain
            $domain = $tenant->domains->first()->domain;
            $scheme = app()->isProduction() ? 'https' : 'http';
            $resetUrl = "{$scheme}://{$domain}/reset-password/{$token}?email=".urlencode($user->email);

            // Send invitation notification with logo for email header
            $user->notify(new TenantAdminInvitationNotification(
                $tenant,
                $resetUrl,
                $tenant->getLogoUrl('medium'),
                $tenant->name
            ));
        });

        return $tenant;
    }
}
