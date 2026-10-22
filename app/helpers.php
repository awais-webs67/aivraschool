<?php

if (! function_exists('money')) {
    /** Format an amount in the platform currency, e.g. "PKR 4,500". */
    function money(float|int|string|null $amount, bool $decimals = false): string
    {
        return config('skooline.currency').' '.number_format((float) $amount, $decimals ? 2 : 0);
    }
}
