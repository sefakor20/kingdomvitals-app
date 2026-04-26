<?php

declare(strict_types=1);

use App\Services\TextTangoService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function makeTextTangoService(): TextTangoService
{
    Config::set('services.texttango.base_url', 'https://app.texttango.com/api/v2');
    Config::set('services.texttango.api_key', 'test-api-key');
    Config::set('services.texttango.sender_id', 'TestSender');

    return new TextTangoService;
}

it('sends a campaign to v2 /campaigns and reads tracking id from data.id', function (): void {
    Http::fake([
        'app.texttango.com/api/v2/campaigns' => Http::response([
            'data' => [
                'type' => 'campaign',
                'id' => 'campaign-uuid-001',
                'attributes' => ['status' => 'queued'],
            ],
            'meta' => ['api_version' => 'v2', 'message' => 'Queued'],
        ], 200),
    ]);

    $result = makeTextTangoService()->sendBulkSms(['+233241234567', '+233241234568'], 'Hello');

    expect($result['success'])->toBeTrue();
    expect($result['tracking_id'])->toBe('campaign-uuid-001');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/v2/campaigns')
            && $body['from'] === 'TestSender'
            && $body['body'] === 'Hello'
            && $body['to'] === ['+233241234567', '+233241234568']
            && $body['flash'] === false
            && $body['is_scheduled'] === false;
    });
});

it('renames is_scheduled_datetime to scheduled_at on v2', function (): void {
    Http::fake([
        '*/api/v2/campaigns' => Http::response([
            'data' => ['type' => 'campaign', 'id' => 'c1', 'attributes' => []],
        ], 200),
    ]);

    makeTextTangoService()->sendBulkSms(['+233241234567'], 'Hi', null, true, '2026-12-25T10:00:00Z');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $body['is_scheduled'] === true
            && ($body['scheduled_at'] ?? null) === '2026-12-25T10:00:00Z'
            && ! array_key_exists('is_scheduled_datetime', $body);
    });
});

it('surfaces the v2 JSON:API error detail on failure', function (): void {
    Http::fake([
        '*/api/v2/campaigns' => Http::response([
            'errors' => [[
                'status' => '422',
                'code' => 'VALIDATION_ERROR',
                'title' => 'Validation failed',
                'detail' => 'The from field is required.',
            ]],
            'meta' => ['api_version' => 'v2'],
        ], 422),
    ]);

    $result = makeTextTangoService()->sendBulkSms(['+233241234567'], 'Hi');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('The from field is required.');
});

it('reads main + bonus balance from v2 /wallet', function (): void {
    Http::fake([
        '*/api/v2/wallet' => Http::response([
            'data' => [
                'type' => 'wallet',
                'id' => 'wallet-1',
                'attributes' => [
                    'main_balance' => 250.50,
                    'bonus_balance' => 49.50,
                    'total_balance' => '300.00',
                    'currency' => 'GHS',
                    'bonus_expiry_date' => '2026-12-31',
                ],
            ],
        ], 200),
    ]);

    $result = makeTextTangoService()->getBalance();

    expect($result['success'])->toBeTrue();
    expect($result['main_balance'])->toBe(250.50);
    expect($result['bonus_balance'])->toBe(49.50);
    expect($result['total_balance'])->toBe(300.00);
    expect($result['currency'])->toBe('GHS');
});

it('hits the nested v2 messages endpoint for a single message', function (): void {
    Http::fake([
        '*/api/v2/campaigns/campaign-1/messages/msg-9' => Http::response([
            'data' => [
                'type' => 'message',
                'id' => 'msg-9',
                'attributes' => [
                    'to' => '+233241234567',
                    'status' => 'delivered',
                    'delivered_at' => '2026-04-26T10:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $result = makeTextTangoService()->trackSingleMessage('campaign-1', 'msg-9');

    expect($result['success'])->toBeTrue();
    expect($result['data']['status'])->toBe('delivered');
});

it('reads campaign analytics summary from v2 trackCampaign', function (): void {
    Http::fake([
        '*/api/v2/campaigns/campaign-1' => Http::response([
            'data' => [
                'type' => 'campaign',
                'id' => 'campaign-1',
                'attributes' => ['status' => 'completed'],
                'analytics' => [
                    'summary' => [
                        'total_sent' => '10',
                        'delivered' => '8',
                        'failed' => '2',
                        'pending' => 0,
                        'delivery_percentage' => 80.0,
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = makeTextTangoService()->trackCampaign('campaign-1');

    expect($result['success'])->toBeTrue();
    expect($result['summary']['delivered'])->toBe('8');
    expect((float) $result['summary']['delivery_percentage'])->toBe(80.0);
});
