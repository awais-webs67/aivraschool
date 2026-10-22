<?php

namespace App\Support;

use App\Models\User;

/**
 * Who can do what inside a school portal.
 *
 * Each ability lists the roles allowed and the plan module it needs (null = always available).
 * Routes use the middleware "school.can:<ability>", views use $user->allows('<ability>').
 */
class SchoolAccess
{
    private const LEADERS = ['admin', 'principal'];

    private const ACADEMIC = ['admin', 'principal', 'vice_principal'];

    /** Everyone who works at the school (not parents). */
    private const STAFF = ['admin', 'principal', 'vice_principal', 'teacher', 'accountant', 'receptionist', 'staff'];

    public const ABILITIES = [
        'staff.self' => ['roles' => self::STAFF, 'module' => null],
        'notices.read' => ['roles' => self::STAFF, 'module' => null],
        'parent' => ['roles' => ['parent'], 'module' => null],
        'setup' => ['roles' => self::LEADERS, 'module' => null],
        'staff.manage' => ['roles' => self::LEADERS, 'module' => null],
        'approvals' => ['roles' => self::LEADERS, 'module' => null],
        'reports' => ['roles' => [...self::ACADEMIC, 'accountant'], 'module' => 'reports'],

        'students.view' => ['roles' => [...self::ACADEMIC, 'teacher', 'accountant', 'receptionist'], 'module' => 'students'],
        'students.manage' => ['roles' => [...self::LEADERS, 'receptionist'], 'module' => 'students'],
        'admissions' => ['roles' => [...self::LEADERS, 'receptionist'], 'module' => 'admissions'],

        'attendance.take' => ['roles' => [...self::ACADEMIC, 'teacher'], 'module' => 'attendance'],
        'attendance.all' => ['roles' => self::ACADEMIC, 'module' => 'attendance'],
        'staff.attendance' => ['roles' => self::LEADERS, 'module' => 'attendance'],
        'leave.request' => ['roles' => ['vice_principal', 'teacher', 'accountant', 'receptionist', 'staff'], 'module' => null],

        'fees.manage' => ['roles' => [...self::LEADERS, 'accountant'], 'module' => 'fees'],
        'timetable.manage' => ['roles' => self::ACADEMIC, 'module' => 'timetable'],
        'timetable.view' => ['roles' => [...self::ACADEMIC, 'teacher'], 'module' => 'timetable'],
        'homework' => ['roles' => [...self::ACADEMIC, 'teacher'], 'module' => 'homework'],
        'notices' => ['roles' => self::ACADEMIC, 'module' => 'communication'],
        'exams.manage' => ['roles' => self::ACADEMIC, 'module' => 'exams'],
        'marks.enter' => ['roles' => [...self::ACADEMIC, 'teacher'], 'module' => 'exams'],
        'discipline' => ['roles' => [...self::ACADEMIC, 'teacher'], 'module' => null],
        'observations' => ['roles' => self::ACADEMIC, 'module' => null],
        'certificates' => ['roles' => [...self::LEADERS, 'receptionist'], 'module' => 'certificates'],
        'front_office' => ['roles' => [...self::LEADERS, 'receptionist'], 'module' => null],
    ];

    public static function allows(?User $user, string $ability): bool
    {
        $rule = self::ABILITIES[$ability] ?? null;

        if (! $user || ! $rule || ! in_array($user->role, $rule['roles'], true)) {
            return false;
        }

        return $rule['module'] === null || (bool) current_school()?->hasModule($rule['module']);
    }

    /** Ability needs a module the school's plan doesn't include (shown as "upgrade" instead of hidden). */
    public static function lockedByPlan(string $ability): bool
    {
        $module = self::ABILITIES[$ability]['module'] ?? null;

        return $module !== null && ! current_school()?->hasModule($module);
    }
}
