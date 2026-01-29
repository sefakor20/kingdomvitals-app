<?php

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Clear the PlanAccessService cache
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);

    $this->branch = Branch::factory()->main()->create();

    $this->member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('qr code service generates svg for member token', function (): void {
    $token = $this->member->getOrGenerateQrToken();
    $qrCodeService = app(QrCodeService::class);

    $svg = $qrCodeService->generateQrCodeSvg($token, 64);

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

test('member can generate qr token', function (): void {
    $token = $this->member->getOrGenerateQrToken();

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    expect($this->member->qr_token)->toBe($token);
});
