<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->cachePath = storage_path('app/public/decks/kingdom-vitals-overview.pdf');
    File::ensureDirectoryExists(dirname($this->cachePath));
    File::delete($this->cachePath);
});

afterEach(function (): void {
    File::delete($this->cachePath);
});

it('serves the cached PDF when it is fresher than the deck view', function (): void {
    $viewPath = resource_path('views/presentations/overview.blade.php');
    $viewMtime = File::lastModified($viewPath);

    File::put($this->cachePath, '%PDF-1.4 fake-pdf-bytes');
    touch($this->cachePath, $viewMtime + 60);

    $response = $this->get('http://kingdomvitals-app.test/deck/pdf');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('inline')
        ->toContain('kingdom-vitals-overview.pdf');
});

it('serves the PDF as an attachment when download=1 is passed', function (): void {
    $viewPath = resource_path('views/presentations/overview.blade.php');
    $viewMtime = File::lastModified($viewPath);

    File::put($this->cachePath, '%PDF-1.4 fake-pdf-bytes');
    touch($this->cachePath, $viewMtime + 60);

    $response = $this->get('http://kingdomvitals-app.test/deck/pdf?download=1');

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('sets a public Cache-Control header on cached responses', function (): void {
    $viewPath = resource_path('views/presentations/overview.blade.php');
    $viewMtime = File::lastModified($viewPath);

    File::put($this->cachePath, '%PDF-1.4 fake-pdf-bytes');
    touch($this->cachePath, $viewMtime + 60);

    $this->get('http://kingdomvitals-app.test/deck/pdf')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'max-age=300, public');
});
