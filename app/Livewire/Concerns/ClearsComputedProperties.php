<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Computed;
use ReflectionClass;
use ReflectionMethod;

trait ClearsComputedProperties
{
    /**
     * Cache of discovered computed property names per class.
     *
     * @var array<class-string, array<string>>
     */
    private static array $computedPropertyCache = [];

    /**
     * Clear all cached computed properties.
     * Discovers properties using #[Computed] attribute.
     */
    protected function clearAllComputedProperties(): void
    {
        foreach ($this->getComputedPropertyNames() as $propertyName) {
            unset($this->{$propertyName});
        }
    }

    /**
     * Clear specific computed properties by name.
     *
     * @param  string  ...$propertyNames  The property names to clear
     */
    protected function clearComputedProperties(string ...$propertyNames): void
    {
        foreach ($propertyNames as $propertyName) {
            unset($this->{$propertyName});
        }
    }

    /**
     * Clear computed properties that match a pattern.
     *
     * @param  string  $pattern  Regex pattern to match property names
     */
    protected function clearComputedPropertiesMatching(string $pattern): void
    {
        foreach ($this->getComputedPropertyNames() as $propertyName) {
            if (preg_match($pattern, $propertyName)) {
                unset($this->{$propertyName});
            }
        }
    }

    /**
     * Clear computed properties by group/prefix.
     *
     * @param  string  $prefix  The prefix to match (e.g., 'quota', 'aiInsights')
     */
    protected function clearComputedPropertiesWithPrefix(string $prefix): void
    {
        $this->clearComputedPropertiesMatching('/^'.preg_quote($prefix, '/').'/i');
    }

    /**
     * Get all computed property names for the current component.
     * Results are cached per class.
     *
     * @return array<string>
     */
    protected function getComputedPropertyNames(): array
    {
        $class = static::class;

        if (! isset(self::$computedPropertyCache[$class])) {
            self::$computedPropertyCache[$class] = $this->discoverComputedProperties();
        }

        return self::$computedPropertyCache[$class];
    }

    /**
     * Discover computed properties using reflection.
     * Finds methods with #[Computed] attribute.
     *
     * @return array<string>
     */
    private function discoverComputedProperties(): array
    {
        $propertyNames = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $propertyName = $this->extractComputedPropertyName($method);

            if ($propertyName !== null) {
                $propertyNames[] = $propertyName;
            }
        }

        return array_unique($propertyNames);
    }

    /**
     * Extract computed property name from a method.
     * Returns null if the method is not a computed property.
     */
    private function extractComputedPropertyName(ReflectionMethod $method): ?string
    {
        $attributes = $method->getAttributes(Computed::class);

        if ($attributes !== []) {
            return $method->getName();
        }

        return null;
    }
}
