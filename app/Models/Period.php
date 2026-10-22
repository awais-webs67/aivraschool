<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** One slot of the school day (a lesson or a break). */
#[Fillable(['name', 'starts_at', 'ends_at', 'is_break', 'sort_order'])]
class Period extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('starts_at')->orderBy('sort_order');
    }

    public function timeRange(): string
    {
        return substr((string) $this->starts_at, 0, 5).'–'.substr((string) $this->ends_at, 0, 5);
    }
}
