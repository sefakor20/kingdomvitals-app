<?php

declare(strict_types=1);

beforeEach(function (): void {
    // Route is scoped to central domains; simulate being on kingdomvitals-app.test.
    $this->serverVariables = ['HTTP_HOST' => 'kingdomvitals-app.test'];
});

it('renders the offline page on central domains', function (): void {
    $this->get('http://kingdomvitals-app.test/offline')
        ->assertOk()
        ->assertSee('Connection Lost')
        ->assertSee('Check your connection');
});

it('includes the offline overlay markup on the home page', function (): void {
    $this->get('http://kingdomvitals-app.test/')
        ->assertOk()
        ->assertSee('id="offline-overlay"', escape: false);
});
