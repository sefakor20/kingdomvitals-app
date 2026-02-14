<?php

declare(strict_types=1);

use App\Models\SystemSetting;
use App\Models\Tenant\Branch;
use App\Services\LogoService;
use Illuminate\Support\Facades\Storage;

describe('LogoService', function () {
    describe('getLogoUrl', function () {
        it('returns branch logo when branch has logo_url', function () {
            $branch = new Branch;
            $branch->logo_url = 'https://example.com/branch-logo.png';

            $result = LogoService::getLogoUrl($branch, 'medium');

            expect($result)->toBe('https://example.com/branch-logo.png');
        });

        it('returns null when branch has no logo and no platform logo', function () {
            $branch = new Branch;
            $branch->logo_url = null;

            // Clear any platform logo setting
            SystemSetting::query()->where('key', 'platform_logo')->delete();

            $result = LogoService::getLogoUrl($branch, 'medium');

            expect($result)->toBeNull();
        });

        it('falls back to platform logo when branch has no logo', function () {
            Storage::fake('public');
            Storage::disk('public')->put('logos/platform-medium.png', 'fake-image');

            $branch = new Branch;
            $branch->logo_url = null;

            // Set platform logo in SystemSetting
            SystemSetting::set('platform_logo', [
                'small' => 'logos/platform-small.png',
                'medium' => 'logos/platform-medium.png',
                'large' => 'logos/platform-large.png',
            ]);

            $result = LogoService::getLogoUrl($branch, 'medium');

            expect($result)->toBeString()->and($result)->toContain('logos/platform-medium.png');
        });
    });

    describe('getTenantLogoUrl', function () {
        it('returns platform logo when no tenant context', function () {
            Storage::fake('public');
            Storage::disk('public')->put('logos/platform-medium.png', 'fake-image');

            SystemSetting::set('platform_logo', [
                'medium' => 'logos/platform-medium.png',
            ]);

            $result = LogoService::getTenantLogoUrl('medium');

            expect($result)->toBeString()->and($result)->toContain('logos/platform-medium.png');
        });

        it('returns null when no tenant and no platform logo', function () {
            SystemSetting::query()->where('key', 'platform_logo')->delete();

            $result = LogoService::getTenantLogoUrl('medium');

            expect($result)->toBeNull();
        });
    });

    describe('getPlatformLogoUrl', function () {
        it('returns platform logo URL when logo exists', function () {
            Storage::fake('public');
            Storage::disk('public')->put('logos/platform-small.png', 'fake-image');

            SystemSetting::set('platform_logo', [
                'small' => 'logos/platform-small.png',
                'medium' => 'logos/platform-medium.png',
            ]);

            $result = LogoService::getPlatformLogoUrl('small');

            expect($result)->toBeString()->and($result)->toContain('logos/platform-small.png');
        });

        it('returns null when platform logo setting is not set', function () {
            SystemSetting::query()->where('key', 'platform_logo')->delete();

            $result = LogoService::getPlatformLogoUrl('medium');

            expect($result)->toBeNull();
        });

        it('returns null when requested size is not in platform logo paths', function () {
            SystemSetting::set('platform_logo', [
                'small' => 'logos/platform-small.png',
            ]);

            $result = LogoService::getPlatformLogoUrl('large');

            expect($result)->toBeNull();
        });

        it('returns null when logo file does not exist', function () {
            Storage::fake('public');
            // Note: We don't create the file here

            SystemSetting::set('platform_logo', [
                'medium' => 'logos/nonexistent.png',
            ]);

            $result = LogoService::getPlatformLogoUrl('medium');

            expect($result)->toBeNull();
        });
    });
});
