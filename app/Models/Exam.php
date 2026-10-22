<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An exam term with its papers. */
#[Fillable(['academic_session_id', 'name', 'starts_on', 'ends_on', 'is_locked', 'is_published'])]
class Exam extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_locked' => 'boolean', 'is_published' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function papers(): HasMany
    {
        return $this->hasMany(ExamSubject::class);
    }

    /** Grade bands from the school settings: [[min %, grade], …] highest first. */
    public static function gradeScale(): array
    {
        return (array) current_school()?->setting('exams.grades', [[90, 'A+'], [80, 'A'], [70, 'B'], [60, 'C'], [50, 'D'], [40, 'E'], [0, 'F']]);
    }

    public static function gradeFor(?float $percent): string
    {
        if ($percent === null) {
            return '—';
        }
        foreach (self::gradeScale() as [$min, $grade]) {
            if ($percent >= (float) $min) {
                return $grade;
            }
        }

        return 'F';
    }
}
