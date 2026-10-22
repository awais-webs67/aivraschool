<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Discount code a school enters when registering or renewing. */
#[Fillable(['code', 'description', 'type', 'value', 'plan_id', 'max_uses', 'used_count', 'expires_at', 'is_active'])]
class Coupon extends Model
{
    public const TYPES = ['percent' => 'Percent off', 'fixed' => 'Fixed amount off'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Codes are stored upper-case so "save10" and "SAVE10" are the same. */
    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Why this code can't be used for the plan, or null when it can. */
    public function problemFor(?Plan $plan): ?string
    {
        return match (true) {
            ! $this->is_active => 'This discount code is not active.',
            $this->expires_at && $this->expires_at->endOfDay()->isPast() => 'This discount code has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'This discount code has been used up.',
            $this->plan_id && $plan && $this->plan_id !== $plan->id => 'This discount code is not valid for the '.$plan->name.' plan.',
            default => null,
        };
    }

    /** Discount on an amount, never more than the amount itself. */
    public function discountOn(float $amount): float
    {
        $discount = $this->type === 'percent' ? $amount * min((float) $this->value, 100) / 100 : (float) $this->value;

        return round(min($discount, $amount), 2);
    }

    public function label(): string
    {
        return $this->type === 'percent' ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off' : money($this->value).' off';
    }
}
