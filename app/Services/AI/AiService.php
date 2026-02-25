<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class AiService
{
    /**
     * The current provider being used.
     */
    protected ?string $provider = null;

    /**
     * Set the provider to use.
     */
    public function using(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get the current provider name.
     */
    public function getProvider(): string
    {
        return $this->provider ?? config('ai.default', 'anthropic');
    }

    /**
     * Get the model for the current provider.
     */
    public function getModel(): string
    {
        $provider = $this->getProvider();

        return config("ai.models.{$provider}", $this->getDefaultModelForProvider($provider));
    }

    /**
     * Generate text using the AI provider.
     */
    public function generate(string $prompt, ?string $systemPrompt = null): AgentResponse
    {
        $agent = $this->createAgent($systemPrompt ?? '');

        return $agent->prompt(
            $prompt,
            provider: $this->getProvider(),
            model: $this->getModel()
        );
    }

    /**
     * Generate text with fallback to alternative providers.
     */
    public function generateWithFallback(string $prompt, ?string $systemPrompt = null): AgentResponse
    {
        $providers = config('ai.fallback_chain', [$this->getProvider()]);
        $lastException = null;

        foreach ($providers as $provider) {
            try {
                $this->using($provider);

                return $this->generate($prompt, $systemPrompt);
            } catch (Throwable $e) {
                Log::warning("AI provider {$provider} failed", [
                    'error' => $e->getMessage(),
                    'tenant' => tenant()?->id,
                ]);
                $lastException = $e;
            }
        }

        throw $lastException ?? new \RuntimeException('All AI providers failed');
    }

    /**
     * Check if a specific AI feature is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return config("ai.features.{$feature}.enabled", false);
    }

    /**
     * Get the provider for a specific feature.
     */
    public function getProviderForFeature(string $feature): string
    {
        return config("ai.features.{$feature}.provider")
            ?? config('ai.default', 'anthropic');
    }

    /**
     * Create an anonymous agent with optional system prompt.
     */
    protected function createAgent(string $systemPrompt = ''): AnonymousAgent
    {
        return new AnonymousAgent(
            instructions: $systemPrompt,
            messages: [],
            tools: []
        );
    }

    /**
     * Get the default model for a provider.
     */
    protected function getDefaultModelForProvider(string $provider): string
    {
        return match ($provider) {
            'anthropic' => 'claude-3-5-sonnet-20241022',
            'openai' => 'gpt-4o',
            'gemini' => 'gemini-1.5-pro',
            'ollama' => 'llama3',
            'groq' => 'llama-3.1-70b-versatile',
            'mistral' => 'mistral-large-latest',
            'deepseek' => 'deepseek-chat',
            default => 'claude-3-5-sonnet-20241022',
        };
    }
}
