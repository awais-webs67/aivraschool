<?php

namespace App\Services;

use App\Support\EnvFile;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Stripe client over HTTPS (Checkout + webhooks); no SDK package needed.
 * Keys live in .env: STRIPE_KEY (publishable), STRIPE_SECRET, STRIPE_WEBHOOK_SECRET.
 */
class Stripe
{
    public const API = 'https://api.stripe.com/v1';

    /** Currencies Stripe charges without minor units. */
    public const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    public static function configured(): bool
    {
        return EnvFile::has('STRIPE_SECRET');
    }

    public static function testMode(): bool
    {
        return str_starts_with((string) EnvFile::get('STRIPE_SECRET'), 'sk_test_');
    }

    protected function http(): PendingRequest
    {
        $secret = EnvFile::get('STRIPE_SECRET') ?? throw new RuntimeException('Stripe is not set up.');

        return Http::withToken($secret)->asForm()->acceptJson()->timeout(20);
    }

    /** Checks the secret key. Returns [ok, message]. */
    public function test(): array
    {
        $response = $this->http()->get(self::API.'/balance');

        return $response->successful()
            ? [true, 'Connected to Stripe ('.(self::testMode() ? 'test mode' : 'LIVE mode').').']
            : [false, self::explain($response)];
    }

    /**
     * One-time payment page hosted by Stripe.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckout(string $name, float $amount, string $currency, string $email, string $reference, string $successUrl, string $cancelUrl): array
    {
        $currency = strtoupper($currency);
        $unitAmount = in_array($currency, self::ZERO_DECIMAL, true) ? (int) round($amount) : (int) round($amount * 100);

        $response = $this->http()->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => $reference,
            'customer_email' => $email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][unit_amount]' => $unitAmount,
            'line_items[0][price_data][product_data][name]' => $name,
            'metadata[reference]' => $reference,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(self::explain($response));
        }

        return ['id' => $response->json('id'), 'url' => $response->json('url')];
    }

    /** @return array<string, mixed> */
    public function retrieveCheckout(string $id): array
    {
        $response = $this->http()->get(self::API.'/checkout/sessions/'.rawurlencode($id));

        if (! $response->successful()) {
            throw new RuntimeException(self::explain($response));
        }

        return $response->json();
    }

    /**
     * Verifies the Stripe-Signature header (HMAC-SHA256 of "timestamp.payload").
     * Returns the event, or null when the signature is wrong or too old.
     *
     * @return array<string, mixed>|null
     */
    public static function verifyWebhook(string $payload, ?string $header, int $tolerance = 300): ?array
    {
        $secret = EnvFile::get('STRIPE_WEBHOOK_SECRET');

        if (! $secret || ! $header) {
            return null;
        }

        $parts = [];
        foreach (explode(',', $header) as $item) {
            [$k, $v] = array_pad(explode('=', trim($item), 2), 2, '');
            $parts[$k][] = $v;
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $valid = collect($parts['v1'] ?? [])->contains(fn ($sig) => hash_equals($expected, $sig));

        if (! $valid || abs(time() - $timestamp) > $tolerance) {
            return null;
        }

        return json_decode($payload, true) ?: null;
    }

    /** Plain-language meaning of a Stripe error response. */
    public static function explain(Response $response): string
    {
        $message = $response->json('error.message') ?? 'Unknown error';

        return match (true) {
            $response->status() === 401 => 'The secret key is wrong or was deleted. Copy it again from Stripe → Developers → API keys (starts with sk_test_ or sk_live_).',
            $response->status() === 403 => 'This key is not allowed to do that (a restricted key). Use the standard secret key.',
            $response->status() === 429 => 'Too many requests to Stripe. Wait a minute and try again.',
            $response->status() >= 500 => 'Stripe is having problems right now. Try again later.',
            default => "Stripe said: {$message}",
        };
    }
}
