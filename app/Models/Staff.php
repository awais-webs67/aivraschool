<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person who works at the school. user_id links the portal login, if they have one.
 */
#[Fillable([
    'user_id', 'employee_no', 'name', 'father_name', 'gender', 'date_of_birth', 'national_id', 'phone', 'email', 'address',
    'designation', 'department', 'qualification', 'joining_date', 'salary', 'photo_path', 'status', 'left_on', 'notes',
])]
class Staff extends Model
{
    use BelongsToSchool;

    protected $table = 'staff';

    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    public const DEPARTMENTS = ['Teaching', 'Administration', 'Accounts', 'Front office', 'IT', 'Library', 'Transport', 'Support staff'];

    public const DESIGNATIONS = ['Principal', 'Vice Principal', 'Coordinator', 'Senior Teacher', 'Teacher', 'Assistant Teacher', 'Accountant', 'Clerk', 'Receptionist', 'Librarian', 'Lab Assistant', 'IT Officer', 'Driver', 'Guard', 'Peon'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'left_on' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Employee numbers run per school: EMP-0001, EMP-0002 … (kept if the admin typed one)
        static::creating(function (Staff $staff) {
            if (blank($staff->employee_no)) {
                $staff->employee_no = self::nextEmployeeNo();
            }
        });
    }

    public static function nextEmployeeNo(): string
    {
        $n = static::query()->count() + 1;

        do {
            $no = 'EMP-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (static::query()->where('employee_no', $no)->exists());

        return $no;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sections where this person is the class teacher. */
    public function classSections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_teacher_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    }
}
