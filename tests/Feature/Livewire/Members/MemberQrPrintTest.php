<?php

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

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
    tenancy()->end();
    $this->tenant?->delete();
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
