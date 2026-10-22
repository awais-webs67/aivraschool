<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits every query on a school-owned model to the current school.
 *
 * Fails closed: with no current school, the query returns nothing. Platform
 * code that really needs every school's rows must say so explicitly with
 * Model::withoutGlobalScope(SchoolScope::class).
 */
class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = app(CurrentSchool::class)->id();

        if ($schoolId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('school_id'), $schoolId);
    }
}
