<?php

use App\Livewire\Onboarding\OnboardingWizard;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchUserInvitation;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\BranchUserInvitationNotification;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    app()->forgetInstance(OnboardingService::class);

    // Reset onboarding status since setUpTestTenant marks it complete
    $this->tenant->initializeOnboarding();

    $this->user = User::factory()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

describe('OnboardingWizard', function (): void {
    it('displays step 1 initially', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->assertSet('branchName', $this->tenant->name)
            ->assertSee('Set Up Your Organization');
    });

    it('pre-populates tenant name', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->assertSet('branchName', 'Test Church');
    });

    it('validates required fields on organization step', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', '')
            ->call('completeOrganizationStep')
            ->assertHasErrors(['branchName' => 'required']);
    });

    it('creates main branch on organization step completion', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->set('address', '123 Church Street')
            ->set('city', 'Accra')
            ->set('country', 'Ghana')
            ->call('completeOrganizationStep');

        $branch = Branch::where('name', 'Main Campus')->first();
        expect($branch)->not->toBeNull()
            ->and($branch->is_main)->toBeTrue()
            ->and($branch->timezone)->toBe('Africa/Accra')
            ->and($branch->city)->toBe('Accra');

        // Check user has admin access
        $access = UserBranchAccess::where('user_id', $this->user->id)
            ->where('branch_id', $branch->id)
            ->first();
        expect($access)->not->toBeNull()
            ->and($access->role->value)->toBe('admin')
            ->and($access->is_primary)->toBeTrue();
    });

    it('advances to team step after organization step', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->assertSee('Invite Your Team');
    });

    it('allows skipping team step', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->assertSee('Connect Integrations');
    });

    it('adds team members to the list', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->set('newTeamEmail', 'pastor@church.org')
            ->set('newTeamRole', 'manager')
            ->call('addTeamMember')
            ->assertCount('teamMembers', 1)
            ->assertSee('pastor@church.org');
    });

    it('prevents adding duplicate team members', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->set('newTeamEmail', 'pastor@church.org')
            ->set('newTeamRole', 'staff')
            ->call('addTeamMember')
            ->set('newTeamEmail', 'pastor@church.org')
            ->call('addTeamMember')
            ->assertHasErrors(['newTeamEmail']);
    });

    it('prevents adding self as team member', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->set('newTeamEmail', $this->user->email)
            ->set('newTeamRole', 'staff')
            ->call('addTeamMember')
            ->assertHasErrors(['newTeamEmail']);
    });

    it('removes team members from the list', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->set('newTeamEmail', 'pastor@church.org')
            ->set('newTeamRole', 'manager')
            ->call('addTeamMember')
            ->assertCount('teamMembers', 1)
            ->call('removeTeamMember', 0)
            ->assertCount('teamMembers', 0);
    });

    it('allows skipping integrations step', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->assertSee('Add Worship Services');
    });

    it('requires at least one service', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->set('services', [])
            ->call('completeServicesStep')
            ->assertHasErrors(['services']);
    });

    it('adds services to the list', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->set('newServiceName', 'Evening Service')
            ->set('newServiceDay', 0)
            ->set('newServiceTime', '18:00')
            ->set('newServiceType', 'sunday')
            ->call('addService')
            ->assertCount('services', 2); // Default Sunday service + new one
    });

    it('creates services on services step completion', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->set('services', [
                ['name' => 'Sunday Service', 'day_of_week' => 0, 'time' => '09:00', 'service_type' => 'sunday'],
                ['name' => 'Midweek Service', 'day_of_week' => 3, 'time' => '18:00', 'service_type' => 'midweek'],
            ])
            ->call('completeServicesStep');

        expect(Service::count())->toBe(2);
    });

    it('sends invitation emails when completing team step', function (): void {
        Notification::fake();

        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->set('newTeamEmail', 'pastor@church.org')
            ->set('newTeamRole', 'manager')
            ->call('addTeamMember')
            ->set('newTeamEmail', 'secretary@church.org')
            ->set('newTeamRole', 'staff')
            ->call('addTeamMember')
            ->call('completeTeamStep');

        // Verify invitation records were created
        expect(BranchUserInvitation::count())->toBe(2);

        $pastorInvitation = BranchUserInvitation::where('email', 'pastor@church.org')->first();
        expect($pastorInvitation)->not->toBeNull()
            ->and($pastorInvitation->role->value)->toBe('manager')
            ->and($pastorInvitation->invited_by)->toBe($this->user->id)
            ->and($pastorInvitation->token)->toHaveLength(64)
            ->and($pastorInvitation->expires_at)->toBeInstanceOf(Carbon\Carbon::class);

        $secretaryInvitation = BranchUserInvitation::where('email', 'secretary@church.org')->first();
        expect($secretaryInvitation)->not->toBeNull()
            ->and($secretaryInvitation->role->value)->toBe('staff');

        // Verify notifications were sent
        Notification::assertSentOnDemand(
            BranchUserInvitationNotification::class,
            function ($notification, $channels, $notifiable): bool {
                return $notifiable->routes['mail'] === 'pastor@church.org';
            }
        );

        Notification::assertSentOnDemand(
            BranchUserInvitationNotification::class,
            function ($notification, $channels, $notifiable): bool {
                return $notifiable->routes['mail'] === 'secretary@church.org';
            }
        );
    });

    it('shows complete step after services', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep');

        // Default services array has one Sunday service
        expect($component->get('services'))->toHaveCount(1);

        // Should be on step 4 now
        expect($component->get('currentStep'))->toBe(4);

        $component->call('completeServicesStep');

        // After completing services, should be on step 5
        expect($component->get('currentStep'))->toBe(5);

        // The tenant's current step should be 5 (complete)
        expect($this->tenant->getCurrentOnboardingStep())->toBe(5);
    });

    it('redirects to dashboard on completion', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->call('completeServicesStep')
            ->call('completeOnboarding')
            ->assertRedirect('/dashboard');
    });

    it('marks tenant onboarding as complete', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->call('skipTeamStep')
            ->call('skipIntegrationsStep')
            ->call('completeServicesStep')
            ->call('completeOnboarding');

        $this->tenant->refresh();
        expect($this->tenant->isOnboardingComplete())->toBeTrue();
    });

    it('allows going back to previous steps', function (): void {
        Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class)
            ->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep')
            ->assertSee('Invite Your Team')
            ->call('goBack')
            ->assertSee('Set Up Your Organization');
    });

    it('updates progress percentage as steps are completed', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(OnboardingWizard::class);

        expect($component->get('progress'))->toBe(20); // Step 1 of 5

        $component->set('branchName', 'Main Campus')
            ->set('timezone', 'Africa/Accra')
            ->call('completeOrganizationStep');

        expect($component->get('progress'))->toBe(40); // Step 2 of 5
    });
});

describe('OnboardingService', function (): void {
    it('correctly identifies when onboarding is required', function (): void {
        $service = app(OnboardingService::class);

        expect($service->isOnboardingRequired())->toBeTrue();
    });

    it('returns correct current step', function (): void {
        $service = app(OnboardingService::class);

        expect($service->getCurrentStep())->toBe(OnboardingService::STEP_ORGANIZATION);
    });

    it('can skip steps that are skippable', function (): void {
        $service = app(OnboardingService::class);

        expect($service->canSkipStep(OnboardingService::STEP_TEAM))->toBeTrue()
            ->and($service->canSkipStep(OnboardingService::STEP_INTEGRATIONS))->toBeTrue()
            ->and($service->canSkipStep(OnboardingService::STEP_ORGANIZATION))->toBeFalse()
            ->and($service->canSkipStep(OnboardingService::STEP_SERVICES))->toBeFalse();
    });

    it('calculates progress percentage correctly', function (): void {
        $this->tenant->setCurrentOnboardingStep(1);
        expect(app(OnboardingService::class)->getProgressPercentage())->toBe(20);

        $this->tenant->setCurrentOnboardingStep(3);
        expect(app(OnboardingService::class)->getProgressPercentage())->toBe(60);

        $this->tenant->setCurrentOnboardingStep(5);
        expect(app(OnboardingService::class)->getProgressPercentage())->toBe(100);
    });

    it('advances to next step correctly', function (): void {
        $service = app(OnboardingService::class);
        expect($service->getCurrentStep())->toBe(1);

        $service->nextStep();
        expect($service->getCurrentStep())->toBe(2);

        $service->nextStep();
        expect($service->getCurrentStep())->toBe(3);
    });

    it('goes back to previous step correctly', function (): void {
        $this->tenant->setCurrentOnboardingStep(3);

        $service = app(OnboardingService::class);
        expect($service->getCurrentStep())->toBe(3);

        $service->previousStep();
        expect($service->getCurrentStep())->toBe(2);
    });

    it('does not go below step 1', function (): void {
        $service = app(OnboardingService::class);
        expect($service->getCurrentStep())->toBe(1);

        $service->previousStep();
        expect($service->getCurrentStep())->toBe(1);
    });

    it('does not go above step 5', function (): void {
        $this->tenant->setCurrentOnboardingStep(5);

        $service = app(OnboardingService::class);
        $service->nextStep();
        expect($service->getCurrentStep())->toBe(5);
    });
});

describe('Tenant onboarding methods', function (): void {
    it('initializes onboarding data correctly', function (): void {
        $this->tenant->initializeOnboarding();
        $data = $this->tenant->getOnboardingData();

        expect($data['completed'])->toBeFalse()
            ->and($data['current_step'])->toBe(1)
            ->and($data['steps']['organization']['completed'])->toBeFalse()
            ->and($data['steps']['team']['completed'])->toBeFalse();
    });

    it('marks steps as completed', function (): void {
        $this->tenant->completeOnboardingStep('organization');

        $data = $this->tenant->getOnboardingData();
        expect($data['steps']['organization']['completed'])->toBeTrue()
            ->and($data['steps']['organization']['skipped'])->toBeFalse();
    });

    it('marks steps as skipped', function (): void {
        $this->tenant->skipOnboardingStep('team');

        $data = $this->tenant->getOnboardingData();
        expect($data['steps']['team']['completed'])->toBeFalse()
            ->and($data['steps']['team']['skipped'])->toBeTrue();
    });

    it('detects when onboarding is complete', function (): void {
        expect($this->tenant->isOnboardingComplete())->toBeFalse();

        $this->tenant->markOnboardingComplete();

        expect($this->tenant->isOnboardingComplete())->toBeTrue();
    });

    it('stores branch id during onboarding', function (): void {
        $this->tenant->setOnboardingBranchId('test-branch-id');

        expect($this->tenant->getOnboardingBranchId())->toBe('test-branch-id');
    });

    it('checks if step is done', function (): void {
        expect($this->tenant->isOnboardingStepDone('organization'))->toBeFalse();

        $this->tenant->completeOnboardingStep('organization');
        expect($this->tenant->isOnboardingStepDone('organization'))->toBeTrue();

        $this->tenant->skipOnboardingStep('team');
        expect($this->tenant->isOnboardingStepDone('team'))->toBeTrue();
    });
});

describe('EnsureOnboardingComplete middleware', function (): void {
    it('allows guests through', function (): void {
        $middleware = new \App\Http\Middleware\EnsureOnboardingComplete(
            app(OnboardingService::class)
        );

        $request = \Illuminate\Http\Request::create('/dashboard');
        $response = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => new \Illuminate\Http\Response('OK'));

        expect($response->getContent())->toBe('OK');
    });

    it('returns expected response for onboarding routes', function (): void {
        $middleware = new \App\Http\Middleware\EnsureOnboardingComplete(
            app(OnboardingService::class)
        );

        $request = \Illuminate\Http\Request::create('/onboarding');
        $request->setRouteResolver(function (): \Illuminate\Routing\Route {
            $route = new \Illuminate\Routing\Route('GET', '/onboarding', []);
            $route->name('onboarding.index');

            return $route;
        });

        auth()->login($this->user);

        $response = $middleware->handle($request, fn ($req): \Illuminate\Http\Response => new \Illuminate\Http\Response('OK'));

        expect($response->getContent())->toBe('OK');
    });
});
