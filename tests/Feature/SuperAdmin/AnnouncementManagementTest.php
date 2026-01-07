<?php

declare(strict_types=1);

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementStatus;
use App\Enums\DeliveryStatus;
use App\Enums\SuperAdminRole;
use App\Jobs\ProcessAnnouncementJob;
use App\Livewire\SuperAdmin\Announcements\AnnouncementIndex;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
});

describe('access control', function () {
    it('allows owner to view announcements page', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        $this->actingAs($owner, 'superadmin')
            ->get(route('superadmin.announcements.index'))
            ->assertOk()
            ->assertSee('Announcements');
    });

    it('allows admin to view announcements page', function () {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.announcements.index'))
            ->assertOk()
            ->assertSee('Announcements');
    });

    it('allows support to view announcements page', function () {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        $this->actingAs($support, 'superadmin')
            ->get(route('superadmin.announcements.index'))
            ->assertOk()
            ->assertSee('Announcements');
    });

    it('denies guest access to announcements page', function () {
        $this->get(route('superadmin.announcements.index'))
            ->assertRedirect(route('superadmin.login'));
    });

    it('shows canCreate as true for owner', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->assertSet('canCreate', true)
            ->assertSet('canSend', true);
    });

    it('shows canCreate as true for admin', function () {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->assertSet('canCreate', true)
            ->assertSet('canSend', true);
    });

    it('shows canCreate as false for support', function () {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        Livewire::actingAs($support, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->assertSet('canCreate', false)
            ->assertSet('canSend', false);
    });
});

describe('creating announcements', function () {
    it('can create a draft announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('showCreateModal', true)
            ->set('title', 'Test Announcement')
            ->set('content', 'This is a test announcement content.')
            ->set('targetAudience', 'all')
            ->set('priority', 'normal')
            ->call('createAnnouncement')
            ->assertSet('showCreateModal', false)
            ->assertDispatched('announcement-created');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Test Announcement',
            'status' => AnnouncementStatus::Draft->value,
            'super_admin_id' => $owner->id,
        ]);
    });

    it('can create a scheduled announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $scheduledTime = now()->addHours(2)->format('Y-m-d\TH:i');

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('showCreateModal', true)
            ->set('title', 'Scheduled Announcement')
            ->set('content', 'This will be sent later.')
            ->set('targetAudience', 'active')
            ->set('priority', 'important')
            ->set('scheduleForLater', true)
            ->set('scheduledAt', $scheduledTime)
            ->call('createAnnouncement')
            ->assertDispatched('announcement-created');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Scheduled Announcement',
            'status' => AnnouncementStatus::Scheduled->value,
            'priority' => AnnouncementPriority::Important->value,
        ]);
    });

    it('validates required fields when creating', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('showCreateModal', true)
            ->set('title', '')
            ->set('content', '')
            ->call('createAnnouncement')
            ->assertHasErrors(['title', 'content']);
    });

    it('support cannot create announcements', function () {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        Livewire::actingAs($support, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('title', 'Test')
            ->set('content', 'Test content')
            ->call('createAnnouncement')
            ->assertForbidden();
    });

    it('logs activity when creating announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('showCreateModal', true)
            ->set('title', 'Logged Announcement')
            ->set('content', 'Content here')
            ->call('createAnnouncement');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'announcement_created',
        ]);
    });
});

describe('editing announcements', function () {
    it('can edit a draft announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->create([
            'super_admin_id' => $owner->id,
            'status' => AnnouncementStatus::Draft,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('openEditModal', $announcement->id)
            ->assertSet('showEditModal', true)
            ->assertSet('title', $announcement->title)
            ->set('title', 'Updated Title')
            ->call('updateAnnouncement')
            ->assertSet('showEditModal', false)
            ->assertDispatched('announcement-updated');

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Title',
        ]);
    });

    it('cannot edit a sent announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->sent()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('openEditModal', $announcement->id)
            ->assertDispatched('error');
    });
});

describe('sending announcements', function () {
    it('can send a draft announcement immediately', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->draft()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('sendAnnouncement', $announcement->id)
            ->assertDispatched('announcement-sending');

        Queue::assertPushed(ProcessAnnouncementJob::class, function ($job) use ($announcement) {
            return $job->announcementId === $announcement->id;
        });
    });

    it('logs activity when sending announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->draft()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('sendAnnouncement', $announcement->id);

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'announcement_sent',
        ]);
    });

    it('support cannot send announcements', function () {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);
        $announcement = Announcement::factory()->draft()->create();

        Livewire::actingAs($support, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('sendAnnouncement', $announcement->id)
            ->assertForbidden();
    });
});

describe('duplicating announcements', function () {
    it('can duplicate an announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $original = Announcement::factory()->sent()->create([
            'super_admin_id' => $owner->id,
            'title' => 'Original Announcement',
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('duplicateAnnouncement', $original->id)
            ->assertDispatched('announcement-duplicated');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Original Announcement (Copy)',
            'status' => AnnouncementStatus::Draft->value,
            'super_admin_id' => $owner->id,
        ]);
    });

    it('logs activity when duplicating announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->create(['super_admin_id' => $owner->id]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('duplicateAnnouncement', $announcement->id);

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'announcement_duplicated',
        ]);
    });
});

describe('deleting announcements', function () {
    it('can delete a draft announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->draft()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('confirmDelete', $announcement->id)
            ->assertSet('showDeleteModal', true)
            ->call('deleteAnnouncement')
            ->assertSet('showDeleteModal', false)
            ->assertDispatched('announcement-deleted');

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    });

    it('can delete a sent announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->sent()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('confirmDelete', $announcement->id)
            ->call('deleteAnnouncement');

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    });

    it('logs activity when deleting announcement', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->draft()->create([
            'super_admin_id' => $owner->id,
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('confirmDelete', $announcement->id)
            ->call('deleteAnnouncement');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'announcement_deleted',
        ]);
    });
});

describe('filtering and searching', function () {
    it('can search announcements by title', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        Announcement::factory()->create(['title' => 'Important Update', 'super_admin_id' => $owner->id]);
        Announcement::factory()->create(['title' => 'Regular Notice', 'super_admin_id' => $owner->id]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('search', 'Important')
            ->assertSee('Important Update')
            ->assertDontSee('Regular Notice');
    });

    it('can filter announcements by status', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        Announcement::factory()->draft()->create(['title' => 'Draft One', 'super_admin_id' => $owner->id]);
        Announcement::factory()->sent()->create(['title' => 'Sent One', 'super_admin_id' => $owner->id]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->set('statusFilter', AnnouncementStatus::Draft->value)
            ->assertSee('Draft One')
            ->assertDontSee('Sent One');
    });
});

describe('viewing announcement details', function () {
    it('can view announcement details with recipients', function () {
        $owner = SuperAdmin::factory()->owner()->create();
        $announcement = Announcement::factory()->sent()->create([
            'super_admin_id' => $owner->id,
            'total_recipients' => 2,
            'successful_count' => 1,
            'failed_count' => 1,
        ]);

        \Illuminate\Support\Facades\DB::table('tenants')->insert([
            'id' => 'test-tenant-view',
            'name' => 'Test Tenant',
            'status' => 'active',
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AnnouncementRecipient::create([
            'announcement_id' => $announcement->id,
            'tenant_id' => 'test-tenant-view',
            'email' => 'test@example.com',
            'delivery_status' => DeliveryStatus::Sent,
            'sent_at' => now(),
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('viewAnnouncement', $announcement->id)
            ->assertSet('showViewModal', true)
            ->assertSee($announcement->title);
    });
});

describe('resending failed announcements', function () {
    it('can resend failed recipients', function () {
        $owner = SuperAdmin::factory()->owner()->create();

        \Illuminate\Support\Facades\DB::table('tenants')->insert([
            'id' => 'test-tenant-resend',
            'name' => 'Test Tenant Resend',
            'contact_email' => 'test@example.com',
            'status' => 'active',
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $announcement = Announcement::factory()->partiallyFailed()->create([
            'super_admin_id' => $owner->id,
        ]);

        AnnouncementRecipient::create([
            'announcement_id' => $announcement->id,
            'tenant_id' => 'test-tenant-resend',
            'email' => 'test@example.com',
            'delivery_status' => DeliveryStatus::Failed,
            'error_message' => 'Connection timeout',
        ]);

        Livewire::actingAs($owner, 'superadmin')
            ->test(AnnouncementIndex::class)
            ->call('resendFailed', $announcement->id)
            ->assertDispatched('announcement-resending');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $owner->id,
            'action' => 'announcement_resent',
        ]);
    });
});

describe('Announcement model', function () {
    it('correctly reports if announcement can be edited', function () {
        $draft = Announcement::factory()->draft()->create();
        $sent = Announcement::factory()->sent()->create();

        expect($draft->canBeEdited())->toBeTrue();
        expect($sent->canBeEdited())->toBeFalse();
    });

    it('correctly reports if announcement can be sent', function () {
        $draft = Announcement::factory()->draft()->create();
        $scheduled = Announcement::factory()->scheduled()->create();
        $sent = Announcement::factory()->sent()->create();

        expect($draft->canBeSent())->toBeTrue();
        expect($scheduled->canBeSent())->toBeTrue();
        expect($sent->canBeSent())->toBeFalse();
    });

    it('correctly calculates delivery percentage', function () {
        $announcement = Announcement::factory()->create([
            'total_recipients' => 10,
            'successful_count' => 8,
            'failed_count' => 2,
        ]);

        expect($announcement->getDeliveryPercentage())->toBe(80.0);
    });
});
