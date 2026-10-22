<?php

namespace App\Support\Tenancy;

use App\Models\School;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Use on every model whose table has a school_id column.
 *
 * - Queries only see the current school's rows (SchoolScope).
 * - New rows get the current school's id automatically.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function ($model) {
            if ($model->school_id === null) {
                $model->school_id = app(CurrentSchool::class)->id()
                    ?? throw new LogicException('Cannot create '.class_basename($model).' without a current school.');
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
