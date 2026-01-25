<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Livewire\Auth\AcceptBranchInvitation;
use App\Livewire\Users\BranchUserIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchUserInvitation;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Notifications\BranchUserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();

    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Load the tenant routes including Fortify auth routes
    Route::middleware(['web'])->group(function (): void {
        require base_path('vendor/laravel/fortify/routes/routes.php');
        Route::get('/invitations/{token}/accept', \App\Livewire\Auth\AcceptBranchInvitation::class)
            ->name('invitations.accept')
            ->middleware('guest');
    });

    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PENDING INVITATION TESTS
// ============================================

test('can create pending invitation for non-existing user', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'newuser@example.com')
        ->set('inviteRole', 'staff')
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('showInviteModal', false);

    $invitation = BranchUserInvitation::where('email', 'newuser@example.com')
        ->where('branch_id', $this->branch->id)
        ->first();

    expect($invitation)->not->toBeNull();
    expect($invitation->role)->toBe(BranchRole::Staff);
    expect($invitation->invited_by)->toBe($admin->id);
    expect($invitation->expires_at)->toBeGreaterThan(now());
    expect($invitation->accepted_at)->toBeNull();

    Notification::assertSentOnDemand(BranchUserInvitationNotification::class);
});

test('sends invitation email when creating pending invitation', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'newuser@example.com')
        ->set('inviteRole', 'manager')
        ->call('invite');

    Notification::assertSentOnDemand(
        BranchUserInvitationNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'newuser@example.com';
        }
    );
});

test('resends existing invitation if pending invitation exists for same email', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $existingInvitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'existing@example.com',
        'role' => BranchRole::Staff,
        'invited_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('openInviteModal')
        ->set('inviteEmail', 'existing@example.com')
        ->set('inviteRole', 'manager')
        ->call('invite')
        ->assertHasNoErrors();

    // Should not create a duplicate
    expect(BranchUserInvitation::where('email', 'existing@example.com')->count())->toBe(1);

    // Should resend notification
    Notification::assertSentOnDemand(BranchUserInvitationNotification::class);
});

test('can cancel pending invitation', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('cancelPendingInvitation', $invitation->id);

    expect(BranchUserInvitation::find($invitation->id))->toBeNull();
});

test('can resend invitation', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('resendInvitation', $invitation->id);

    Notification::assertSentOnDemand(BranchUserInvitationNotification::class);
});

test('pending invitations are displayed on branch users page', function (): void {
    $admin = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending1@example.com',
        'role' => BranchRole::Staff,
        'invited_by' => $admin->id,
    ]);

    BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending2@example.com',
        'role' => BranchRole::Manager,
        'invited_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->assertSee('Pending Invitations')
        ->assertSee('pending1@example.com')
        ->assertSee('pending2@example.com');
});

// ============================================
// ACCEPT INVITATION TESTS
// ============================================

test('can view accept invitation page with valid token', function (): void {
    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'newuser@example.com',
        'role' => BranchRole::Staff,
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->assertSet('invitationValid', true)
        ->assertSee("You're Invited!");
});

test('shows invalid message for expired invitation', function (): void {
    $invitation = BranchUserInvitation::factory()->expired()->create([
        'branch_id' => $this->branch->id,
        'email' => 'expired@example.com',
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->assertSee('Invalid Invitation');
});

test('shows invalid message for non-existent token', function (): void {
    Livewire::test(AcceptBranchInvitation::class, ['token' => 'invalid-token'])
        ->assertSee('Invalid Invitation');
});

test('shows invalid message for already accepted invitation', function (): void {
    $invitation = BranchUserInvitation::factory()->accepted()->create([
        'branch_id' => $this->branch->id,
        'email' => 'accepted@example.com',
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->assertSee('Invalid Invitation');
});

test('new user can accept invitation and register', function (): void {
    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'newuser@example.com',
        'role' => BranchRole::Manager,
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->assertSet('invitationValid', true)
        ->assertSet('userExists', false)
        ->set('name', 'New User')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('accept')
        ->assertRedirect('/dashboard');

    // User should be created
    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('New User');

    // User should have branch access
    $access = UserBranchAccess::where('user_id', $user->id)
        ->where('branch_id', $this->branch->id)
        ->first();
    expect($access)->not->toBeNull();
    expect($access->role)->toBe(BranchRole::Manager);
    expect($access->is_primary)->toBeTrue();

    // Invitation should be marked as accepted
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('existing user can accept invitation', function (): void {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'existing@example.com',
        'role' => BranchRole::Admin,
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->assertSet('invitationValid', true)
        ->assertSet('userExists', true)
        ->call('accept')
        ->assertRedirect('/dashboard');

    // User should have branch access
    $access = UserBranchAccess::where('user_id', $existingUser->id)
        ->where('branch_id', $this->branch->id)
        ->first();
    expect($access)->not->toBeNull();
    expect($access->role)->toBe(BranchRole::Admin);

    // Invitation should be marked as accepted
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('existing user with access already can still accept invitation', function (): void {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    UserBranchAccess::factory()->create([
        'user_id' => $existingUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'existing@example.com',
        'role' => BranchRole::Admin,
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->call('accept')
        ->assertRedirect('/dashboard');

    // Should not create duplicate access
    expect(UserBranchAccess::where('user_id', $existingUser->id)
        ->where('branch_id', $this->branch->id)
        ->count())->toBe(1);

    // Invitation should still be marked as accepted
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('new user registration requires password confirmation', function (): void {
    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'newuser@example.com',
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->set('name', 'New User')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('accept')
        ->assertHasErrors(['password']);
});

test('new user registration requires minimum password length', function (): void {
    $invitation = BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'newuser@example.com',
    ]);

    Livewire::test(AcceptBranchInvitation::class, ['token' => $invitation->token])
        ->set('name', 'New User')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('accept')
        ->assertHasErrors(['password']);
});

// ============================================
// MODEL TESTS
// ============================================

test('invitation generates unique token', function (): void {
    $token1 = BranchUserInvitation::generateToken();
    $token2 = BranchUserInvitation::generateToken();

    expect($token1)->not->toBe($token2);
    expect(strlen($token1))->toBe(64);
});

test('invitation pending scope excludes expired', function (): void {
    BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    BranchUserInvitation::factory()->expired()->create([
        'branch_id' => $this->branch->id,
        'email' => 'expired@example.com',
    ]);

    $pending = BranchUserInvitation::pending()->get();

    expect($pending)->toHaveCount(1);
    expect($pending->first()->email)->toBe('pending@example.com');
});

test('invitation pending scope excludes accepted', function (): void {
    BranchUserInvitation::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'pending@example.com',
    ]);

    BranchUserInvitation::factory()->accepted()->create([
        'branch_id' => $this->branch->id,
        'email' => 'accepted@example.com',
    ]);

    $pending = BranchUserInvitation::pending()->get();

    expect($pending)->toHaveCount(1);
    expect($pending->first()->email)->toBe('pending@example.com');
});

// ============================================
// PASSWORD RESET LINK TESTS
// ============================================

test('admin can send password reset link to user', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $userAccess = UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('sendPasswordResetLink', $userAccess->id)
        ->assertDispatched('password-reset-sent');

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
});

test('admin cannot send password reset link to themselves', function (): void {
    $admin = User::factory()->create();

    $adminAccess = UserBranchAccess::factory()->create([
        'user_id' => $admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($admin);

    Livewire::test(BranchUserIndex::class, ['branch' => $this->branch])
        ->call('sendPasswordResetLink', $adminAccess->id)
        ->assertForbidden();
});
