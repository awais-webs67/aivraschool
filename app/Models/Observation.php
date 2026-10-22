<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The principal's notes from watching a lesson. Ratings are 1 (weak) to 5 (excellent). */
class Observation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const AREAS = [
        'planning' => 'Lesson planning',
        'knowledge' => 'Subject knowledge',
        'delivery' => 'Teaching and explaining',
        'engagement' => 'Students involved',
        'control' => 'Class control',
        'assessment' => 'Checking understanding',
    ];

    public const SCALE = [1 => 'Weak', 2 => 'Needs work', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];

    protected function casts(): array
    {
        return ['date' => 'date', 'ratings' => 'array', 'overall' => 'decimal:1'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }
}
