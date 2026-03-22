<?php

use App\Enums\BranchRole;
use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Livewire\Email\EmailAnalytics;
use App\Livewire\Email\EmailCompose;
use App\Livewire\Email\EmailIndex;
use App\Livewire\Email\EmailTemplateIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create user with branch access
    $this->user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// MODEL & FACTORY TESTS
// ============================================

test('email log model can be created', function (): void {
    $emailLog = EmailLog::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($emailLog)->toBeInstanceOf(EmailLog::class);
    expect($emailLog->branch_id)->toBe($this->branch->id);
    expect($emailLog->status)->toBeInstanceOf(EmailStatus::class);
    expect($emailLog->message_type)->toBeInstanceOf(EmailType::class);
});

test('email template model can be created', function (): void {
    $template = EmailTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    expect($template)->toBeInstanceOf(EmailTemplate::class);
    expect($template->branch_id)->toBe($this->branch->id);
    expect($template->type)->toBeInstanceOf(EmailType::class);
});

test('email log has factory states for different statuses', function (): void {
    $delivered = EmailLog::factory()->delivered()->create(['branch_id' => $this->branch->id]);
    $pending = EmailLog::factory()->pending()->create(['branch_id' => $this->branch->id]);
    $failed = EmailLog::factory()->failed()->create(['branch_id' => $this->branch->id]);

    expect($delivered->status)->toBe(EmailStatus::Delivered);
    expect($pending->status)->toBe(EmailStatus::Pending);
    expect($failed->status)->toBe(EmailStatus::Failed);
});

// ============================================
// EMAIL INDEX TESTS
// ============================================

test('authenticated user can view email index page', function (): void {
    $this->actingAs($this->user)
        ->get(route('email.index', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(EmailIndex::class);
});

test('unauthenticated user cannot view email index', function (): void {
    $this->get(route('email.index', $this->branch))
        ->assertRedirect('/login');
});

test('email index displays email logs', function (): void {
    EmailLog::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailIndex::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Email Messages');
});

test('email index can filter by status', function (): void {
    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'subject' => 'Delivered Email Subject',
    ]);

    EmailLog::factory()->failed()->create([
        'branch_id' => $this->branch->id,
        'subject' => 'Failed Email Subject',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', EmailStatus::Delivered->value);

    // The filtered results should only contain delivered emails
    $emails = $component->get('emailRecords');
    expect($emails->every(fn ($email) => $email->status === EmailStatus::Delivered))->toBeTrue();
});

test('email index can search by subject', function (): void {
    EmailLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject' => 'Monthly Newsletter March',
    ]);

    EmailLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject' => 'Birthday Greetings',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailIndex::class, ['branch' => $this->branch])
        ->set('search', 'Newsletter');

    $emails = $component->get('emailRecords');
    expect($emails)->toHaveCount(1);
    expect($emails->first()->subject)->toContain('Newsletter');
});

// ============================================
// EMAIL COMPOSE TESTS
// ============================================

test('authenticated user can view email compose page', function (): void {
    $this->actingAs($this->user)
        ->get(route('email.compose', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(EmailCompose::class);
});

test('email compose can load template', function (): void {
    $template = EmailTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Welcome Template',
        'subject' => 'Welcome to our church!',
        'body' => 'Hello {first_name}, welcome aboard!',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailCompose::class, ['branch' => $this->branch])
        ->set('templateId', $template->id);

    expect($component->get('subject'))->toBe('Welcome to our church!');
    expect($component->get('body'))->toBe('Hello {first_name}, welcome aboard!');
});

// ============================================
// EMAIL TEMPLATE TESTS
// ============================================

test('authenticated user can view email templates page', function (): void {
    $this->actingAs($this->user)
        ->get(route('email.templates', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(EmailTemplateIndex::class);
});

test('email templates index displays templates', function (): void {
    EmailTemplate::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailTemplateIndex::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Email Templates');
});

test('can create email template', function (): void {
    Livewire::actingAs($this->user)
        ->test(EmailTemplateIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'New Birthday Template')
        ->set('type', EmailType::Birthday->value)
        ->set('subject', 'Happy Birthday {first_name}!')
        ->set('body', 'Wishing you a wonderful birthday!')
        ->set('is_active', true)
        ->call('store')
        ->assertHasNoErrors();

    expect(EmailTemplate::where('name', 'New Birthday Template')->exists())->toBeTrue();
});

test('can edit email template', function (): void {
    $template = EmailTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Name',
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailTemplateIndex::class, ['branch' => $this->branch])
        ->call('edit', $template->id)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($template->fresh()->name)->toBe('Updated Name');
});

test('can toggle template active status', function (): void {
    $template = EmailTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailTemplateIndex::class, ['branch' => $this->branch])
        ->call('toggleActive', $template->id);

    expect($template->fresh()->is_active)->toBeFalse();
});

test('can delete email template', function (): void {
    $template = EmailTemplate::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailTemplateIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $template->id)
        ->call('delete');

    expect(EmailTemplate::find($template->id))->toBeNull();
});

// ============================================
// EMAIL ANALYTICS TESTS
// ============================================

test('authenticated user can view email analytics page', function (): void {
    $this->actingAs($this->user)
        ->get(route('email.analytics', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(EmailAnalytics::class);
});

test('analytics component renders with correct data', function (): void {
    EmailLog::factory()->count(5)->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    EmailLog::factory()->count(3)->failed()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Email Analytics')
        ->assertSee('Total Sent')
        ->assertSee('Delivered');
});

test('summary stats are calculated correctly', function (): void {
    // Create emails with different statuses
    EmailLog::factory()->count(7)->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    EmailLog::factory()->count(2)->failed()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    EmailLog::factory()->count(1)->pending()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch]);

    $summaryStats = $component->get('summaryStats');

    expect($summaryStats['total'])->toBe(10);
    expect($summaryStats['delivered'])->toBe(7);
    expect($summaryStats['failed'])->toBe(2);
    expect($summaryStats['delivery_rate'])->toBe(70.0);
});

test('period selector changes data range', function (): void {
    // Create emails at different times
    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(5),
    ]);

    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(20),
    ]);

    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(60),
    ]);

    // With 7 day period, should only see 1 email
    $component = Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch])
        ->call('setPeriod', 7);

    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(1);

    // With 30 day period, should see 2 emails
    $component->call('setPeriod', 30);
    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(2);

    // With 90 day period, should see all 3 emails
    $component->call('setPeriod', 90);
    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(3);
});

test('messages by type data is grouped correctly', function (): void {
    EmailLog::factory()->count(3)->birthday()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    EmailLog::factory()->count(2)->reminder()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch]);

    $messagesByType = $component->get('messagesByTypeData');

    expect($messagesByType['labels'])->toContain('Birthday');
    expect($messagesByType['labels'])->toContain('Reminder');
    expect(count($messagesByType['data']))->toBe(2);
});

test('status distribution data includes correct colors', function (): void {
    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    EmailLog::factory()->failed()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch]);

    $statusDistribution = $component->get('statusDistributionData');

    expect($statusDistribution['labels'])->toContain('Delivered');
    expect($statusDistribution['labels'])->toContain('Failed');
    expect($statusDistribution['colors'])->toContain('#22c55e'); // green for delivered
    expect($statusDistribution['colors'])->toContain('#ef4444'); // red for failed
});

test('delivery rate data returns correct format', function (): void {
    EmailLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(EmailAnalytics::class, ['branch' => $this->branch]);

    $deliveryRate = $component->get('deliveryRateData');

    expect($deliveryRate)->toHaveKeys(['labels', 'data']);
    expect($deliveryRate['labels'])->toBeArray();
    expect($deliveryRate['data'])->toBeArray();
});
