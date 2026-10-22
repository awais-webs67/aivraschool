<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notice from AivraSol shown on school dashboards.
 */
#[Fillable(['title', 'body', 'tone', 'audience', 'plan_id', 'school_id', 'starts_on', 'ends_on', 'is_active', 'created_by'])]
class Announcement extends Model
{
    public const TONES = ['info' => 'Information', 'success' => 'Good news', 'warning' => 'Important', 'danger' => 'Urgent'];

    public const AUDIENCES = ['all' => 'All schools', 'plan' => 'Schools on a plan', 'school' => 'One school'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Active today and addressed to this school. */
    public function scopeVisibleTo(Builder $query, School $school): void
    {
        $today = now()->toDateString();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->where(fn ($q) => $q->where('audience', 'all')
                ->orWhere(fn ($q) => $q->where('audience', 'plan')->where('plan_id', $school->plan_id))
                ->orWhere(fn ($q) => $q->where('audience', 'school')->where('school_id', $school->id)));
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'plan' => 'Plan: '.($this->plan?->name ?? '—'),
            'school' => $this->school?->name ?? 'One school',
            default => 'All schools',
        };
    }

    public function isLive(): bool
    {
        return $this->is_active
            && (! $this->starts_on || $this->starts_on->lte(today()))
            && (! $this->ends_on || $this->ends_on->gte(today()));
    }
}
