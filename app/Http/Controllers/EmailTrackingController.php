<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\EmailLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    /**
     * Track email opens via a 1x1 transparent pixel.
     */
    public function pixel(Request $request, EmailLog $emailLog): Response
    {
        // Update the opened_at timestamp if not already set
        if (! $emailLog->opened_at) {
            $emailLog->update([
                'opened_at' => now(),
            ]);
        }

        // Return a 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Track email link clicks and redirect to the original URL.
     */
    public function click(Request $request, EmailLog $emailLog): RedirectResponse
    {
        // Update the clicked_at timestamp if not already set
        if (! $emailLog->clicked_at) {
            $emailLog->update([
                'clicked_at' => now(),
            ]);
        }

        // Also mark as opened if not already
        if (! $emailLog->opened_at) {
            $emailLog->update([
                'opened_at' => now(),
            ]);
        }

        // Get the original URL from the query parameter
        $encodedUrl = $request->query('url');
        if (! $encodedUrl) {
            return redirect('/');
        }

        $originalUrl = base64_decode($encodedUrl);

        // Validate the URL
        if (! filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return redirect('/');
        }

        return redirect()->away($originalUrl);
    }
}
