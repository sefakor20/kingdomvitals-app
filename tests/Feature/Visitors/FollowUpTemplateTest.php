<?php

use App\Enums\BranchRole;
use App\Enums\FollowUpType;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FollowUpTemplate;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use App\Services\FollowUpTemplatePlaceholderService;
use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create a subscription plan with visitors module enabled
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['visitors', 'members'],
    ]);    // Initialize tenancy and run migrations    // Clear cached plan data
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);

    // Configure app URL and host for tenant domain routing    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// MODEL TESTS
// ============================================

test('follow-up template can be created with all fields', function (): void {
    $template = FollowUpTemplate::factory()->forCalls()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->assertDatabaseHas('follow_up_templates', [
        'id' => $template->id,
        'branch_id' => $this->branch->id,
        'type' => 'call',
    ]);

    expect($template->type)->toBe(FollowUpType::Call);
    expect($template->branch_id)->toBe($this->branch->id);
});

test('follow-up template can be generic (null type)', function (): void {
    $template = FollowUpTemplate::factory()->generic()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBeNull();
});

test('follow-up template active scope works', function (): void {
    FollowUpTemplate::factory()->active()->create(['branch_id' => $this->branch->id]);
    FollowUpTemplate::factory()->inactive()->create(['branch_id' => $this->branch->id]);

    $activeTemplates = FollowUpTemplate::active()->get();

    expect($activeTemplates)->toHaveCount(1);
    expect($activeTemplates->first()->is_active)->toBeTrue();
});

test('follow-up template ofType scope works', function (): void {
    FollowUpTemplate::factory()->forCalls()->create(['branch_id' => $this->branch->id]);
    FollowUpTemplate::factory()->forSms()->create(['branch_id' => $this->branch->id]);
    FollowUpTemplate::factory()->generic()->create(['branch_id' => $this->branch->id]);

    $callTemplates = FollowUpTemplate::ofType(FollowUpType::Call)->get();

    expect($callTemplates)->toHaveCount(1);
    expect($callTemplates->first()->type)->toBe(FollowUpType::Call);
});

test('follow-up template forTypeOrGeneric scope returns matching and generic templates', function (): void {
    FollowUpTemplate::factory()->forCalls()->create(['branch_id' => $this->branch->id]);
    FollowUpTemplate::factory()->forSms()->create(['branch_id' => $this->branch->id]);
    FollowUpTemplate::factory()->generic()->create(['branch_id' => $this->branch->id]);

    $templates = FollowUpTemplate::forTypeOrGeneric(FollowUpType::Call)->get();

    expect($templates)->toHaveCount(2);
    expect($templates->contains(fn ($t) => $t->type === FollowUpType::Call))->toBeTrue();
    expect($templates->contains(fn ($t) => $t->type === null))->toBeTrue();
});

test('follow-up template belongs to branch', function (): void {
    $template = FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->branch)->not->toBeNull();
    expect($template->branch->id)->toBe($this->branch->id);
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admin can create follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    expect($user->can('create', [FollowUpTemplate::class, $this->branch]))->toBeTrue();
});

test('manager can create follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    expect($user->can('create', [FollowUpTemplate::class, $this->branch]))->toBeTrue();
});

test('staff cannot create follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    expect($user->can('create', [FollowUpTemplate::class, $this->branch]))->toBeFalse();
});

test('volunteer cannot create follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    expect($user->can('create', [FollowUpTemplate::class, $this->branch]))->toBeFalse();
});

test('all branch roles can view follow-up templates', function (): void {
    foreach (BranchRole::cases() as $role) {
        $user = User::factory()->create();
        UserBranchAccess::factory()->create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'role' => $role,
        ]);

        $this->actingAs($user);

        expect($user->can('viewAny', [FollowUpTemplate::class, $this->branch]))->toBeTrue();
    }
});

test('admin can update follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $template = FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('update', $template))->toBeTrue();
});

test('staff cannot update follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $template = FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('update', $template))->toBeFalse();
});

test('admin can delete follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $template = FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('delete', $template))->toBeTrue();
});

test('staff cannot delete follow-up template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $template = FollowUpTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('delete', $template))->toBeFalse();
});

// ============================================
// PLACEHOLDER SERVICE TESTS
// ============================================

test('placeholder service replaces all placeholders correctly', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '555-1234',
        'email' => 'john@example.com',
        'visit_date' => now()->subDays(5),
    ]);

    $template = 'Hello {first_name} {last_name}, thank you for visiting {branch_name} on {visit_date}. '.
        'It has been {days_since_visit} days. Contact: {phone}, {email}';

    $service = new FollowUpTemplatePlaceholderService;
    $result = $service->replacePlaceholders($template, $visitor, $this->branch);

    expect($result)->toContain('Hello John Doe');
    expect($result)->toContain($this->branch->name);
    expect($result)->toContain('555-1234');
    expect($result)->toContain('john@example.com');
    expect($result)->toContain('5 days');
});

test('placeholder service returns available placeholders', function (): void {
    $service = new FollowUpTemplatePlaceholderService;
    $placeholders = $service->getAvailablePlaceholders();

    expect($placeholders)->toBeArray();
    expect($placeholders)->toHaveKey('{first_name}');
    expect($placeholders)->toHaveKey('{last_name}');
    expect($placeholders)->toHaveKey('{full_name}');
    expect($placeholders)->toHaveKey('{visit_date}');
    expect($placeholders)->toHaveKey('{branch_name}');
    expect($placeholders)->toHaveKey('{days_since_visit}');
    expect($placeholders)->toHaveKey('{phone}');
    expect($placeholders)->toHaveKey('{email}');
});

test('placeholder service handles missing optional fields gracefully', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => null,
        'email' => null,
    ]);

    $template = 'Hello {first_name}, contact: {phone}, {email}';

    $service = new FollowUpTemplatePlaceholderService;
    $result = $service->replacePlaceholders($template, $visitor, $this->branch);

    expect($result)->toContain('Hello John');
    expect($result)->toContain('contact: , ');
});

// ============================================
// FACTORY TESTS
// ============================================

test('factory creates valid call template', function (): void {
    $template = FollowUpTemplate::factory()->forCalls()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBe(FollowUpType::Call);
    expect($template->body)->toContain('{first_name}');
});

test('factory creates valid sms template', function (): void {
    $template = FollowUpTemplate::factory()->forSms()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBe(FollowUpType::Sms);
});

test('factory creates valid email template', function (): void {
    $template = FollowUpTemplate::factory()->forEmail()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBe(FollowUpType::Email);
});

test('factory creates valid visit template', function (): void {
    $template = FollowUpTemplate::factory()->forVisit()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBe(FollowUpType::Visit);
});

test('factory creates valid whatsapp template', function (): void {
    $template = FollowUpTemplate::factory()->forWhatsApp()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template->type)->toBe(FollowUpType::WhatsApp);
});
