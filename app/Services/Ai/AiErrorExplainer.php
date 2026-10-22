<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Throwable;

/**
 * Turns provider errors into something an admin can act on:
 * wrong key, out of quota, wrong model, wrong endpoint, network trouble…
 * The same list is shown in the "?" help card on the AI setup page.
 */
class AiErrorExplainer
{
    /** type => [title, what it means and what to do] */
    public const TYPES = [
        'no_key' => ['No API key', 'Paste the provider\'s API key and save before testing.'],
        'invalid_key' => ['API key rejected', 'The key is wrong, expired or was deleted. Create a new key on the provider\'s website and paste it again (no spaces).'],
        'forbidden' => ['Access denied', 'The key works but is not allowed to use this model or endpoint (region, plan or permission). Try another model, or check the key\'s permissions.'],
        'no_credit' => ['No credit / billing needed', 'The account has no balance or needs a payment method. Add credit on the provider\'s website, or use a provider with a free tier.'],
        'quota' => ['Quota or rate limit reached', 'Too many requests, or the free daily/monthly limit is used up. Wait and try again later, or turn on another provider as a backup.'],
        'model_not_found' => ['Model not found', 'This model name does not exist for this provider (or was retired). Turn on "Edit endpoints & models" and pick one from "Load models".'],
        'bad_endpoint' => ['Wrong endpoint', 'The address does not answer like an AI API. Check the endpoint URL (it usually ends in /v1).'],
        'bad_request' => ['Request not accepted', 'The provider did not accept the request, often because the model does not support a setting. Try another model.'],
        'overloaded' => ['Provider busy or down', 'The provider\'s servers are overloaded or failing right now. Try again in a few minutes; other providers are used meanwhile.'],
        'timeout' => ['No answer in time', 'The provider took too long. It may be busy, or the model is slow. Try a smaller/faster model.'],
        'network' => ['Could not connect', 'The server could not reach the provider (internet, DNS, firewall or SSL problem). On localhost check your internet; on hosting ask whether outgoing connections are allowed.'],
        'refusal' => ['Declined by the model', 'The model refused this request for safety reasons. Rephrase the question.'],
        'empty' => ['Empty answer', 'The provider answered but with no text. Try another model or raise the answer length.'],
        'unknown' => ['Unexpected error', 'Something went wrong. The provider\'s own message is shown below.'],
    ];

    public static function fromResponse(int $status, ?string $message, ?string $provider = null): AiException
    {
        $m = strtolower((string) $message);

        $type = match (true) {
            $status === 401 || str_contains($m, 'invalid api key') || str_contains($m, 'invalid_api_key') || str_contains($m, 'incorrect api key') || str_contains($m, 'api key not valid') || str_contains($m, 'authentication') => 'invalid_key',
            $status === 402 || str_contains($m, 'insufficient') || str_contains($m, 'credit') || str_contains($m, 'billing') || str_contains($m, 'balance') || str_contains($m, 'payment') => 'no_credit',
            $status === 429 || str_contains($m, 'quota') || str_contains($m, 'rate limit') || str_contains($m, 'rate_limit') || str_contains($m, 'resource_exhausted') || str_contains($m, 'too many') => 'quota',
            $status === 403 => 'forbidden',
            str_contains($m, 'model') && ($status === 404 || $status === 400 || str_contains($m, 'not found') || str_contains($m, 'does not exist') || str_contains($m, 'not supported') || str_contains($m, 'unknown')) => 'model_not_found',
            in_array($status, [404, 405], true) => 'bad_endpoint',
            in_array($status, [400, 413, 422], true) => 'bad_request',
            $status === 408 || $status === 504 => 'timeout',
            $status >= 500 => 'overloaded',
            default => 'unknown',
        };

        return self::make($type, $status, $message, $provider);
    }

    public static function fromThrowable(Throwable $e, ?string $provider = null): AiException
    {
        if ($e instanceof AiException) {
            return $e;
        }

        $m = strtolower($e->getMessage());
        $type = match (true) {
            str_contains($m, 'timed out') || str_contains($m, 'timeout') => 'timeout',
            $e instanceof ConnectionException, str_contains($m, 'could not resolve'), str_contains($m, 'ssl'), str_contains($m, 'connection') => 'network',
            default => 'unknown',
        };

        return self::make($type, null, $e->getMessage(), $provider);
    }

    public static function make(string $type, ?int $status = null, ?string $detail = null, ?string $provider = null): AiException
    {
        [$title, $explanation] = self::TYPES[$type] ?? self::TYPES['unknown'];

        return new AiException($type, $title, $explanation, $status, $detail ? mb_substr($detail, 0, 500) : null, $provider);
    }
}
