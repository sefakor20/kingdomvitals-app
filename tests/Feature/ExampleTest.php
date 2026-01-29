<?php

use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('returns a successful response', function (): void {
    // Root route redirects to dashboard in tenant context
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});
