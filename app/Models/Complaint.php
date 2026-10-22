<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const STATUSES = ['open' => 'Open', 'in_progress' => 'Being handled', 'resolved' => 'Resolved'];

    public const CATEGORIES = ['Teaching', 'Fees', 'Transport', 'Behaviour', 'Facilities', 'Staff', 'Other'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function statusBadge(): string
    {
        return ['open' => 'badge-danger', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success'][$this->status] ?? 'badge-neutral';
    }
}
