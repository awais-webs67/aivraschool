<?php

use App\Models\School;
use App\Support\Tenancy\CurrentSchool;

if (! function_exists('money')) {
    /** Format an amount in the platform currency, e.g. "PKR 4,500". */
    function money(float|int|string|null $amount, bool $decimals = false): string
    {
        return config('skooline.currency').' '.number_format((float) $amount, $decimals ? 2 : 0);
    }
}

if (! function_exists('current_school')) {
    /** The school the current request belongs to (null on platform pages). */
    function current_school(): ?School
    {
        return app(CurrentSchool::class)->get();
    }
}
