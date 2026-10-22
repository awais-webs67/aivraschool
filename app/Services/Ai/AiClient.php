<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Talks to one provider. Two wire formats cover all providers:
 * OpenAI-compatible chat completions, and Anthropic's Messages API.
 */
class AiClient
{
    /**
     * @param  list<array{role: string, content: string}>  $messages  user/assistant turns
     * @return array{text: string, input_tokens: int, output_tokens: int, model: string, provider: string, ms: int}
     */
    public function chat(AiProvider $provider, string $system, array $messages, int $maxTokens): array
    {
        if (! $provider->ready()) {
            throw AiErrorExplainer::make(str_contains($provider->baseUrl, 'YOUR_') ? 'bad_endpoint' : 'no_key', provider: $provider->name);
        }

        $started = microtime(true);

        try {
            $response = $provider->style === 'anthropic'
                ? $this->http($provider)->post($provider->baseUrl.'/messages', $this->anthropicBody($provider, $system, $messages, $maxTokens))
                : $this->http($provider)->post($provider->baseUrl.'/chat/completions', $this->openAiBody($provider, $system, $messages, $maxTokens));
        } catch (Throwable $e) {
            throw AiErrorExplainer::fromThrowable($e, $provider->name);
        }

        if (! $response->successful()) {
            throw AiErrorExplainer::fromResponse($response->status(), $this->errorMessage($response), $provider->name);
        }

        $result = $provider->style === 'anthropic' ? $this->parseAnthropic($response) : $this->parseOpenAi($response);

        return $result + ['model' => $provider->model, 'provider' => $provider->name, 'ms' => (int) round((microtime(true) - $started) * 1000)];
    }

    /** Model names the provider offers right now (for the "Load models" button). */
    public function models(AiProvider $provider): array
    {
        try {
            $response = $this->http($provider)->timeout(15)->get($provider->baseUrl.'/models');
        } catch (Throwable $e) {
            throw AiErrorExplainer::fromThrowable($e, $provider->name);
        }

        if (! $response->successful()) {
            throw AiErrorExplainer::fromResponse($response->status(), $this->errorMessage($response), $provider->name);
        }

        return collect($response->json('data') ?? $response->json('models') ?? [])
            ->map(fn ($m) => is_array($m) ? ($m['id'] ?? $m['name'] ?? null) : $m)
            ->filter()->map(fn ($id) => preg_replace('#^models/#', '', (string) $id))
            ->unique()->sort()->values()->all();
    }

    protected function http(AiProvider $provider): PendingRequest
    {
        $request = Http::acceptJson()->asJson()->timeout((int) config('ai.timeout', 30))->connectTimeout(10);

        if ($provider->style === 'anthropic') {
            return $request->withHeaders(['x-api-key' => (string) $provider->apiKey, 'anthropic-version' => '2023-06-01']);
        }

        if ($provider->key === 'openrouter') {
            $request = $request->withHeaders(['HTTP-Referer' => url('/'), 'X-Title' => 'Skooline']);
        }

        return $provider->apiKey ? $request->withToken($provider->apiKey) : $request;
    }

    protected function openAiBody(AiProvider $provider, string $system, array $messages, int $maxTokens): array
    {
        return [
            'model' => $provider->model,
            'messages' => array_merge([['role' => 'system', 'content' => $system]], $messages),
            // OpenAI's newer models only accept max_completion_tokens.
            $provider->key === 'openai' ? 'max_completion_tokens' : 'max_tokens' => $maxTokens,
        ];
    }

    protected function anthropicBody(AiProvider $provider, string $system, array $messages, int $maxTokens): array
    {
        $body = [
            'model' => $provider->model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => $messages,
        ];

        // Current Claude models think adaptively; low effort keeps answers quick and token use small.
        if (preg_match('/^claude-(opus|sonnet|haiku|fable)-5|^claude-opus-4-[678]|^claude-sonnet-4-6/', $provider->model)) {
            $body['output_config'] = ['effort' => 'low'];
        }

        return $body;
    }

    protected function parseOpenAi(Response $response): array
    {
        return [
            'text' => trim((string) $response->json('choices.0.message.content')),
            'input_tokens' => (int) $response->json('usage.prompt_tokens', 0),
            'output_tokens' => (int) $response->json('usage.completion_tokens', 0),
        ];
    }

    protected function parseAnthropic(Response $response): array
    {
        if ($response->json('stop_reason') === 'refusal') {
            throw AiErrorExplainer::make('refusal', 200, $response->json('stop_details.explanation'));
        }

        // Only text blocks carry the answer (thinking blocks are skipped).
        $text = collect($response->json('content') ?? [])->where('type', 'text')->pluck('text')->join("\n");

        return [
            'text' => trim($text),
            'input_tokens' => (int) $response->json('usage.input_tokens', 0),
            'output_tokens' => (int) $response->json('usage.output_tokens', 0),
        ];
    }

    protected function errorMessage(Response $response): string
    {
        $json = $response->json();

        $message = data_get($json, 'error.message') ?? data_get($json, 'message') ?? data_get($json, 'detail')
            ?? data_get($json, '0.error.message') ?? (is_string(data_get($json, 'error')) ? data_get($json, 'error') : null);

        return (string) ($message ?? mb_substr(strip_tags($response->body()), 0, 300));
    }
}
