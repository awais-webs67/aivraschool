<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A student of the school. Parents / guardian details are kept on the same row.
 */
class Student extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public const STATUSES = ['active' => 'Studying', 'left' => 'Left', 'graduated' => 'Passed out'];

    public const GENDERS = ['male' => 'Male', 'female' => 'Female'];

    public const CATEGORIES = ['Regular', 'Scholarship', 'Staff child', 'Sibling', 'Orphan', 'Special needs'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** Columns accepted by the form and the import (in this order on the import template). */
    public const IMPORT_COLUMNS = [
        'admission_no', 'name', 'gender', 'date_of_birth', 'class', 'section', 'roll_no', 'father_name', 'father_phone',
        'father_national_id', 'mother_name', 'mother_phone', 'address', 'city', 'national_id', 'admission_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'left_on' => 'date',
            'fee_discount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (blank($student->admission_no)) {
                $student->admission_no = self::nextAdmissionNo();
            }
        });

        // Siblings share a family code, worked out from the father's (or guardian's) phone.
        static::saving(function (Student $student) {
            $phone = preg_replace('/\D/', '', (string) ($student->father_phone ?: $student->guardian_phone ?: $student->mother_phone));
            $student->family_code = strlen($phone) >= 7 ? substr($phone, -10) : null;
        });
    }

    /** Next admission number: the year plus a running number, e.g. 2026-0042. */
    public static function nextAdmissionNo(): string
    {
        $prefix = now()->format('Y').'-';
        $n = static::query()->where('admission_no', 'like', $prefix.'%')->count() + 1;

        do {
            $no = $prefix.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (static::query()->where('admission_no', $no)->exists());

        return $no;
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** Other students with the same family code. */
    public function siblings(): Collection
    {
        return $this->family_code
            ? static::query()->where('family_code', $this->family_code)->whereKeyNot($this->id)->with('schoolClass', 'section')->get()
            : collect();
    }

    public function classLabel(): string
    {
        return trim(($this->schoolClass?->name ?? 'No class').($this->section ? ' – '.$this->section->name : ''));
    }

    public function parentPhone(): ?string
    {
        return $this->father_phone ?: $this->guardian_phone ?: $this->mother_phone;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
