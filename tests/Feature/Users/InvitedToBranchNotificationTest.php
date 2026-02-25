<?php

use App\Enums\BranchRole;
use App\Livewire\Users\BranchUserIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\BranchUserInvitationNotification;
use App\Notifications\InvitedToBranchNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('invitation email is sent when user is added to branch', function (): void {
    Notification::fake();

    $admin = User::factory()->create();
    $userToInvite = User::factory()->create(['email' => 'invite@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'invite@example.com')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasNoErrors();

    Notification::assertSentTo($userToInvite, InvitedToBranchNotification::class);
});

test('invitation email contains correct branch and role info', function (): void {
    Notification::fake();

    $admin = User::factory()->create(['name' => 'Admin User']);
    $userToInvite = User::factory()->create(['email' => 'invite@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'invite@example.com')
        ->set('inviteRole', 'manager')
        ->call('invite');

    Notification::assertSentTo(
        $userToInvite,
        InvitedToBranchNotification::class,
        function ($notification) use ($admin): true {
            expect($notification->branch->id)->toBe($this->branch->id);
            expect($notification->role)->toBe('manager');
            expect($notification->invitedBy->id)->toBe($admin->id);

            return true;
        }
    );
});

test('pending invitation is created when user does not exist', function (): void {
    Notification::fake();

    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'nonexistent@example.com')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasNoErrors();

    // A pending invitation notification should be sent to the email
    Notification::assertSentOnDemand(BranchUserInvitationNotification::class);
});

test('invitation email is not sent when user already has access', function (): void {
    Notification::fake();

    $admin = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $existingUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'existing@example.com')
        ->set('inviteRole', 'manager')
        ->call('invite')
        ->assertHasErrors(['inviteEmail']);

    Notification::assertNothingSent();
});

test('notification toArray returns correct data', function (): void {
    $admin = User::factory()->create();
    $userToInvite = User::factory()->create();

    $notification = new InvitedToBranchNotification(
        $this->branch,
        'staff',
        $admin
    );

    $data = $notification->toArray($userToInvite);

    expect($data)->toHaveKeys(['branch_id', 'branch_name', 'role', 'invited_by']);
    expect($data['branch_id'])->toBe($this->branch->id);
    expect($data['branch_name'])->toBe($this->branch->name);
    expect($data['role'])->toBe('staff');
    expect($data['invited_by'])->toBe($admin->id);
});
