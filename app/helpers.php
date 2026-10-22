<?php

use App\Models\School;
use App\Models\Setting;
use App\Support\Tenancy\CurrentSchool;

if (! function_exists('money')) {
    /**
     * Format an amount, e.g. "USD 4,500". Uses the given currency, else the current
     * school's currency inside a school portal, else the platform billing currency.
     */
    function money(float|int|string|null $amount, bool $decimals = false, ?string $currency = null): string
    {
        $currency ??= current_school()?->currencyCode() ?? Setting::currency();
        $places = $decimals ? (config("currencies.$currency.1") ?? 2) : 0;

        return $currency.' '.number_format((float) $amount, $places);
    }
}

if (! function_exists('current_school')) {
    /** The school the current request belongs to (null on platform pages). */
    function current_school(): ?School
    {
        return app(CurrentSchool::class)->get();
    }
}
