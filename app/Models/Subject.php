<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'code', 'type'])]
class Subject extends Model
{
    use BelongsToSchool;

    public const TYPES = [
        'theory' => 'Theory',
        'practical' => 'Practical',
        'both' => 'Theory + Practical',
    ];

    /** Common subjects, offered as a one-click start. */
    public const STANDARD = [
        'English', 'Urdu', 'Mathematics', 'General Science', 'Islamiyat', 'Social Studies',
        'Computer Science', 'General Knowledge', 'Physics', 'Chemistry', 'Biology',
        'Pakistan Studies', 'Art & Drawing',
    ];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id')->ordered();
    }
}
