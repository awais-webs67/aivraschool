<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An admission enquiry from a parent, followed up until admitted or lost. */
class Enquiry extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const SOURCES = ['walk_in' => 'Walk-in', 'phone' => 'Phone call', 'website' => 'Online form', 'referral' => 'Referral', 'social' => 'Social media', 'other' => 'Other'];

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'visit' => 'Visit / test booked', 'admitted' => 'Admitted', 'lost' => 'Not joining'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'follow_up_on' => 'date'];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'new' => 'badge-brand',
            'contacted', 'visit' => 'badge-warning',
            'admitted' => 'badge-success',
            default => 'badge-neutral',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['admitted', 'lost'], true);
    }
}
