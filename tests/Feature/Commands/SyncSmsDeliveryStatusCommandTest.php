<?php

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use Illuminate\Support\Facades\Crypt;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create branch with SMS configured
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
        ],
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command finds SMS stuck in sent status', function (): void {
    // Create SMS in "Sent" status from 3 hours ago
    $smsLog = SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => SmsStatus::Sent,
        'sent_at' => now()->subHours(3),
        'provider_message_id' => 'test-tracking-123',
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Would check')
        ->assertSuccessful();
});

test('command skips SMS sent less than threshold hours ago', function (): void {
    // Create SMS in "Sent" status from 1 hour ago (less than default 2 hours)
    $smsLog = SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => SmsStatus::Sent,
        'sent_at' => now()->subHour(),
        'provider_message_id' => 'test-tracking-123',
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Checked: 0')
        ->assertSuccessful();
});

test('command skips SMS without provider message id', function (): void {
    // Create SMS without tracking ID
    $smsLog = SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => SmsStatus::Sent,
        'sent_at' => now()->subHours(3),
        'provider_message_id' => null,
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Checked: 0')
        ->assertSuccessful();
});

test('command skips already delivered SMS', function (): void {
    // Create SMS already delivered
    $smsLog = SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => SmsStatus::Delivered,
        'sent_at' => now()->subHours(3),
        'delivered_at' => now()->subHours(2),
        'provider_message_id' => 'test-tracking-123',
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Checked: 0')
        ->assertSuccessful();
});

test('command respects limit option', function (): void {
    // Create 5 SMS in "Sent" status
    SmsLog::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'status' => SmsStatus::Sent,
        'sent_at' => now()->subHours(3),
        'provider_message_id' => 'test-tracking-123',
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
        '--limit' => 2,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Reached limit of 2 SMS')
        ->assertSuccessful();
});

test('command skips branch without SMS configured', function (): void {
    // Create branch without SMS config
    $branchNoSms = Branch::factory()->create([
        'settings' => [],
    ]);

    $smsLog = SmsLog::factory()->create([
        'branch_id' => $branchNoSms->id,
        'status' => SmsStatus::Sent,
        'sent_at' => now()->subHours(3),
        'provider_message_id' => 'test-tracking-123',
        'message_type' => SmsType::Custom,
    ]);

    $this->artisan('sms:sync-delivery-status', [
        '--tenant' => [$this->tenant->id],
        '--hours' => 2,
    ])
        ->expectsOutputToContain('SMS not configured')
        ->assertSuccessful();
});
