<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'tagline', 'description', 'billing_model', 'price', 'price_per_student', 'prices',
    'setup_fee', 'trial_days', 'cta_mode', 'cta_label', 'max_students', 'max_staff', 'max_branches', 'storage_gb',
    'sms_credits', 'modules', 'is_active', 'show_on_website', 'is_featured', 'sort_order',
])]
class Plan extends Model
{
    use HasFactory;

    /** What the plan's button on the website does. */
    public const CTA_MODES = [
        'trial' => 'Free trial: registration form, starts a trial',
        'register' => 'Show price: registration form, you approve and send the invoice',
        'stripe' => 'Show price: registration form, then pay online with Stripe',
        'contact' => 'Contact us: opens the demo request form',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'prices' => 'array',
            'price' => 'decimal:2',
            'price_per_student' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'show_on_website' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('price');
    }

    public function billingLabel(): string
    {
        return config("skooline.billing_models.{$this->billing_model}.label", $this->billing_model);
    }

    /** Months covered by one billing cycle, or null for lifetime plans. */
    public function cycleMonths(): ?int
    {
        return config("skooline.billing_models.{$this->billing_model}.months");
    }

    /** Unit shown after the price, e.g. "/ month", "one-time". */
    public function priceUnit(): string
    {
        return match ($this->billing_model) {
            'per_student' => '/ student / month',
            'lifetime' => 'one-time',
            'monthly' => '/ month',
            'yearly' => '/ year',
            default => '/ '.$this->cycleMonths().' months',
        };
    }

    public function isPerStudent(): bool
    {
        return $this->billing_model === 'per_student';
    }

    public function isLifetime(): bool
    {
        return $this->cycleMonths() === null;
    }

    /** Amount for one billing cycle (base price + per-student charge). */
    public function priceFor(int $students = 0): float
    {
        return round((float) $this->price + (float) $this->price_per_student * max(0, $students), 2);
    }

    /** Button text on the website, e.g. "Start 14-day free trial". */
    public function ctaLabel(): string
    {
        return $this->cta_label ?: match ($this->cta_mode) {
            'stripe' => 'Buy now',
            'register' => 'Get started',
            'contact' => 'Contact sales',
            default => $this->trial_days > 0 ? "Start {$this->trial_days}-day free trial" : 'Get started',
        };
    }

    /** The headline price: per-student rate for per-student plans, otherwise the plan price. */
    public function headlinePrice(): float
    {
        return (float) ($this->isPerStudent() ? $this->price_per_student : $this->price);
    }

    /**
     * Exact price set for another currency (e.g. the USD price), or null to convert with
     * the website rates. The billing currency always uses the plan's own price.
     */
    public function priceIn(string $currency): ?float
    {
        if ($currency === Setting::currency()) {
            return $this->headlinePrice();
        }

        $value = $this->prices[$currency] ?? null;

        return $value === null || $value === '' ? null : (float) $value;
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }
}
