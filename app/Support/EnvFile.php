<?php

namespace App\Support;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

/**
 * Reads and writes the .env file, where integration secrets (SMTP password, Stripe and AI keys)
 * are kept so they never sit in the database.
 *
 * Secrets are read from the file itself, not env(), so they keep working when the
 * configuration is cached on the live server.
 */
class EnvFile
{
    /** @var array<string, string>|null */
    protected static ?array $values = null;

    protected static ?string $path = null;

    /** Point at another file (used by tests so the real .env is never touched). */
    public static function usePath(?string $path): void
    {
        static::$path = $path;
        static::$values = null;
    }

    public static function path(): string
    {
        return static::$path ?? app()->environmentFilePath();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::all()[$key] ?? null;

        return $value === null || $value === '' || $value === 'null' ? $default : $value;
    }

    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        if (static::$values === null) {
            try {
                static::$values = is_file(static::path()) ? Dotenv::parse((string) file_get_contents(static::path())) : [];
            } catch (Throwable) {
                static::$values = [];
            }
        }

        return static::$values;
    }

    /**
     * Save values (null removes the line). Existing lines are replaced in place,
     * new ones are appended under a "Skooline integrations" heading.
     *
     * @param  array<string, string|int|bool|null>  $values
     */
    public static function set(array $values): void
    {
        $path = static::path();

        if (! is_file($path) || ! is_writable($path)) {
            throw new RuntimeException('The .env file is missing or not writable. Make it writable (permission 644/664) and try again.');
        }

        $contents = (string) file_get_contents($path);

        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new RuntimeException("Invalid setting name {$key}.");
            }

            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if ($value === null) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*(\R|$)/m', '', $contents);

                continue;
            }

            $line = $key.'='.static::quote((string) $value);

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace_callback($pattern, fn () => $line, $contents, 1);
            } else {
                if (! str_contains($contents, '# Skooline integrations')) {
                    $contents = rtrim($contents)."\n\n# Skooline integrations (saved from the Super Admin panel)\n";
                }
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }

        file_put_contents($path, $contents, LOCK_EX);
        static::$values = null;

        // A cached configuration would keep the old values until it is rebuilt.
        if (app()->configurationIsCached()) {
            Artisan::call('config:cache');
        }
    }

    /** Quote a value only when .env syntax needs it. */
    protected static function quote(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+\-]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    /** Show only the start and end of a secret: "sk-a••••••••3f9c". */
    public static function mask(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }

        $length = strlen($secret);

        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        $show = min(4, intdiv($length, 5));

        return substr($secret, 0, $show).str_repeat('•', 8).substr($secret, -$show);
    }
}
