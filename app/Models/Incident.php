<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A behaviour note on a student: a concern or a merit. */
class Incident extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const CONCERNS = ['Misbehaviour', 'Late coming', 'Uniform', 'Homework not done', 'Fighting', 'Bullying', 'Mobile phone', 'Absent without leave', 'Other'];

    public const MERITS = ['Good behaviour', 'Academic achievement', 'Sports', 'Helping others', 'Competition win', 'Other'];

    public const ACTIONS = ['None', 'Verbal warning', 'Written warning', 'Parent called', 'Parent meeting', 'Detention', 'Suspension', 'Certificate / prize'];

    public const SEVERITIES = ['low' => 'Minor', 'medium' => 'Serious', 'high' => 'Very serious'];

    protected function casts(): array
    {
        return ['date' => 'date', 'parent_informed' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function badge(): string
    {
        return $this->kind === 'merit' ? 'badge-success' : ['low' => 'badge-neutral', 'medium' => 'badge-warning', 'high' => 'badge-danger'][$this->severity];
    }
}
