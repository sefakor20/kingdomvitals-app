<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\Tenant\Branch;
use Illuminate\Support\Facades\Storage;

class LogoService
{
    /**
     * Get logo URL with full fallback chain: Branch → Tenant → Platform.
     *
     * @param  Branch|null  $branch  Optional branch to check for logo first
     * @param  string  $size  Logo size: 'small', 'medium', 'large', 'favicon', 'apple-touch'
     */
    public static function getLogoUrl(?Branch $branch = null, string $size = 'medium'): ?string
    {
        // 1. Check branch logo (if provided)
        if ($branch && $branch->logo_url) {
            return $branch->logo_url;
        }

        // 2. Fall back to tenant logo
        $tenantLogo = self::getTenantLogoUrl($size);
        if ($tenantLogo) {
            return $tenantLogo;
        }

        // 3. Fall back to platform logo
        return self::getPlatformLogoUrl($size);
    }

    /**
     * Get tenant logo with platform fallback (no branch).
     *
     * @param  string  $size  Logo size: 'small', 'medium', 'large', 'favicon', 'apple-touch'
     */
    public static function getTenantLogoUrl(string $size = 'medium'): ?string
    {
        // Check tenant logo
        if (function_exists('tenant') && tenant() && tenant()->hasLogo()) {
            $tenantLogo = tenant()->getLogoUrl($size);
            if ($tenantLogo) {
                return $tenantLogo;
            }
        }

        // Fall back to platform logo
        return self::getPlatformLogoUrl($size);
    }

    /**
     * Get platform logo only (no tenant/branch fallback).
     *
     * @param  string  $size  Logo size: 'small', 'medium', 'large', 'favicon', 'apple-touch'
     */
    public static function getPlatformLogoUrl(string $size = 'medium'): ?string
    {
        $platformLogoPaths = SystemSetting::get('platform_logo');

        if (! $platformLogoPaths || ! is_array($platformLogoPaths)) {
            return null;
        }

        $path = $platformLogoPaths[$size] ?? null;

        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
