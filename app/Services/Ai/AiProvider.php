<?php

namespace App\Services\Ai;

/**
 * One configured AI provider: a template from config/ai.php (or a custom one)
 * merged with what the Super Admin saved.
 */
final class AiProvider
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $style,          // "openai" | "anthropic"
        public readonly string $baseUrl,
        public readonly string $model,
        public readonly array $models,
        public readonly string $keyEnv,          // .env name of the API key
        public readonly ?string $apiKey,
        public readonly bool $enabled,
        public readonly int $priority,
        public readonly bool $custom = false,
        public readonly bool $keyOptional = false,
        public readonly ?string $free = null,
        public readonly ?string $signup = null,
        public readonly ?array $lastTest = null,
    ) {}

    /** Has what it needs to be called (a key, unless the provider runs without one). */
    public function ready(): bool
    {
        return ($this->apiKey !== null || $this->keyOptional) && ! str_contains($this->baseUrl, 'YOUR_');
    }

    public function usable(): bool
    {
        return $this->enabled && $this->ready();
    }
}
