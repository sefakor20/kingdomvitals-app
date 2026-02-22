<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Services\EventTicketPdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventTicketController extends Controller
{
    public function __construct(
        private EventTicketPdfService $ticketPdfService
    ) {}

    public function download(Branch $branch, Event $event, EventRegistration $registration): StreamedResponse
    {
        abort_unless($registration->event_id === $event->id, 404);
        abort_unless($registration->branch_id === $branch->id, 404);
        abort_unless($registration->ticket_number !== null, 404);

        return $this->ticketPdfService->downloadTicket($registration);
    }
}
