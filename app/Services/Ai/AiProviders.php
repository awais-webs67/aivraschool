<?php

namespace App\Services\Ai;

use App\Models\Setting;
use App\Support\EnvFile;
use Illuminate\Support\Collection;

/**
 * Every AI provider: the templates in config/ai.php plus custom ones added by the admin,
 * merged with saved choices (Setting "ai") and keys from .env.
 */
class AiProviders
{
    /** Saved AI settings: providers[key] = {enabled, model, base_url, priority, last_test}, custom[key] = {...}, edit_mode. */
    public static function settings(): array
    {
        return (array) Setting::get('ai', []) + ['providers' => [], 'custom' => [], 'edit_mode' => false];
    }

    public static function save(array $settings): void
    {
        Setting::put(['ai' => $settings]);
    }

    public static function keyEnv(string $key): string
    {
        return 'AI_'.strtoupper(str_replace('-', '_', $key)).'_API_KEY';
    }

    /** @return Collection<string, AiProvider> ordered by priority (lower first), then name */
    public static function all(): Collection
    {
        $settings = self::settings();
        $templates = collect(config('ai.providers'))->map(fn ($t) => $t + ['custom' => false]);
        $custom = collect($settings['custom'])->map(fn ($t) => $t + ['custom' => true, 'models' => [], 'free' => 'Custom provider']);

        $position = $templates->merge($custom)->keys()->flip();

        return $templates->merge($custom)
            ->map(function (array $t, string $key) use ($settings) {
                $saved = $settings['providers'][$key] ?? [];
                $env = self::keyEnv($key);

                return new AiProvider(
                    key: $key,
                    name: $t['name'],
                    style: $t['style'],
                    baseUrl: rtrim($saved['base_url'] ?? $t['base_url'], '/'),
                    model: $saved['model'] ?? $t['model'],
                    models: $t['models'] ?? [],
                    keyEnv: $env,
                    apiKey: EnvFile::get($env),
                    enabled: (bool) ($saved['enabled'] ?? false),
                    priority: (int) ($saved['priority'] ?? 50),
                    custom: $t['custom'],
                    keyOptional: (bool) ($t['key_optional'] ?? false),
                    free: $t['free'] ?? null,
                    signup: $t['signup'] ?? null,
                    lastTest: $saved['last_test'] ?? null,
                );
            })
            // Lower "Order" first; ties keep the catalog order (free, popular providers first).
            ->sortBy(fn (AiProvider $p) => sprintf('%03d-%03d', $p->priority, $position[$p->key]));
    }

    public static function find(string $key): ?AiProvider
    {
        return self::all()->get($key);
    }

    /** Providers the assistant may use, in the order they are tried. */
    public static function usable(): Collection
    {
        return self::all()->filter(fn (AiProvider $p) => $p->usable());
    }

    /** Merge values into one provider's saved settings. */
    public static function update(string $key, array $values): void
    {
        $settings = self::settings();
        $settings['providers'][$key] = array_merge($settings['providers'][$key] ?? [], $values);
        self::save($settings);
    }
}
