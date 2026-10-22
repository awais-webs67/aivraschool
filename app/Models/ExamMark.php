<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_subject_id', 'student_id', 'marks', 'is_absent', 'remarks', 'entered_by'])]
class ExamMark extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['marks' => 'decimal:2', 'is_absent' => 'boolean'];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class, 'exam_subject_id');
    }
}
