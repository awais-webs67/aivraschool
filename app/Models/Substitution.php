<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'timetable_entry_id', 'absent_staff_id', 'substitute_staff_id', 'created_by'])]
class Substitution extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class, 'timetable_entry_id');
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'substitute_staff_id');
    }
}
