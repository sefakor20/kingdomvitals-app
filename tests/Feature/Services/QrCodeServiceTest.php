<?php

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\QrCodeService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->qrService = app(QrCodeService::class);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// QR CODE GENERATION TESTS
// ============================================

test('can generate qr code svg for member', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $svg = $this->qrService->generateMemberQrCode($member);

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

test('can generate qr code svg with custom size', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $svg = $this->qrService->generateMemberQrCode($member, 500);

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
});

test('can generate raw qr code svg from data', function (): void {
    $svg = $this->qrService->generateQrCodeSvg('https://example.com');

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

// ============================================
// TOKEN GENERATION TESTS
// ============================================

test('member can generate qr token', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'qr_token' => null,
    ]);

    $token = $member->generateQrToken();

    expect($token)->toHaveLength(64);
    expect($member->fresh()->qr_token)->toBe($token);
    expect($member->fresh()->qr_token_generated_at)->not->toBeNull();
});

test('regenerate token creates new unique token', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $oldToken = $member->generateQrToken();
    $newToken = $this->qrService->regenerateToken($member);

    expect($newToken)->not->toBe($oldToken);
    expect($newToken)->toHaveLength(64);
    expect($member->fresh()->qr_token)->toBe($newToken);
});

test('get or generate qr token returns existing token if present', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $token1 = $member->getOrGenerateQrToken();
    $token2 = $member->getOrGenerateQrToken();

    expect($token1)->toBe($token2);
});

test('get or generate qr token creates token if not present', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'qr_token' => null,
    ]);

    expect($member->qr_token)->toBeNull();

    $token = $member->getOrGenerateQrToken();

    expect($token)->toHaveLength(64);
    expect($member->fresh()->qr_token)->toBe($token);
});

// ============================================
// TOKEN VALIDATION TESTS
// ============================================

test('can validate a valid qr token', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $token = $member->generateQrToken();

    $validatedMember = $this->qrService->validateToken($token);

    expect($validatedMember)->not->toBeNull();
    expect($validatedMember->id)->toBe($member->id);
});

test('returns null for invalid qr token', function (): void {
    $member = $this->qrService->validateToken('invalid-token');

    expect($member)->toBeNull();
});

test('returns null for empty qr token', function (): void {
    $member = $this->qrService->validateToken('');

    expect($member)->toBeNull();
});

test('returns null for non-existent qr token', function (): void {
    // Generate a valid-looking token that doesn't exist in the database
    $nonExistentToken = hash('sha256', 'nonexistent');

    $member = $this->qrService->validateToken($nonExistentToken);

    expect($member)->toBeNull();
});

test('returns null for token with wrong length', function (): void {
    $member = $this->qrService->validateToken('short');

    expect($member)->toBeNull();
});

// ============================================
// CHECK-IN URL TESTS
// ============================================

test('can get check-in url for member', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $url = $this->qrService->getCheckInUrl($member);

    expect($url)->toContain('checkin');
    expect($url)->toContain($member->getOrGenerateQrToken());
});

test('get member token returns token', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $token = $this->qrService->getMemberToken($member);

    expect($token)->toHaveLength(64);
    expect($token)->toBe($member->qr_token);
});

// ============================================
// TOKEN UNIQUENESS TESTS
// ============================================

test('generated tokens are unique across members', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $token1 = $member1->generateQrToken();
    $token2 = $member2->generateQrToken();

    expect($token1)->not->toBe($token2);
});
