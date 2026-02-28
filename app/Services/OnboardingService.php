<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BranchRole;
use App\Enums\BranchStatus;
use App\Enums\ServiceType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchUserInvitation;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\BranchUserInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OnboardingService
{
    public const STEP_ORGANIZATION = 1;

    public const STEP_TEAM = 2;

    public const STEP_INTEGRATIONS = 3;

    public const STEP_SERVICES = 4;

    public const STEP_COMPLETE = 5;

    public const STEPS = [
        self::STEP_ORGANIZATION => 'organization',
        self::STEP_TEAM => 'team',
        self::STEP_INTEGRATIONS => 'integrations',
        self::STEP_SERVICES => 'services',
        self::STEP_COMPLETE => 'complete',
    ];

    public function __construct(
        protected BranchContextService $branchContextService
    ) {}

    /**
     * Check if the current tenant needs onboarding.
     */
    public function isOnboardingRequired(): bool
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $tenant->needsOnboarding();
    }

    /**
     * Get the current step number.
     */
    public function getCurrentStep(): int
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return self::STEP_ORGANIZATION;
        }

        return $tenant->getCurrentOnboardingStep();
    }

    /**
     * Get the step name from step number.
     */
    public function getStepName(int $step): string
    {
        return self::STEPS[$step] ?? 'organization';
    }

    /**
     * Get step number from step name.
     */
    public function getStepNumber(string $name): int
    {
        return array_search($name, self::STEPS, true) ?: self::STEP_ORGANIZATION;
    }

    /**
     * Advance to the next step.
     */
    public function nextStep(): int
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return self::STEP_ORGANIZATION;
        }

        $currentStep = $tenant->getCurrentOnboardingStep();
        $nextStep = min($currentStep + 1, self::STEP_COMPLETE);
        $tenant->setCurrentOnboardingStep($nextStep);

        return $nextStep;
    }

    /**
     * Go back to the previous step.
     */
    public function previousStep(): int
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return self::STEP_ORGANIZATION;
        }

        $currentStep = $tenant->getCurrentOnboardingStep();
        $previousStep = max($currentStep - 1, self::STEP_ORGANIZATION);
        $tenant->setCurrentOnboardingStep($previousStep);

        return $previousStep;
    }

    /**
     * Complete the organization step - creates main branch and grants user access.
     *
     * @param  array{name: string, timezone: string, address?: string, city?: string, state?: string, zip?: string, country?: string, phone?: string, email?: string}  $data
     */
    public function completeOrganizationStep(array $data, User $user): Branch
    {
        $tenant = tenant();

        return DB::transaction(function () use ($data, $user, $tenant) {
            $slug = Str::slug($data['name']);

            // Use firstOrCreate to handle re-submission of organization step
            $branch = Branch::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'is_main' => true,
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'status' => BranchStatus::Active,
                ]
            );

            // Update branch data if it already existed
            if (! $branch->wasRecentlyCreated) {
                $branch->update([
                    'name' => $data['name'],
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                ]);
            }

            // Grant the user admin access to the main branch (if not already granted)
            UserBranchAccess::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                ],
                [
                    'role' => BranchRole::Admin,
                    'is_primary' => true,
                ]
            );

            // Set the branch context
            $this->branchContextService->setCurrentBranch($branch->id);

            // Update tenant onboarding data
            if ($tenant instanceof Tenant) {
                $tenant->setOnboardingBranchId($branch->id);
                $tenant->completeOnboardingStep('organization');
                $tenant->setCurrentOnboardingStep(self::STEP_TEAM);
            }

            Log::info('Onboarding: Organization step completed', [
                'tenant_id' => $tenant?->id,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
            ]);

            return $branch;
        });
    }

    /**
     * Complete the team step - sends invites to team members.
     *
     * @param  array<int, array{email: string, role: string}>  $teamMembers
     */
    public function completeTeamStep(array $teamMembers, Branch $branch): void
    {
        $tenant = tenant();

        DB::transaction(function () use ($teamMembers, $branch, $tenant): void {
            foreach ($teamMembers as $member) {
                // Create placeholder user or send invite
                // For now, we'll just create the user with a random password
                // and they can reset it when they receive the invite email
                $existingUser = User::where('email', $member['email'])->first();

                if (! $existingUser) {
                    $existingUser = User::create([
                        'name' => explode('@', $member['email'])[0],
                        'email' => $member['email'],
                        'password' => Hash::make(Str::random(32)),
                    ]);
                }

                // Grant branch access if not already exists
                $existingAccess = UserBranchAccess::where('user_id', $existingUser->id)
                    ->where('branch_id', $branch->id)
                    ->first();

                if (! $existingAccess) {
                    UserBranchAccess::create([
                        'user_id' => $existingUser->id,
                        'branch_id' => $branch->id,
                        'role' => BranchRole::from($member['role']),
                        'is_primary' => false,
                    ]);
                }

                // Create invitation record and send email
                $invitation = BranchUserInvitation::create([
                    'branch_id' => $branch->id,
                    'email' => $member['email'],
                    'role' => BranchRole::from($member['role']),
                    'token' => BranchUserInvitation::generateToken(),
                    'invited_by' => auth()->id(),
                    'expires_at' => now()->addDays(7),
                ]);

                $acceptUrl = tenant_route(
                    tenant()->domains->first()?->domain ?? '',
                    'invitations.accept',
                    ['token' => $invitation->token]
                );

                Notification::route('mail', $member['email'])
                    ->notify(new BranchUserInvitationNotification(
                        $invitation,
                        $acceptUrl,
                        $tenant?->getLogoUrl('medium'),
                        $tenant?->name
                    ));
            }

            if ($tenant instanceof Tenant) {
                $tenant->completeOnboardingStep('team');
                $tenant->setCurrentOnboardingStep(self::STEP_INTEGRATIONS);
            }
        });

        Log::info('Onboarding: Team step completed', [
            'tenant_id' => $tenant?->id,
            'team_count' => count($teamMembers),
        ]);
    }

    /**
     * Skip the team step.
     */
    public function skipTeamStep(): void
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant) {
            $tenant->skipOnboardingStep('team');
            $tenant->setCurrentOnboardingStep(self::STEP_INTEGRATIONS);
        }

        Log::info('Onboarding: Team step skipped', [
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Complete the integrations step - saves SMS and Paystack credentials.
     *
     * @param  array{sms_api_key?: string, sms_sender_id?: string, paystack_secret_key?: string, paystack_public_key?: string}  $data
     */
    public function completeIntegrationsStep(array $data, Branch $branch): void
    {
        $tenant = tenant();

        DB::transaction(function () use ($data, $branch, $tenant): void {
            // Save SMS settings
            if (! empty($data['sms_api_key']) && ! empty($data['sms_sender_id'])) {
                $branch->setSetting('sms_api_key', $data['sms_api_key']);
                $branch->setSetting('sms_sender_id', $data['sms_sender_id']);
            }

            // Save Paystack settings
            if (! empty($data['paystack_secret_key']) && ! empty($data['paystack_public_key'])) {
                $branch->setSetting('paystack_secret_key', $data['paystack_secret_key']);
                $branch->setSetting('paystack_public_key', $data['paystack_public_key']);
            }

            $branch->save();

            if ($tenant instanceof Tenant) {
                $tenant->completeOnboardingStep('integrations');
                $tenant->setCurrentOnboardingStep(self::STEP_SERVICES);
            }
        });

        Log::info('Onboarding: Integrations step completed', [
            'tenant_id' => $tenant?->id,
            'has_sms' => ! empty($data['sms_api_key']),
            'has_paystack' => ! empty($data['paystack_secret_key']),
        ]);
    }

    /**
     * Skip the integrations step.
     */
    public function skipIntegrationsStep(): void
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant) {
            $tenant->skipOnboardingStep('integrations');
            $tenant->setCurrentOnboardingStep(self::STEP_SERVICES);
        }

        Log::info('Onboarding: Integrations step skipped', [
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Complete the services step - creates worship services.
     *
     * @param  array<int, array{name: string, day_of_week: int, time: string, service_type: string}>  $services
     */
    public function completeServicesStep(array $services, Branch $branch): void
    {
        $tenant = tenant();

        DB::transaction(function () use ($services, $branch, $tenant): void {
            foreach ($services as $serviceData) {
                Service::create([
                    'branch_id' => $branch->id,
                    'name' => $serviceData['name'],
                    'day_of_week' => $serviceData['day_of_week'],
                    'time' => $serviceData['time'],
                    'service_type' => ServiceType::from($serviceData['service_type']),
                    'is_active' => true,
                ]);
            }

            if ($tenant instanceof Tenant) {
                $tenant->completeOnboardingStep('services');
                $tenant->setCurrentOnboardingStep(self::STEP_COMPLETE);
            }
        });

        Log::info('Onboarding: Services step completed', [
            'tenant_id' => $tenant?->id,
            'services_count' => count($services),
        ]);
    }

    /**
     * Complete the entire onboarding process.
     */
    public function completeOnboarding(): void
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant) {
            $tenant->markOnboardingComplete();
        }

        Log::info('Onboarding: Completed', [
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Check if a specific step can be skipped.
     */
    public function canSkipStep(int $step): bool
    {
        // Only team and integrations steps can be skipped
        return in_array($step, [self::STEP_TEAM, self::STEP_INTEGRATIONS], true);
    }

    /**
     * Check if the current user can go back from the current step.
     */
    public function canGoBack(): bool
    {
        return $this->getCurrentStep() > self::STEP_ORGANIZATION;
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): int
    {
        $currentStep = $this->getCurrentStep();
        $totalSteps = count(self::STEPS);

        return (int) round(($currentStep / $totalSteps) * 100);
    }

    /**
     * Get the main branch created during onboarding.
     */
    public function getOnboardingBranch(): ?Branch
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return null;
        }

        $branchId = $tenant->getOnboardingBranchId();

        if (! $branchId) {
            return Branch::where('is_main', true)->first();
        }

        return Branch::find($branchId);
    }
}
