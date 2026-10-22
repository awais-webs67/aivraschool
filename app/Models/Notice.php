<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A notice on the school notice board. */
#[Fillable(['title', 'body', 'audience', 'section_id', 'is_pinned', 'publish_on', 'expires_on', 'attachment_path', 'attachment_name', 'created_by'])]
class Notice extends Model
{
    use BelongsToSchool;

    public const AUDIENCES = ['everyone' => 'Everyone', 'staff' => 'Staff only', 'parents' => 'Parents only', 'section' => 'One class'];

    protected function casts(): array
    {
        return ['publish_on' => 'date', 'expires_on' => 'date', 'is_pinned' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Notices showing today. */
    public function scopeLive(Builder $query): void
    {
        $query->whereDate('publish_on', '<=', today())
            ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', today()));
    }

    /** Notices for staff (everyone + staff + class notices). */
    public function scopeForStaff(Builder $query): void
    {
        $query->whereIn('audience', ['everyone', 'staff', 'section']);
    }

    /** Notices a parent of these sections may see. */
    public function scopeForParents(Builder $query, array $sectionIds): void
    {
        $query->where(fn ($q) => $q->whereIn('audience', ['everyone', 'parents'])
            ->orWhere(fn ($q) => $q->where('audience', 'section')->whereIn('section_id', $sectionIds)));
    }

    public function audienceLabel(): string
    {
        return $this->audience === 'section' ? ($this->section?->label() ?? 'One class') : self::AUDIENCES[$this->audience];
    }
}
