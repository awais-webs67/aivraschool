<?php

namespace App\Support;

use App\Http\Controllers\School\ApprovalController;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The school portal sidebar. An item shows when its page exists, the user's role may open it
 * and the school's plan includes it. Plan modules without a page yet are listed as "coming next".
 */
class SchoolMenu
{
    /** section => [route, active pattern, icon, label, ability (null = everyone), module] */
    public const ITEMS = [
        'Overview' => [
            ['school.dashboard', 'school.dashboard', 'home', 'Dashboard', null, null],
            ['school.approvals', 'school.approvals*', 'check-circle', 'Approvals', 'approvals', null],
            ['school.ai.index', 'school.ai.*', 'sparkles', 'AI assistant', 'setup', 'ai'],
        ],
        'People' => [
            ['school.students.index', 'school.students.*', 'users', 'Students', 'students.view', 'students'],
            ['school.enquiries.index', 'school.enquiries.*', 'clipboard', 'Admissions', 'admissions', 'admissions'],
            ['school.staff.index', 'school.staff.*', 'briefcase', 'Staff', 'staff.manage', null],
        ],
        'Daily' => [
            ['school.attendance.index', 'school.attendance.*', 'check-circle', 'Attendance', 'attendance.take', 'attendance'],
            ['school.leave.index', 'school.leave.*', 'calendar', 'Leave', null, null],
            ['school.timetable.index', 'school.timetable.*', 'calendar', 'Timetable', 'timetable.view', 'timetable'],
            ['school.homework.index', 'school.homework.*', 'book-open', 'Homework & diary', 'homework', 'homework'],
            ['school.notices.index', 'school.notices.*', 'chat', 'Notices', null, 'communication'],
        ],
        'Results & money' => [
            ['school.exams.index', 'school.exams.*', 'academic-cap', 'Exams & results', 'marks.enter', 'exams'],
            ['school.fees.index', 'school.fees.*', 'banknotes', 'Fees', 'fees.manage', 'fees'],
            ['school.reports.index', 'school.reports.*', 'chart', 'Reports', 'reports', 'reports'],
        ],
        'Office' => [
            ['school.discipline.index', 'school.discipline.*', 'alert', 'Discipline', 'discipline', null],
            ['school.observations.index', 'school.observations.*', 'eye', 'Class observations', 'observations', null],
            ['school.certificates.index', 'school.certificates.*', 'identification', 'Certificates & ID cards', 'certificates', 'certificates'],
            ['school.visitors.index', 'school.visitors.*', 'clipboard', 'Front office', 'front_office', null],
        ],
        'School setup' => [
            ['school.setup.profile', 'school.setup.profile*', 'building', 'School profile', 'setup', null],
            ['school.setup.sessions', 'school.setup.sessions*', 'calendar', 'Academic sessions', 'setup', null],
            ['school.setup.classes', 'school.setup.classes*', 'layers', 'Classes & sections', 'setup', null],
            ['school.setup.subjects', 'school.setup.subjects*', 'book-open', 'Subjects', 'setup', null],
            ['school.billing', 'school.billing', 'credit-card', 'Billing', 'setup', null],
            ['school.support.index', 'school.support.*', 'chat', 'Help & support', 'setup', null],
        ],
    ];

    /** @return array<string, list<array{route: string, active: string, icon: string, label: string}>> */
    public static function for(User $user, School $school): array
    {
        $menu = [];

        foreach (self::ITEMS as $section => $items) {
            foreach ($items as [$route, $active, $icon, $label, $ability, $module]) {
                $visible = Route::has($route)
                    && ($ability === null || $user->allows($ability))
                    && ($module === null || $school->hasModule($module))
                    && ($route !== 'school.ai.index' || $school->aiMonthlyLimit() > 0);

                if ($visible) {
                    $count = $route === 'school.approvals' ? ApprovalController::waiting() : 0;
                    $menu[$section][] = compact('route', 'active', 'icon', 'label', 'count');
                }
            }
        }

        return $menu;
    }

    /** Modules in the plan that don't have a page yet. */
    public static function comingNext(School $school): array
    {
        $built = collect(self::ITEMS)->flatten(1)->filter(fn ($i) => $i[5] && Route::has($i[0]))->pluck(5)->all();

        return collect(config('skooline.modules'))
            ->filter(fn ($m, $key) => $school->hasModule($key) && ! in_array($key, $built, true))
            ->all();
    }
}
