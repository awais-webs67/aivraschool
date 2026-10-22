<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One paper: the subject of a class in an exam, with its maximum and pass marks. */
#[Fillable(['exam_id', 'class_id', 'subject_id', 'max_marks', 'pass_marks', 'paper_date'])]
class ExamSubject extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['max_marks' => 'decimal:2', 'pass_marks' => 'decimal:2', 'paper_date' => 'date'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }
}
