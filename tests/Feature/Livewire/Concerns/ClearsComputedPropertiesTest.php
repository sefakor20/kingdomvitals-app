<?php

use App\Livewire\Concerns\ClearsComputedProperties;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Livewire;

class TestComponentWithComputedProperties extends Component
{
    use ClearsComputedProperties;

    public int $branchId = 1;

    public int $clearAllCallCount = 0;

    public int $clearPrefixCallCount = 0;

    public int $clearSpecificCallCount = 0;

    #[Computed]
    public function totalMembers(): int
    {
        return 100;
    }

    #[Computed]
    public function quotaUsage(): array
    {
        return ['used' => 50, 'limit' => 100];
    }

    #[Computed]
    public function quotaWarning(): bool
    {
        return true;
    }

    #[Computed]
    public function aiInsightsEnabled(): bool
    {
        return true;
    }

    public function switchBranch(int $branchId): void
    {
        $this->branchId = $branchId;
        $this->clearAllComputedProperties();
        $this->clearAllCallCount++;
    }

    public function clearQuotaOnly(): void
    {
        $this->clearComputedPropertiesWithPrefix('quota');
        $this->clearPrefixCallCount++;
    }

    public function clearSpecific(): void
    {
        $this->clearComputedProperties('totalMembers', 'quotaUsage');
        $this->clearSpecificCallCount++;
    }

    public function render(): string
    {
        return '<div>Test Component</div>';
    }

    public function exposedGetComputedPropertyNames(): array
    {
        return $this->getComputedPropertyNames();
    }
}

describe('ClearsComputedProperties', function (): void {
    test('discovers all computed properties', function (): void {
        $component = new TestComponentWithComputedProperties;

        $propertyNames = $component->exposedGetComputedPropertyNames();

        expect($propertyNames)->toContain('totalMembers');
        expect($propertyNames)->toContain('quotaUsage');
        expect($propertyNames)->toContain('quotaWarning');
        expect($propertyNames)->toContain('aiInsightsEnabled');
        expect($propertyNames)->toHaveCount(4);
    });

    test('caches discovered properties per class', function (): void {
        $component1 = new TestComponentWithComputedProperties;
        $component2 = new TestComponentWithComputedProperties;

        $names1 = $component1->exposedGetComputedPropertyNames();
        $names2 = $component2->exposedGetComputedPropertyNames();

        expect($names1)->toBe($names2);
    });

    test('clearAllComputedProperties can be called without error', function (): void {
        Livewire::test(TestComponentWithComputedProperties::class)
            ->assertSet('branchId', 1)
            ->call('switchBranch', 2)
            ->assertSet('branchId', 2)
            ->assertSet('clearAllCallCount', 1);
    });

    test('clearComputedPropertiesWithPrefix can be called without error', function (): void {
        Livewire::test(TestComponentWithComputedProperties::class)
            ->call('clearQuotaOnly')
            ->assertSet('clearPrefixCallCount', 1);
    });

    test('clearComputedProperties can be called without error', function (): void {
        Livewire::test(TestComponentWithComputedProperties::class)
            ->call('clearSpecific')
            ->assertSet('clearSpecificCallCount', 1);
    });
});
