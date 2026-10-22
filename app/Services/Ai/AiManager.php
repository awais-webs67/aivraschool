<?php

namespace App\Services\Ai;

/**
 * Asks the enabled providers in priority order and returns the first good answer,
 * so a provider that is out of free quota is skipped automatically.
 */
class AiManager
{
    public function __construct(protected AiClient $client) {}

    public static function available(): bool
    {
        return AiProviders::usable()->isNotEmpty();
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{text: string, input_tokens: int, output_tokens: int, model: string, provider: string, ms: int}
     */
    public function chat(string $system, array $messages, ?int $maxTokens = null): array
    {
        $maxTokens ??= (int) config('ai.max_tokens', 700);
        $errors = [];

        foreach (AiProviders::usable() as $provider) {
            try {
                $result = $this->client->chat($provider, $system, $messages, $maxTokens);

                if ($result['text'] === '') {
                    throw AiErrorExplainer::make('empty', 200, null, $provider->name);
                }

                return $result;
            } catch (AiException $e) {
                $errors[] = $e;
                report($e);
            }
        }

        throw $errors === []
            ? AiErrorExplainer::make('no_key', null, 'No AI provider is switched on. Super Admin → AI setup.')
            : end($errors);
    }
}
