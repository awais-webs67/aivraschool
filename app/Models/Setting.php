<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Platform settings stored as key => JSON value, cached as one array.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected const CACHE_KEY = 'platform-settings';

    /** Loaded once per request; money() alone may ask for the currency dozens of times. */
    protected static ?array $loaded = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }

        Cache::forget(self::CACHE_KEY);
        static::$loaded = null;
    }

    public static function flushMemory(): void
    {
        static::$loaded = null;
    }

    /** @return array<string, mixed> */
    protected static function all_(): array
    {
        if (static::$loaded !== null) {
            return static::$loaded;
        }

        try {
            return static::$loaded = Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('settings')
                ? static::query()->pluck('value', 'key')->map(fn ($v) => json_decode((string) $v, true))->all()
                : []);
        } catch (Throwable) {
            return []; // database not ready yet (fresh install)
        }
    }

    /** The currency plans and subscriptions are billed in. */
    public static function currency(): string
    {
        return static::get('currency') ?: config('skooline.currency');
    }

    /**
     * Currencies offered on the website price switcher, base first.
     * Each: code, name, rate (1 unit of base = rate units of this currency), decimals.
     *
     * @return list<array{code: string, name: string, rate: float, decimals: int}>
     */
    public static function websiteCurrencies(): array
    {
        $base = static::currency();
        $all = config('currencies');
        $list = [['code' => $base, 'name' => $all[$base][0] ?? $base, 'rate' => 1.0, 'decimals' => $all[$base][1] ?? 2]];

        foreach ((array) static::get('website_currencies', []) as $code => $perUnit) {
            // Stored as "1 {code} = {perUnit} {base}"
            if ($code !== $base && isset($all[$code]) && (float) $perUnit > 0) {
                $list[] = ['code' => $code, 'name' => $all[$code][0], 'rate' => 1 / (float) $perUnit, 'decimals' => $all[$code][1]];
            }
        }

        return $list;
    }
}
