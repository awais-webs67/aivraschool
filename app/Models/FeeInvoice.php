<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A fee voucher (challan) for one student. */
class FeeInvoice extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const STATUSES = ['unpaid' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'fine' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $invoice) {
            $invoice->invoice_no ??= self::nextNumber('FV-', 'invoice_no', static::query());
        });
    }

    /** Running number per school with a prefix: FV-000123. */
    public static function nextNumber(string $prefix, string $column, Builder $query): string
    {
        $last = (clone $query)->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column);
        $n = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class)->orderBy('paid_on');
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function balance(): float
    {
        return $this->status === 'cancelled' ? 0.0 : round((float) $this->total - (float) $this->paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->balance() > 0 && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /** Works out paid / part paid / unpaid from the amounts. */
    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $this->status = $this->balance() <= 0 ? 'paid' : ((float) $this->paid > 0 ? 'partial' : 'unpaid');
    }

    public function statusBadge(): string
    {
        return ['paid' => 'badge-success', 'partial' => 'badge-warning', 'unpaid' => 'badge-danger'][$this->status] ?? 'badge-neutral';
    }
}
