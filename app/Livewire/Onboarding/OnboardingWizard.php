<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Enums\BranchRole;
use App\Enums\ServiceType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.onboarding')]
class OnboardingWizard extends Component
{
    // Step 1: Organization
    #[Validate('required|string|max:255')]
    public string $branchName = '';

    #[Validate('required|string|timezone')]
    public string $timezone = 'Africa/Accra';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('nullable|string|max:20')]
    public string $zip = '';

    #[Validate('nullable|string|max:100')]
    public string $country = 'Ghana';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $branchEmail = '';

    // Step 2: Team
    /** @var array<int, array{email: string, role: string}> */
    public array $teamMembers = [];

    public string $newTeamEmail = '';

    public string $newTeamRole = 'staff';

    // Step 3: Integrations
    public string $smsApiKey = '';

    public string $smsSenderId = '';

    public string $paystackSecretKey = '';

    public string $paystackPublicKey = '';

    // Step 4: Services
    /** @var array<int, array{name: string, day_of_week: int, time: string, service_type: string}> */
    public array $services = [];

    public string $newServiceName = '';

    public int $newServiceDay = 0;

    public string $newServiceTime = '09:00';

    public string $newServiceType = 'sunday';

    public function mount(OnboardingService $onboardingService): void
    {
        $tenant = tenant();

        // Redirect if onboarding is already complete
        if ($tenant instanceof Tenant && $tenant->isOnboardingComplete()) {
            $this->redirect('/dashboard', navigate: true);

            return;
        }

        // Initialize with tenant name if available
        if ($tenant instanceof Tenant) {
            $this->branchName = $tenant->name ?? '';
            $this->branchEmail = $tenant->contact_email ?? '';
            $this->phone = $tenant->contact_phone ?? '';
        }

        // Add default Sunday service
        $this->services = [
            [
                'name' => 'Sunday Service',
                'day_of_week' => 0,
                'time' => '09:00',
                'service_type' => 'sunday',
            ],
        ];
    }

    #[Computed]
    public function currentStep(): int
    {
        return app(OnboardingService::class)->getCurrentStep();
    }

    #[Computed]
    public function progress(): int
    {
        return app(OnboardingService::class)->getProgressPercentage();
    }

    #[Computed]
    public function canGoBack(): bool
    {
        return app(OnboardingService::class)->canGoBack();
    }

    #[Computed]
    public function onboardingBranch(): ?Branch
    {
        return app(OnboardingService::class)->getOnboardingBranch();
    }

    #[Computed]
    public function timezones(): array
    {
        return [
            'Africa/Accra' => 'Africa/Accra (GMT)',
            'Africa/Lagos' => 'Africa/Lagos (WAT)',
            'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'America/New_York' => 'America/New_York (EST/EDT)',
            'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
            'UTC' => 'UTC',
        ];
    }

    #[Computed]
    public function daysOfWeek(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    #[Computed]
    public function serviceTypes(): array
    {
        return collect(ServiceType::cases())
            ->mapWithKeys(fn (ServiceType $type) => [$type->value => ucfirst($type->value)])
            ->toArray();
    }

    #[Computed]
    public function teamRoles(): array
    {
        return collect(BranchRole::cases())
            ->filter(fn (BranchRole $role) => $role !== BranchRole::Admin)
            ->mapWithKeys(fn (BranchRole $role) => [$role->value => ucfirst($role->value)])
            ->toArray();
    }

    public function goBack(): void
    {
        app(OnboardingService::class)->previousStep();
        unset($this->currentStep);
        unset($this->progress);
        unset($this->canGoBack);
    }

    public function completeOrganizationStep(): void
    {
        $this->validate([
            'branchName' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'branchEmail' => 'nullable|email|max:255',
        ]);

        $onboardingService = app(OnboardingService::class);

        $onboardingService->completeOrganizationStep([
            'name' => $this->branchName,
            'timezone' => $this->timezone,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'zip' => $this->zip ?: null,
            'country' => $this->country ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->branchEmail ?: null,
        ], Auth::user());

        unset($this->currentStep);
        unset($this->progress);
        unset($this->onboardingBranch);
    }

    public function addTeamMember(): void
    {
        $this->validate([
            'newTeamEmail' => 'required|email|max:255',
            'newTeamRole' => 'required|in:'.implode(',', array_keys($this->teamRoles)),
        ]);

        // Check for duplicates
        foreach ($this->teamMembers as $member) {
            if (strtolower($member['email']) === strtolower($this->newTeamEmail)) {
                $this->addError('newTeamEmail', 'This email has already been added.');

                return;
            }
        }

        // Check not adding self
        if (strtolower($this->newTeamEmail) === strtolower(Auth::user()->email)) {
            $this->addError('newTeamEmail', 'You cannot add yourself to the team.');

            return;
        }

        $this->teamMembers[] = [
            'email' => $this->newTeamEmail,
            'role' => $this->newTeamRole,
        ];

        $this->newTeamEmail = '';
        $this->newTeamRole = 'staff';
    }

    public function removeTeamMember(int $index): void
    {
        unset($this->teamMembers[$index]);
        $this->teamMembers = array_values($this->teamMembers);
    }

    public function completeTeamStep(): void
    {
        $onboardingService = app(OnboardingService::class);
        $branch = $this->onboardingBranch;

        if (! $branch) {
            $this->addError('teamMembers', 'Please complete the organization step first.');

            return;
        }

        if (count($this->teamMembers) > 0) {
            $onboardingService->completeTeamStep($this->teamMembers, $branch);
        } else {
            $onboardingService->skipTeamStep();
        }

        unset($this->currentStep);
        unset($this->progress);
    }

    public function skipTeamStep(): void
    {
        app(OnboardingService::class)->skipTeamStep();
        unset($this->currentStep);
        unset($this->progress);
    }

    public function completeIntegrationsStep(): void
    {
        $onboardingService = app(OnboardingService::class);
        $branch = $this->onboardingBranch;

        if (! $branch) {
            return;
        }

        $hasAnyIntegration = ! empty($this->smsApiKey) || ! empty($this->paystackSecretKey);

        if ($hasAnyIntegration) {
            $onboardingService->completeIntegrationsStep([
                'sms_api_key' => $this->smsApiKey ?: null,
                'sms_sender_id' => $this->smsSenderId ?: null,
                'paystack_secret_key' => $this->paystackSecretKey ?: null,
                'paystack_public_key' => $this->paystackPublicKey ?: null,
            ], $branch);
        } else {
            $onboardingService->skipIntegrationsStep();
        }

        unset($this->currentStep);
        unset($this->progress);
    }

    public function skipIntegrationsStep(): void
    {
        app(OnboardingService::class)->skipIntegrationsStep();
        unset($this->currentStep);
        unset($this->progress);
    }

    public function addService(): void
    {
        $this->validate([
            'newServiceName' => 'required|string|max:255',
            'newServiceDay' => 'required|integer|min:0|max:6',
            'newServiceTime' => 'required|string',
            'newServiceType' => 'required|in:'.implode(',', array_keys($this->serviceTypes)),
        ]);

        $this->services[] = [
            'name' => $this->newServiceName,
            'day_of_week' => $this->newServiceDay,
            'time' => $this->newServiceTime,
            'service_type' => $this->newServiceType,
        ];

        $this->newServiceName = '';
        $this->newServiceDay = 0;
        $this->newServiceTime = '09:00';
        $this->newServiceType = 'sunday';
    }

    public function removeService(int $index): void
    {
        unset($this->services[$index]);
        $this->services = array_values($this->services);
    }

    public function completeServicesStep(): void
    {
        if (count($this->services) === 0) {
            $this->addError('services', 'Please add at least one worship service.');

            return;
        }

        $onboardingService = app(OnboardingService::class);
        $branch = $this->onboardingBranch;

        if (! $branch) {
            return;
        }

        $onboardingService->completeServicesStep($this->services, $branch);

        unset($this->currentStep);
        unset($this->progress);
    }

    public function completeOnboarding(): void
    {
        app(OnboardingService::class)->completeOnboarding();
        $this->redirect('/dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.onboarding.onboarding-wizard')
            ->layoutData(['progress' => $this->progress]);
    }
}
