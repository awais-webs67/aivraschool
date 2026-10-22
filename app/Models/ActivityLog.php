<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the audit trail. Written through App\Support\Activity.
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Icon for the activity feed, picked from the action's first word. */
    public function icon(): string
    {
        return match (strtok($this->action, '.')) {
            'school' => 'building',
            'subscription', 'payment' => 'banknotes',
            'plan' => 'layers',
            'team', 'login', 'impersonation' => 'user-circle',
            'announcement' => 'chat',
            'ticket' => 'inbox',
            'lead' => 'clipboard',
            'settings', 'system' => 'sliders',
            default => 'info',
        };
    }
}
