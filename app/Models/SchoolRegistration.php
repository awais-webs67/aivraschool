<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A school that signed up from the website "Register your school" form.
 * The Super Admin approves it (or it is approved automatically) to create the real school.
 */
#[Hidden(['admin_password'])]
class SchoolRegistration extends Model
{
    public const STATUSES = ['pending' => 'Waiting for review', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public const CHOICES = ['trial' => 'Free trial', 'pay' => 'Paid online', 'request' => 'Wants to subscribe'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /** Waiting on Stripe: the school chose to pay online but has not finished paying. */
    public function awaitingPayment(): bool
    {
        return $this->billing_choice === 'pay' && ! $this->isPaid();
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => $this->awaitingPayment() ? 'badge-warning' : 'badge-brand',
        };
    }

    public function statusLabel(): string
    {
        return $this->isPending() && $this->awaitingPayment() ? 'Awaiting payment' : (self::STATUSES[$this->status] ?? $this->status);
    }

    public function number(): string
    {
        return 'REG-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
