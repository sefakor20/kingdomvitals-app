<?php

use App\Enums\SmsStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create SmsLog with tracking ID
    $this->smsLog = SmsLog::factory()->sent()->create([
        'branch_id' => $this->branch->id,
        'provider_message_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
    ]);

    tenancy()->end();

    // Configure webhook secret for testing
    Config::set('services.texttango.webhook_secret', 'test-secret');
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('webhook updates sms status to delivered', function (): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => 'delivered',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'success']);

    // Verify the SmsLog was updated    $this->smsLog->refresh();

    expect($this->smsLog->status)->toBe(SmsStatus::Delivered);
    expect($this->smsLog->delivered_at)->not->toBeNull();
});

test('webhook updates sms status to failed with error message', function (): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => 'failed',
        'error_message' => 'Invalid phone number',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk();
    $this->smsLog->refresh();

    expect($this->smsLog->status)->toBe(SmsStatus::Failed);
    expect($this->smsLog->error_message)->toBe('Invalid phone number');
});

test('webhook rejects request with invalid signature', function (): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'status' => 'delivered',
    ];

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => 'invalid-signature',
    ]);

    $response->assertForbidden();
});

test('webhook rejects request without signature', function (): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'status' => 'delivered',
    ];

    $response = $this->postJson('/webhooks/texttango/delivery', $payload);

    $response->assertForbidden();
});

test('webhook ignores request when sms log not found', function (): void {
    $payload = [
        'tracking_id' => 'non-existent-tracking-id',
        'status' => 'delivered',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'not_found']);
});

test('webhook ignores request when status field is missing', function (): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'missing_status']);
});

test('webhook handles various status mappings', function (string $inputStatus, SmsStatus $expectedStatus): void {
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => $inputStatus,
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);
    $this->smsLog->refresh();

    expect($this->smsLog->status)->toBe($expectedStatus);
})->with([
    'delivered' => ['delivered', SmsStatus::Delivered],
    'success' => ['success', SmsStatus::Delivered],
    'failed' => ['failed', SmsStatus::Failed],
    'rejected' => ['rejected', SmsStatus::Failed],
    'expired' => ['expired', SmsStatus::Failed],
    'sent' => ['sent', SmsStatus::Sent],
    'submitted' => ['submitted', SmsStatus::Sent],
    'accepted' => ['accepted', SmsStatus::Sent],
]);

test('webhook allows requests in local environment without secret configured', function (): void {
    Config::set('services.texttango.webhook_secret');
    Config::set('app.env', 'local');

    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => 'delivered',
    ];

    $response = $this->postJson('/webhooks/texttango/delivery', $payload);

    $response->assertOk();
});

test('webhook handles campaign with multiple recipients', function (): void {    // Create multiple SmsLogs with same tracking_id (campaign)
    $smsLog2 = SmsLog::factory()->sent()->create([
        'branch_id' => $this->branch->id,
        'provider_message_id' => 'campaign-123',
        'phone_number' => '+233241234568',
    ]);

    $smsLog3 = SmsLog::factory()->sent()->create([
        'branch_id' => $this->branch->id,
        'provider_message_id' => 'campaign-123',
        'phone_number' => '+233241234569',
    ]);

    tenancy()->end();

    // Webhook for specific recipient in campaign
    $payload = [
        'tracking_id' => 'campaign-123',
        'phone_number' => '+233241234568',
        'status' => 'delivered',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk();
    $smsLog2->refresh();
    $smsLog3->refresh();

    // Only the specific recipient should be updated
    expect($smsLog2->status)->toBe(SmsStatus::Delivered);
    expect($smsLog3->status)->toBe(SmsStatus::Sent); // Unchanged
});

test('webhook ignores request with missing identifiers', function (): void {
    $payload = [
        'status' => 'delivered',
        'phone_number' => '+233241234567',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'missing_identifiers']);
});

test('webhook accepts v2 JSON:API message payload and updates by campaign + phone', function (): void {
    $payload = [
        'data' => [
            'type' => 'message',
            'id' => 'msg-uuid-001',
            'attributes' => [
                'to' => '+233241234567',
                'status' => 'delivered',
                'delivered_at' => '2026-04-26T10:00:00Z',
            ],
            'relationships' => [
                'campaign' => [
                    'data' => ['type' => 'campaign', 'id' => 'test-tracking-123'],
                ],
            ],
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()->assertJson(['status' => 'success']);

    $this->smsLog->refresh();
    expect($this->smsLog->status)->toBe(SmsStatus::Delivered);
    expect($this->smsLog->provider_recipient_id)->toBe('msg-uuid-001');
    expect($this->smsLog->delivered_at?->toIso8601String())->toBe('2026-04-26T10:00:00+00:00');
});

test('webhook accepts v2 JSON:API failed payload with delivery_details description', function (): void {
    $payload = [
        'data' => [
            'type' => 'message',
            'id' => 'msg-uuid-002',
            'attributes' => [
                'to' => '+233241234567',
                'status' => 'failed',
                'failed_at' => '2026-04-26T10:00:00Z',
                'delivery_details' => [
                    'detailed_status' => 'INVALID_NUMBER',
                    'description' => 'Number not reachable',
                ],
            ],
            'relationships' => [
                'campaign' => [
                    'data' => ['type' => 'campaign', 'id' => 'test-tracking-123'],
                ],
            ],
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ])->assertOk();

    $this->smsLog->refresh();
    expect($this->smsLog->status)->toBe(SmsStatus::Failed);
    expect($this->smsLog->error_message)->toBe('Number not reachable');
});

test('webhook accepts branched URL signed with branch-stored secret', function (): void {
    $branchSecret = str_repeat('b', 64);
    $branchId = $this->branch->id;

    // Persist the per-branch secret. We rely on the request's tenant-walking
    // logic (used in production) to find this row when the webhook arrives.
    $this->tenant->run(function () use ($branchId, $branchSecret): void {
        $branch = Branch::find($branchId);
        $branch->setSetting('sms_webhook_secret', Crypt::encryptString($branchSecret));
        $branch->save();
    });

    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => 'delivered',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), $branchSecret);

    $response = $this->postJson("/webhooks/texttango/delivery/{$branchId}", $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()->assertJson(['status' => 'success']);
});

test('webhook accepts branched URL signed with global secret as fallback', function (): void {
    $branchId = $this->branch->id;

    // Branch has no per-branch secret stored — global fallback should kick in.
    $payload = [
        'tracking_id' => 'test-tracking-123',
        'phone_number' => '+233241234567',
        'status' => 'delivered',
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $response = $this->postJson("/webhooks/texttango/delivery/{$branchId}", $payload, [
        'X-TextTango-Signature' => $signature,
    ]);

    $response->assertOk()->assertJson(['status' => 'success']);
});

test('webhook rejects branched URL when neither branch nor global secret matches', function (): void {
    $branchSecret = str_repeat('c', 64);
    $branchId = $this->branch->id;

    $this->tenant->run(function () use ($branchId, $branchSecret): void {
        $branch = Branch::find($branchId);
        $branch->setSetting('sms_webhook_secret', Crypt::encryptString($branchSecret));
        $branch->save();
    });

    $payload = [
        'tracking_id' => 'test-tracking-123',
        'status' => 'delivered',
    ];

    // Sign with neither the branch secret nor the configured global secret.
    $signature = hash_hmac('sha256', json_encode($payload), 'totally-wrong-secret');

    $this->postJson("/webhooks/texttango/delivery/{$branchId}", $payload, [
        'X-TextTango-Signature' => $signature,
    ])->assertForbidden();
});

test('webhook v2 status mappings', function (string $inputStatus, SmsStatus $expectedStatus): void {
    $payload = [
        'data' => [
            'type' => 'message',
            'id' => 'msg-uuid-003',
            'attributes' => [
                'to' => '+233241234567',
                'status' => $inputStatus,
            ],
            'relationships' => [
                'campaign' => [
                    'data' => ['type' => 'campaign', 'id' => 'test-tracking-123'],
                ],
            ],
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');

    $this->postJson('/webhooks/texttango/delivery', $payload, [
        'X-TextTango-Signature' => $signature,
    ])->assertOk();

    $this->smsLog->refresh();
    expect($this->smsLog->status)->toBe($expectedStatus);
})->with([
    'delivered' => ['delivered', SmsStatus::Delivered],
    'failed' => ['failed', SmsStatus::Failed],
    'expired' => ['expired', SmsStatus::Failed],
    'submitted' => ['submitted', SmsStatus::Sent],
]);
