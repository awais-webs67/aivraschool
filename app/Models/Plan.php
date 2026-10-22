<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable([
    'name', 'slug', 'tagline', 'description', 'billing_model', 'price', 'price_per_student',
    'setup_fee', 'trial_days', 'max_students', 'max_staff', 'max_branches', 'storage_gb',
    'sms_credits', 'modules', 'is_active', 'show_on_website', 'is_featured', 'sort_order',
])]
class Plan extends Model
{
    use CentralConnection, HasFactory;

    protected function casts(): array
    {
        return [
            'modules' => 'array',
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

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }
}
