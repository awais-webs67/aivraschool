<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_number', 'school_id', 'plan_id', 'plan_name', 'billing_model', 'starts_at', 'ends_at', 'student_count',
    'amount', 'payment_status', 'payment_method', 'payment_reference', 'paid_at', 'notes', 'created_by',
])]
class Subscription extends Model
{
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'partial', 'waived'];

    public const PAYMENT_METHODS = ['bank_transfer', 'cash', 'cheque', 'card', 'online', 'mobile_wallet', 'other'];

    protected static function booted(): void
    {
        // Invoice numbers follow the row id, so they are unique without locking: INV-2026-00042
        static::created(function (Subscription $subscription) {
            if (! $subscription->invoice_number) {
                $subscription->forceFill([
                    'invoice_number' => 'INV-'.$subscription->created_at->format('Y').'-'.str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function paymentBadge(): string
    {
        return match ($this->payment_status) {
            'paid' => 'badge-success',
            'unpaid' => 'badge-danger',
            'partial' => 'badge-warning',
            default => 'badge-neutral',
        };
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by');
    }

    /** Monthly value of this subscription (used for MRR). Lifetime counts as 0. */
    public function monthlyValue(): float
    {
        $months = config("skooline.billing_models.{$this->billing_model}.months");

        return $months ? round((float) $this->amount / $months, 2) : 0.0;
    }
}
