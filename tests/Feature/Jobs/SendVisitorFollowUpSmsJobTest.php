<?php

use App\Enums\SmsType;
use App\Jobs\SendVisitorFollowUpSmsJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch with SMS configured
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

test('job sends follow-up SMS to visitor with phone', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+233241234567',
    ]);

    // Fake HTTP response for successful SMS send
    Http::fake([
        '*' => Http::response([
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => ['tracking_id' => 'test-tracking-id'],
        ], 200),
    ]);

    $message = 'Hello John, thank you for visiting our church!';

    $job = new SendVisitorFollowUpSmsJob($visitor->id, $this->branch->id, $message);
    $job->handle();

    // Assert SMS log was created
    expect(SmsLog::where('visitor_id', $visitor->id)
        ->where('message_type', SmsType::FollowUp)
        ->exists())->toBeTrue();

    $smsLog = SmsLog::where('visitor_id', $visitor->id)->first();
    expect($smsLog->message)->toBe($message);
    expect($smsLog->phone_number)->toBe($visitor->phone);
});

test('job skips visitors without phone number', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => null,
    ]);

    $job = new SendVisitorFollowUpSmsJob($visitor->id, $this->branch->id, 'Test message');
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('visitor_id', $visitor->id)->exists())->toBeFalse();
});

test('job skips when SMS is not configured for branch', function (): void {
    $branchNoSms = Branch::factory()->create();

    $visitor = Visitor::factory()->create([
        'branch_id' => $branchNoSms->id,
        'phone' => '+233241234567',
    ]);

    $job = new SendVisitorFollowUpSmsJob($visitor->id, $branchNoSms->id, 'Test message');
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('visitor_id', $visitor->id)->exists())->toBeFalse();
});

test('job handles non-existent visitor gracefully', function (): void {
    $job = new SendVisitorFollowUpSmsJob('non-existent-id', $this->branch->id, 'Test message');
    $job->handle();

    // Should not throw exception
    expect(true)->toBeTrue();
});

test('job handles non-existent branch gracefully', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => '+233241234567',
    ]);

    $job = new SendVisitorFollowUpSmsJob($visitor->id, 'non-existent-branch-id', 'Test message');
    $job->handle();

    // Should not throw exception and no SMS log created
    expect(SmsLog::where('visitor_id', $visitor->id)->exists())->toBeFalse();
});

test('job logs failed SMS with error message', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => '+233241234567',
    ]);

    // Fake HTTP response to simulate failure
    Http::fake([
        '*' => Http::response([
            'success' => false,
            'message' => 'Insufficient balance',
        ], 400),
    ]);

    $job = new SendVisitorFollowUpSmsJob($visitor->id, $this->branch->id, 'Test message');
    $job->handle();

    // Assert SMS log was created with failed status
    $smsLog = SmsLog::where('visitor_id', $visitor->id)->first();
    expect($smsLog)->not->toBeNull();
    expect($smsLog->status->value)->toBe('failed');
    expect($smsLog->error_message)->toBe('Insufficient balance');
});
