<?php

use App\Enums\BranchRole;
use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestPrivacy;
use App\Enums\PrayerRequestStatus;
use App\Jobs\SendPrayerChainSmsJob;
use App\Livewire\PrayerRequests\PrayerRequestIndex;
use App\Livewire\PrayerRequests\PrayerRequestShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\PrayerUpdate;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();
    $this->member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view prayer requests page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('prayer-requests.index', $this->branch))
        ->assertSuccessful()
        ->assertSeeLivewire(PrayerRequestIndex::class);
});

test('user without branch access cannot view prayer requests page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get(route('prayer-requests.index', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user cannot view prayer requests page', function () {
    $this->get(route('prayer-requests.index', $this->branch))
        ->assertRedirect();
});

// ============================================
// VIEW PRAYER REQUEST TESTS
// ============================================

test('admin can view prayer request list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    PrayerRequest::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->prayerRequests->count())->toBe(3);
});

test('volunteer can view prayer request list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    PrayerRequest::factory()->count(2)->public()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->prayerRequests->count())->toBe(2);
});

// ============================================
// PRIVACY TESTS
// ============================================

test('admin can view private prayer requests', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $prayerRequest = PrayerRequest::factory()->private()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user)
        ->get(route('prayer-requests.show', [$this->branch, $prayerRequest]))
        ->assertSuccessful();
});

test('staff can view leaders only prayer requests', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->leadersOnly()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user)
        ->get(route('prayer-requests.show', [$this->branch, $prayerRequest]))
        ->assertSuccessful();
});

test('volunteer cannot view private prayer requests', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $prayerRequest = PrayerRequest::factory()->private()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user)
        ->get(route('prayer-requests.show', [$this->branch, $prayerRequest]))
        ->assertForbidden();
});

// ============================================
// CREATE PRAYER REQUEST TESTS
// ============================================

test('admin can create prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Prayer for healing')
        ->set('description', 'Please pray for healing from illness.')
        ->set('category', PrayerRequestCategory::Health->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('member_id', $this->member->id)
        ->call('store')
        ->assertDispatched('prayer-request-created');

    expect(PrayerRequest::where('title', 'Prayer for healing')->exists())->toBeTrue();
});

test('staff can create prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Prayer for family')
        ->set('description', 'Please pray for family unity.')
        ->set('category', PrayerRequestCategory::Family->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('member_id', $this->member->id)
        ->call('store')
        ->assertDispatched('prayer-request-created');

    expect(PrayerRequest::where('title', 'Prayer for family')->exists())->toBeTrue();
});

test('volunteer cannot create prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

test('prayer request can be assigned to a cluster', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $cluster = Cluster::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Cluster prayer request')
        ->set('description', 'Prayer request for the cluster.')
        ->set('category', PrayerRequestCategory::Spiritual->value)
        ->set('privacy', PrayerRequestPrivacy::LeadersOnly->value)
        ->set('member_id', $this->member->id)
        ->set('cluster_id', $cluster->id)
        ->call('store')
        ->assertDispatched('prayer-request-created');

    $prayerRequest = PrayerRequest::where('title', 'Cluster prayer request')->first();
    expect($prayerRequest->cluster_id)->toBe($cluster->id);
});

// ============================================
// UPDATE PRAYER REQUEST TESTS
// ============================================

test('admin can update prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'title' => 'Old Title',
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('edit', $prayerRequest)
        ->set('title', 'Updated Title')
        ->call('update')
        ->assertDispatched('prayer-request-updated');

    expect($prayerRequest->fresh()->title)->toBe('Updated Title');
});

test('volunteer cannot update prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $prayerRequest = PrayerRequest::factory()->public()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('edit', $prayerRequest)
        ->assertForbidden();
});

// ============================================
// DELETE PRAYER REQUEST TESTS
// ============================================

test('admin can delete prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $prayerRequest)
        ->call('delete')
        ->assertDispatched('prayer-request-deleted');

    expect(PrayerRequest::find($prayerRequest->id))->toBeNull();
});

test('manager can delete prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $prayerRequest)
        ->call('delete')
        ->assertDispatched('prayer-request-deleted');

    expect(PrayerRequest::find($prayerRequest->id))->toBeNull();
});

test('staff cannot delete prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $prayerRequest)
        ->assertForbidden();
});

// ============================================
// MARK AS ANSWERED TESTS
// ============================================

test('staff can mark prayer request as answered', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->open()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('openAnsweredModal', $prayerRequest)
        ->set('answer_details', 'God answered this prayer miraculously!')
        ->call('markAsAnswered')
        ->assertDispatched('prayer-request-answered');

    $prayerRequest->refresh();
    expect($prayerRequest->status)->toBe(PrayerRequestStatus::Answered);
    expect($prayerRequest->answer_details)->toBe('God answered this prayer miraculously!');
    expect($prayerRequest->answered_at)->not->toBeNull();
});

test('prayer request can be marked answered from show page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->open()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestShow::class, ['branch' => $this->branch, 'prayerRequest' => $prayerRequest])
        ->call('openAnsweredModal')
        ->set('answer_details', 'Prayer answered!')
        ->call('markAsAnswered')
        ->assertDispatched('prayer-request-answered');

    expect($prayerRequest->fresh()->isAnswered())->toBeTrue();
});

// ============================================
// PRAYER UPDATE TESTS
// ============================================

test('staff can add update to prayer request', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->public()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestShow::class, ['branch' => $this->branch, 'prayerRequest' => $prayerRequest])
        ->call('openAddUpdateModal')
        ->set('update_content', 'We are continuing to pray for this request.')
        ->call('addUpdate')
        ->assertDispatched('prayer-update-added');

    expect(PrayerUpdate::where('prayer_request_id', $prayerRequest->id)->exists())->toBeTrue();
});

// ============================================
// PRAYER CHAIN SMS TESTS
// ============================================

test('staff can send prayer chain sms', function () {
    Queue::fake();

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $prayerRequest = PrayerRequest::factory()->open()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('sendPrayerChain', $prayerRequest)
        ->assertDispatched('prayer-chain-sent');

    Queue::assertPushed(SendPrayerChainSmsJob::class, function ($job) use ($prayerRequest) {
        return $job->prayerRequest->id === $prayerRequest->id;
    });
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search prayer requests by title', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'title' => 'Healing from sickness',
    ]);

    PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'title' => 'Financial breakthrough',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->set('search', 'Healing');

    expect($component->instance()->prayerRequests->count())->toBe(1);
    expect($component->instance()->prayerRequests->first()->title)->toBe('Healing from sickness');
});

test('can filter prayer requests by category', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PrayerRequest::factory()->health()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    PrayerRequest::factory()->family()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->set('categoryFilter', PrayerRequestCategory::Health->value);

    expect($component->instance()->prayerRequests->count())->toBe(1);
    expect($component->instance()->prayerRequests->first()->category)->toBe(PrayerRequestCategory::Health);
});

test('can filter prayer requests by status', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PrayerRequest::factory()->open()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    PrayerRequest::factory()->answered()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', PrayerRequestStatus::Answered->value);

    expect($component->instance()->prayerRequests->count())->toBe(1);
    expect($component->instance()->prayerRequests->first()->status)->toBe(PrayerRequestStatus::Answered);
});

test('can filter prayer requests by privacy', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    PrayerRequest::factory()->public()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    PrayerRequest::factory()->private()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->set('privacyFilter', PrayerRequestPrivacy::Private->value);

    expect($component->instance()->prayerRequests->count())->toBe(1);
    expect($component->instance()->prayerRequests->first()->privacy)->toBe(PrayerRequestPrivacy::Private);
});

// ============================================
// STATS TESTS
// ============================================

test('prayer request stats are calculated correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PrayerRequest::factory()->open()->count(3)->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    PrayerRequest::factory()->answered()->count(2)->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'answered_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch]);

    $stats = $component->instance()->stats;

    expect($stats['total'])->toBe(5);
    expect($stats['open'])->toBe(3);
    expect($stats['answered'])->toBe(2);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('prayer request title is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', '')
        ->set('description', 'Test description')
        ->set('category', PrayerRequestCategory::Health->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('member_id', $this->member->id)
        ->call('store')
        ->assertHasErrors(['title' => 'required']);
});

test('prayer request description is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Test Title')
        ->set('description', '')
        ->set('category', PrayerRequestCategory::Health->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('member_id', $this->member->id)
        ->call('store')
        ->assertHasErrors(['description' => 'required']);
});

test('prayer request member is required when not anonymous', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Test Title')
        ->set('description', 'Test description')
        ->set('category', PrayerRequestCategory::Health->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('is_anonymous', false)
        ->set('member_id', '')
        ->call('store')
        ->assertHasErrors(['member_id' => 'required_if']);
});

test('prayer request can be submitted anonymously', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('title', 'Anonymous Prayer')
        ->set('description', 'This is an anonymous prayer request')
        ->set('category', PrayerRequestCategory::Personal->value)
        ->set('privacy', PrayerRequestPrivacy::Public->value)
        ->set('is_anonymous', true)
        ->call('store')
        ->assertDispatched('prayer-request-created');

    expect(PrayerRequest::where('title', 'Anonymous Prayer')
        ->whereNull('member_id')
        ->exists())->toBeTrue();
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('empty state is shown when no prayer requests exist', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->prayerRequests->count())->toBe(0);
    $component->assertSee('No prayer requests found');
});

// ============================================
// CROSS-BRANCH ACCESS TESTS
// ============================================

test('user cannot update prayer request from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();
    $otherMember = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $prayerRequest = PrayerRequest::factory()->public()->create([
        'branch_id' => $otherBranch->id,
        'member_id' => $otherMember->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('edit', $prayerRequest)
        ->assertForbidden();
});

// ============================================
// MODAL TESTS
// ============================================

test('cancel create modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false);
});

test('cancel delete modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PrayerRequestIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $prayerRequest)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false);
});
