<?php

namespace App\Models;

use App\Support\SchoolAccess;
use App\Support\Tenancy\BelongsToSchool;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A person using one school's portal (admin, principal, staff, teacher...).
 */
#[Fillable(['school_id', 'name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToSchool, HasFactory;

    public const ROLES = [
        'admin' => 'School Admin',
        'principal' => 'Principal',
        'vice_principal' => 'Vice Principal / Coordinator',
        'teacher' => 'Teacher',
        'accountant' => 'Accountant',
        'receptionist' => 'Receptionist',
        'staff' => 'Staff',
    ];

    /** What each role can do, shown when giving someone a login. */
    public const ROLE_HINTS = [
        'admin' => 'Everything, including setup, staff logins and billing.',
        'principal' => 'Everything, plus approvals and school-wide reports.',
        'vice_principal' => 'Academics: attendance, timetable, exams and homework.',
        'teacher' => 'Their own classes: attendance, homework and marks.',
        'accountant' => 'Fees, expenses and payroll.',
        'receptionist' => 'Admissions enquiries, visitors and student lookup.',
        'staff' => 'Dashboard and notices only.',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /** Role and plan check, see App\Support\SchoolAccess. */
    public function allows(string $ability): bool
    {
        return SchoolAccess::allows($this, $ability);
    }

    /**
     * Sections this user may take attendance / marks for: null means every section
     * (principal, admin, vice principal); a teacher gets the sections they are class teacher of
     * or teach in the timetable.
     *
     * @return list<int>|null
     */
    public function teachingSectionIds(): ?array
    {
        if ($this->allows('attendance.all')) {
            return null;
        }

        $staffId = $this->staff?->id;
        if (! $staffId) {
            return [];
        }

        return Section::query()->where('class_teacher_id', $staffId)->pluck('id')
            ->merge(TimetableEntry::query()->where('staff_id', $staffId)->pluck('section_id'))
            ->unique()->values()->all();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}
