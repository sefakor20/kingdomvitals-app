<?php

declare(strict_types=1);

use App\Models\Tenant;

it('persists onboarding state as a proper json object through repeated saves', function (): void {
    $tenant = Tenant::create(['id' => 'json-test', 'name' => 'JSON Test']);
    $tenant->initializeOnboarding();

    // Simulate the onboarding flow writing through each step.
    $tenant->completeOnboardingStep('organization');
    $tenant->setCurrentOnboardingStep(2);
    $tenant->completeOnboardingStep('team');
    $tenant->setCurrentOnboardingStep(3);
    $tenant->completeOnboardingStep('integrations');
    $tenant->setCurrentOnboardingStep(4);

    // Fresh read from DB — emulates a new request.
    $fresh = Tenant::query()->where('id', $tenant->id)->firstOrFail();

    // Raw DB inspection: the onboarding value stored inside `data` must be a JSON
    // object/array, never a string — otherwise the double-cast bug is back.
    $rawData = $fresh->getRawOriginal('data');
    expect($rawData)->toBeString();

    $decoded = json_decode((string) $rawData, true);
    expect($decoded)->toBeArray()
        ->and($decoded['onboarding'] ?? null)->toBeArray()
        ->and($decoded['onboarding']['current_step'])->toBe(4)
        ->and($decoded['onboarding']['steps']['organization']['completed'])->toBeTrue()
        ->and($decoded['onboarding']['steps']['team']['completed'])->toBeTrue()
        ->and($decoded['onboarding']['steps']['integrations']['completed'])->toBeTrue();

    expect($fresh->getCurrentOnboardingStep())->toBe(4);
});
