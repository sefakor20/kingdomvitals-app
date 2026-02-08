<?php

use App\Http\Controllers\Webhooks\TextTangoWebhookController;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Route;

// Webhook routes (work on all domains, no auth, no tenant middleware)
Route::post('/webhooks/texttango/delivery', [TextTangoWebhookController::class, 'handleDelivery'])
    ->name('webhooks.texttango.delivery');

// Central domain routes - explicitly bind to central domains only
// This prevents these routes from matching on tenant subdomains
$centralDomainsForLanding = [
    'kingdomvitals-app.test',
    'kingdomvitals.app',
    'localhost',
    '127.0.0.1',
];

foreach ($centralDomainsForLanding as $domain) {
    Route::domain($domain)->group(function () use ($domain) {
        Route::get('/', function () use ($domain) {
            // Redirect admin domains to admin login
            if (str_starts_with($domain, 'admin.')) {
                return redirect()->route('superadmin.login');
            }

            $plans = SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('price_monthly')
                ->get();

            return view('landing.index', compact('plans'));
        })->name($domain === 'kingdomvitals-app.test' || $domain === 'kingdomvitals.app' ? 'home' : null);
    });
}

// Admin domain redirects
foreach (['admin.kingdomvitals-app.test', 'admin.kingdomvitals.app', 'admin.localhost'] as $adminDomain) {
    Route::domain($adminDomain)->group(function () {
        Route::get('/', function () {
            return redirect()->route('superadmin.login');
        });
    });
}
