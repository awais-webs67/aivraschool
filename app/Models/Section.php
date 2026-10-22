<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A section of a class, e.g. "A" in "Class 7-A".
 */
#[Fillable(['class_id', 'name', 'capacity', 'class_teacher_id'])]
class Section extends Model
{
    use BelongsToSchool;

    /**
     * Every section grouped by class, for <select> lists: ['Class 1' => [id => 'Class 1 – A'], …]
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return static::query()->with('schoolClass')->get()
            ->sortBy(fn (Section $s) => [$s->schoolClass?->sort_order, $s->schoolClass?->name, $s->name])
            ->groupBy(fn (Section $s) => $s->schoolClass?->name ?? '—')
            ->map(fn ($sections) => $sections->mapWithKeys(fn (Section $s) => [$s->id => $s->label()])->all())
            ->all();
    }

    public function label(): string
    {
        return ($this->schoolClass?->name ?? '').' – '.$this->name;
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'class_teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
