<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A lesson in the weekly timetable. */
#[Fillable(['section_id', 'day', 'period_id', 'subject_id', 'staff_id', 'room'])]
class TimetableEntry extends Model
{
    use BelongsToSchool;

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Days the school is open (from school settings, Monday–Saturday by default). */
    public static function workingDays(): array
    {
        $days = (array) current_school()?->setting('timetable.days', [1, 2, 3, 4, 5, 6]);

        return array_intersect_key(self::DAYS, array_flip($days));
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
