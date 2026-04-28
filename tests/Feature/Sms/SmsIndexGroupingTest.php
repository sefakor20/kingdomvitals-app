<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Livewire\Sms\SmsIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();

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

test('three recipients of one campaign render as a single grouped row', function (): void {
    foreach (['+233241000001', '+233241000002', '+233241000003'] as $phone) {
        SmsLog::factory()->create([
            'branch_id' => $this->branch->id,
            'phone_number' => $phone,
            'message' => 'Welcome to Kingdom Vitals!',
            'message_type' => SmsType::Custom,
            'status' => SmsStatus::Delivered,
            'provider_message_id' => 'campaign-aaa',
            'cost' => 0.06,
            'currency' => 'GHS',
        ]);
    }

    Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->assertSeeText('3 recipients')
        ->assertSeeText('Welcome to Kingdom Vitals!');
});

test('aggregated status uses worst-case rollup', function (): void {
    // 1 failed + 2 delivered → campaign shows as Failed.
    foreach ([SmsStatus::Delivered, SmsStatus::Failed, SmsStatus::Delivered] as $i => $status) {
        SmsLog::factory()->create([
            'branch_id' => $this->branch->id,
            'phone_number' => "+23324100000{$i}",
            'message' => 'Mixed status campaign',
            'message_type' => SmsType::Custom,
            'status' => $status,
            'provider_message_id' => 'campaign-bbb',
            'cost' => 0.06,
        ]);
    }

    $component = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch]);

    $records = $component->instance()->smsRecords;
    expect($records->total())->toBe(1);

    $campaign = $records->first();
    expect($campaign['recipient_count'])->toBe(3);
    expect($campaign['status'])->toBe(SmsStatus::Failed);
    expect((float) $campaign['total_cost'])->toBe(0.18);
});

test('all-delivered campaign rolls up to Delivered', function (): void {
    foreach (['+233241000001', '+233241000002'] as $phone) {
        SmsLog::factory()->create([
            'branch_id' => $this->branch->id,
            'phone_number' => $phone,
            'message' => 'All good',
            'status' => SmsStatus::Delivered,
            'provider_message_id' => 'campaign-ccc',
        ]);
    }

    $records = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->instance()->smsRecords;

    expect($records->first()['status'])->toBe(SmsStatus::Delivered);
});

test('any-pending (no failures) campaign rolls up to Pending', function (): void {
    foreach ([SmsStatus::Sent, SmsStatus::Pending, SmsStatus::Delivered] as $i => $status) {
        SmsLog::factory()->create([
            'branch_id' => $this->branch->id,
            'phone_number' => "+23324100000{$i}",
            'message' => 'Mid-flight',
            'status' => $status,
            'provider_message_id' => 'campaign-ddd',
        ]);
    }

    $records = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->instance()->smsRecords;

    expect($records->first()['status'])->toBe(SmsStatus::Pending);
});

test('logs without provider_message_id form their own single-row groups', function (): void {
    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'phone_number' => '+233241000001',
        'message' => 'Stuck pending',
        'status' => SmsStatus::Pending,
        'provider_message_id' => null,
    ]);
    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'phone_number' => '+233241000002',
        'message' => 'Errored before submit',
        'status' => SmsStatus::Failed,
        'provider_message_id' => null,
    ]);

    $records = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->instance()->smsRecords;

    expect($records->total())->toBe(2);
    expect($records->getCollection()->every(fn (array $c): bool => $c['recipient_count'] === 1))->toBeTrue();
});

test('viewMessage opens the modal and exposes the campaign with all recipients', function (): void {
    foreach (['+233241000001', '+233241000002'] as $phone) {
        SmsLog::factory()->create([
            'branch_id' => $this->branch->id,
            'phone_number' => $phone,
            'message' => 'Look me up',
            'status' => SmsStatus::Delivered,
            'provider_message_id' => 'campaign-eee',
        ]);
    }

    $component = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->call('viewMessage', 'campaign-eee')
        ->assertSet('showMessageModal', true)
        ->assertSet('viewingCampaignKey', 'campaign-eee');

    $campaign = $component->instance()->viewingCampaign;
    expect($campaign)->not->toBeNull();
    expect($campaign['recipient_count'])->toBe(2);
    expect($campaign['logs']->pluck('phone_number')->all())->toContain('+233241000001', '+233241000002');
});

test('type filter still works on grouped rows', function (): void {
    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'message_type' => SmsType::Birthday,
        'phone_number' => '+233241000001',
        'status' => SmsStatus::Delivered,
        'provider_message_id' => 'campaign-bday',
    ]);
    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'message_type' => SmsType::Reminder,
        'phone_number' => '+233241000002',
        'status' => SmsStatus::Delivered,
        'provider_message_id' => 'campaign-rem',
    ]);

    $records = Livewire::actingAs($this->user)
        ->test(SmsIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', SmsType::Birthday->value)
        ->instance()->smsRecords;

    expect($records->total())->toBe(1);
    expect($records->first()['message_type'])->toBe(SmsType::Birthday);
});
