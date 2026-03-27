<?php

use App\Mail\VisitorFollowUpMail;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
    $this->visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'visitor@example.com',
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('it renders the email with message body', function (): void {
    $mail = new VisitorFollowUpMail(
        visitor: $this->visitor,
        messageBody: 'Hello, thank you for visiting us!',
        branch: $this->branch
    );

    $rendered = $mail->render();

    expect($rendered)
        ->toContain('Hello, thank you for visiting us!')
        ->toContain($this->branch->name);
});

test('it uses default subject when not provided', function (): void {
    $mail = new VisitorFollowUpMail(
        visitor: $this->visitor,
        messageBody: 'Test message',
        branch: $this->branch
    );

    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain($this->branch->name);
});

test('it uses custom subject when provided', function (): void {
    $mail = new VisitorFollowUpMail(
        visitor: $this->visitor,
        messageBody: 'Test message',
        branch: $this->branch,
        emailSubject: 'Custom Subject Line'
    );

    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Custom Subject Line');
});

test('it uses branch email settings when configured', function (): void {
    $this->branch->setSetting('email_sender_address', 'custom@church.org');
    $this->branch->setSetting('email_sender_name', 'Custom Sender');

    $mail = new VisitorFollowUpMail(
        visitor: $this->visitor,
        messageBody: 'Test message',
        branch: $this->branch
    );

    $envelope = $mail->envelope();

    expect($envelope->from->address)->toBe('custom@church.org')
        ->and($envelope->from->name)->toBe('Custom Sender');
});

test('it falls back to config when branch email settings not configured', function (): void {
    $mail = new VisitorFollowUpMail(
        visitor: $this->visitor,
        messageBody: 'Test message',
        branch: $this->branch
    );

    $envelope = $mail->envelope();

    expect($envelope->from->address)->toBe(config('mail.from.address'));
});
