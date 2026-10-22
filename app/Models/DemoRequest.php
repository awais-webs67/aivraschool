<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'school_name', 'email', 'phone', 'city', 'students', 'message', 'notes', 'follow_up_on', 'status', 'school_id', 'ip_address'])]
class DemoRequest extends Model
{
    public const STATUSES = ['new', 'contacted', 'demo_done', 'converted', 'closed'];

    public const STATUS_LABELS = ['new' => 'New', 'contacted' => 'Contacted', 'demo_done' => 'Demo done', 'converted' => 'Converted', 'closed' => 'Closed'];

    protected function casts(): array
    {
        return ['follow_up_on' => 'date'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'new' => 'badge-brand',
            'contacted' => 'badge-info',
            'demo_done' => 'badge-warning',
            'converted' => 'badge-success',
            default => 'badge-neutral',
        };
    }

    public function followUpDue(): bool
    {
        return $this->follow_up_on && $this->follow_up_on->lte(today()) && ! in_array($this->status, ['converted', 'closed'], true);
    }
}
