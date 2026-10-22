<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

/**
 * Works out an invoice: price − discount code + tax (GST / VAT / sales tax from Settings → General).
 */
class InvoiceCalculator
{
    /**
     * @return array{subtotal: float, discount: float, coupon_code: ?string, tax_name: ?string, tax_rate: float, tax_amount: float, total: float}
     *
     * @throws ValidationException when $strict and the coupon can't be used
     */
    public static function calculate(float $subtotal, ?string $couponCode = null, ?Plan $plan = null, bool $strict = true): array
    {
        $discount = 0.0;
        $applied = null;

        if (trim((string) $couponCode) !== '') {
            $coupon = Coupon::findByCode($couponCode);
            $problem = $coupon ? $coupon->problemFor($plan) : 'This discount code does not exist.';

            if ($problem === null) {
                $discount = $coupon->discountOn($subtotal);
                $applied = $coupon->code;
            } elseif ($strict) {
                throw ValidationException::withMessages(['coupon_code' => $problem]);
            }
        }

        $rate = self::taxRate();
        $taxable = max($subtotal - $discount, 0);
        $tax = round($taxable * $rate / 100, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'coupon_code' => $applied,
            'tax_name' => $rate > 0 ? self::taxName() : null,
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'total' => round($taxable + $tax, 2),
        ];
    }

    public static function taxRate(): float
    {
        return max(0, min(100, (float) Setting::get('tax_rate', 0)));
    }

    public static function taxName(): string
    {
        return Setting::get('tax_name') ?: 'Tax';
    }

    /** Counts one use of a code (called once the invoice exists). */
    public static function redeem(?string $code): void
    {
        if ($code) {
            Coupon::where('code', strtoupper($code))->increment('used_count');
        }
    }
}
