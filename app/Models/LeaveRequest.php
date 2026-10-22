<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A staff member's leave application. */
class LeaveRequest extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const TYPES = ['casual' => 'Casual leave', 'sick' => 'Sick leave', 'annual' => 'Annual leave', 'maternity' => 'Maternity / paternity', 'unpaid' => 'Unpaid leave', 'other' => 'Other'];

    protected function casts(): array
    {
        return ['from_date' => 'date', 'to_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusBadge(): string
    {
        return ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'][$this->status] ?? 'badge-neutral';
    }

    public function period(): string
    {
        return $this->from_date->equalTo($this->to_date)
            ? $this->from_date->format('d M Y')
            : $this->from_date->format('d M').' – '.$this->to_date->format('d M Y');
    }
}
