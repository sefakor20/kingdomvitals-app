<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Member;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Generate an SVG QR code for a member's check-in URL.
     */
    public function generateMemberQrCode(Member $member, int $size = 300): string
    {
        $url = $this->getCheckInUrl($member);

        return $this->generateQrCodeSvg($url, $size);
    }

    /**
     * Generate a raw SVG QR code from any data.
     */
    public function generateQrCodeSvg(string $data, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($data);
    }

    /**
     * Regenerate a member's QR token and return the new token.
     */
    public function regenerateToken(Member $member): string
    {
        return $member->generateQrToken();
    }

    /**
     * Validate a QR token and return the member if valid.
     */
    public function validateToken(string $token): ?Member
    {
        if ($token === '' || $token === '0' || strlen($token) !== 64) {
            return null;
        }

        return Member::where('qr_token', $token)->first();
    }

    /**
     * Get the check-in URL for a member.
     */
    public function getCheckInUrl(Member $member): string
    {
        $token = $member->getOrGenerateQrToken();

        return route('checkin.qr', ['token' => $token]);
    }

    /**
     * Get just the token for a member (for display in QR code scanners).
     */
    public function getMemberToken(Member $member): string
    {
        return $member->getOrGenerateQrToken();
    }
}
