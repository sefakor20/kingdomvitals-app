<?php

use App\Enums\SmsType;
use App\Jobs\SendWelcomeSmsJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch with SMS and welcome SMS configured
    $this->branch = Branch::factory()->main()->create([
        'settings' => [
            'sms_api_key' => Crypt::encryptString('test-api-key'),
            'sms_sender_id' => 'TestSender',
            'auto_welcome_sms' => true,
        ],
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('job sends welcome SMS to new member with phone', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Mock the TextTango service
    $this->mock(TextTangoService::class, function ($mock): void {
        $mock->shouldReceive('forBranch')->andReturnSelf();
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('sendBulkSms')->andReturn([
            'success' => true,
            'tracking_id' => 'test-tracking-id',
        ]);
    });

    // Clear the welcome SMS that was sent during member creation (from observer)
    SmsLog::truncate();

    // Run the job
    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert SMS log was created
    expect(SmsLog::where('member_id', $member->id)
        ->where('message_type', SmsType::Welcome)
        ->exists())->toBeTrue();
});

test('job skips members without phone number', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => null,
        'status' => 'active',
    ]);

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('member_id', $member->id)->exists())->toBeFalse();
});

test('job skips inactive members', function (): void {
    $member = Member::factory()->inactive()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
    ]);

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('member_id', $member->id)->exists())->toBeFalse();
});

test('job skips when welcome SMS is disabled for branch', function (): void {
    $this->branch->setSetting('auto_welcome_sms', false);
    $this->branch->save();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('member_id', $member->id)->exists())->toBeFalse();
});

test('job skips when SMS is not configured for branch', function (): void {
    $branchNoSms = Branch::factory()->create([
        'settings' => ['auto_welcome_sms' => true],
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $branchNoSms->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert no SMS log was created
    expect(SmsLog::where('member_id', $member->id)->exists())->toBeFalse();
});

test('job prevents duplicate welcome SMS', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Create existing welcome SMS log
    SmsLog::factory()->welcome()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'phone_number' => $member->phone,
    ]);

    $initialCount = SmsLog::where('member_id', $member->id)
        ->where('message_type', SmsType::Welcome)
        ->count();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert no new SMS log was created
    expect(SmsLog::where('member_id', $member->id)
        ->where('message_type', SmsType::Welcome)
        ->count())->toBe($initialCount);
});

test('job uses custom template when configured', function (): void {
    // Create a welcome template
    $template = SmsTemplate::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Custom Welcome',
        'body' => 'Hello {first_name}, welcome to {branch_name}!',
        'type' => SmsType::Welcome,
        'is_active' => true,
    ]);

    // Set the template in branch settings
    $this->branch->setSetting('welcome_template_id', $template->id);
    $this->branch->save();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'David',
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Mock the TextTango service
    $this->mock(TextTangoService::class, function ($mock): void {
        $mock->shouldReceive('forBranch')->andReturnSelf();
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('sendBulkSms')
            ->withArgs(function ($phones, $message): bool {
                return str_contains($message, 'Hello David') &&
                    str_contains($message, $this->branch->name);
            })
            ->andReturn([
                'success' => true,
                'tracking_id' => 'test-tracking-id',
            ]);
    });

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert SMS was sent with personalized message
    $smsLog = SmsLog::where('member_id', $member->id)->first();
    expect($smsLog)->not->toBeNull();
    expect($smsLog->message)->toContain('Hello David');
});

test('job personalizes message with member details', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Mary',
        'last_name' => 'Smith',
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    // Mock the TextTango service
    $this->mock(TextTangoService::class, function ($mock): void {
        $mock->shouldReceive('forBranch')->andReturnSelf();
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('sendBulkSms')
            ->withArgs(function ($phones, $message): bool {
                return str_contains($message, 'Mary') &&
                    str_contains($message, $this->branch->name);
            })
            ->andReturn([
                'success' => true,
                'tracking_id' => 'test-tracking-id',
            ]);
    });

    // Clear any SMS logs
    SmsLog::truncate();

    $job = new SendWelcomeSmsJob($member->id);
    $job->handle();

    // Assert SMS was personalized
    $smsLog = SmsLog::where('member_id', $member->id)->first();
    expect($smsLog)->not->toBeNull();
    expect($smsLog->message)->toContain('Mary');
});

test('job is dispatched when member is created', function (): void {
    Queue::fake();

    // Creating a member should dispatch the welcome SMS job
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '+233241234567',
        'status' => 'active',
    ]);

    Queue::assertPushed(SendWelcomeSmsJob::class, function ($job) use ($member): bool {
        return $job->memberId === $member->id;
    });
});

test('job handles non-existent member gracefully', function (): void {
    $job = new SendWelcomeSmsJob('non-existent-id');
    $job->handle();

    // Should not throw exception
    expect(true)->toBeTrue();
});
