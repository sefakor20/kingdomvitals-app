<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PlatformInvoice;
use App\Services\PlatformInvoicePdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private PlatformInvoicePdfService $pdfService) {}

    public function download(PlatformInvoice $invoice): StreamedResponse
    {
        // Ensure invoice belongs to current tenant
        abort_unless($invoice->tenant_id === tenant()->getTenantKey(), 404);

        return $this->pdfService->download($invoice);
    }
}
