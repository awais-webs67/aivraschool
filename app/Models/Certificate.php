<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A certificate issued to a student, kept for the record. */
class Certificate extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const TYPES = [
        'leaving' => 'School Leaving Certificate',
        'character' => 'Character Certificate',
        'bonafide' => 'Bonafide (Studying) Certificate',
    ];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'details' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
