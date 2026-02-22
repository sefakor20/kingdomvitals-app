<?php

use App\Enums\EventVisibility;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Services\EventTicketPdfService;
use Illuminate\Support\Facades\URL;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
    $this->event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('event ticket pdf service generates pdf content', function (): void {
    $registration = EventRegistration::factory()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => 'EVT-TEST-0001',
        'guest_name' => 'John Doe',
        'guest_email' => 'john@example.com',
    ]);

    $service = app(EventTicketPdfService::class);
    $pdfContent = $service->generatePdf($registration);

    expect($pdfContent)->toBeString();
    expect($pdfContent)->toContain('%PDF'); // PDF magic bytes
});

test('ticket download route requires signed url', function (): void {
    $registration = EventRegistration::factory()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => 'EVT-TEST-0001',
    ]);

    // Unsigned URL should fail
    $this->get(route('events.public.ticket.download', [$this->branch, $this->event, $registration]))
        ->assertStatus(403);
});

test('ticket download works with signed url', function (): void {
    $registration = EventRegistration::factory()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => 'EVT-TEST-0001',
    ]);

    $signedUrl = URL::signedRoute('events.public.ticket.download', [$this->branch, $this->event, $registration]);

    $this->get($signedUrl)
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('ticket download fails if registration has no ticket number', function (): void {
    $registration = EventRegistration::factory()->create([
        'event_id' => $this->event->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => null,
    ]);

    $signedUrl = URL::signedRoute('events.public.ticket.download', [$this->branch, $this->event, $registration]);

    $this->get($signedUrl)
        ->assertStatus(404);
});

test('ticket download fails if registration does not belong to event', function (): void {
    $otherEvent = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
    ]);

    $registration = EventRegistration::factory()->create([
        'event_id' => $otherEvent->id,
        'branch_id' => $this->branch->id,
        'ticket_number' => 'EVT-TEST-0001',
    ]);

    $signedUrl = URL::signedRoute('events.public.ticket.download', [$this->branch, $this->event, $registration]);

    $this->get($signedUrl)
        ->assertStatus(404);
});
