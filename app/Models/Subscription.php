<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable([
    'tenant_id', 'plan_id', 'plan_name', 'billing_model', 'starts_at', 'ends_at', 'student_count',
    'amount', 'payment_status', 'payment_method', 'payment_reference', 'paid_at', 'notes', 'created_by',
])]
class Subscription extends Model
{
    use CentralConnection;

    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'partial', 'waived'];

    public const PAYMENT_METHODS = ['bank_transfer', 'cash', 'cheque', 'jazzcash', 'easypaisa', 'card', 'other'];

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
        return $this->belongsTo(School::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Monthly value of this subscription (used for MRR). Lifetime counts as 0. */
    public function monthlyValue(): float
    {
        $months = config("skooline.billing_models.{$this->billing_model}.months");

        return $months ? round((float) $this->amount / $months, 2) : 0.0;
    }
}
