<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var School $school */
        $school = current_school();

        $modules = collect(config('skooline.modules'))
            ->map(fn (array $module, string $key) => $module + ['key' => $key, 'enabled' => $school->hasModule($key)]);

        $counts = [
            'classes' => SchoolClass::query()->count(),
            'sections' => Section::query()->count(),
            'subjects' => Subject::query()->count(),
            'users' => User::query()->count(),
        ];

        // Step-by-step setup checklist shown until everything is done.
        $setup = [
            ['done' => filled($school->phone) && filled($school->address), 'label' => 'Complete the school profile', 'hint' => 'Phone, address and logo', 'route' => 'school.setup.profile'],
            ['done' => AcademicSession::current() !== null, 'label' => 'Set the current academic session', 'hint' => 'e.g. 2026-27', 'route' => 'school.setup.sessions'],
            ['done' => $counts['classes'] > 0, 'label' => 'Add classes and sections', 'hint' => 'Playgroup to Class 10 in one click', 'route' => 'school.setup.classes'],
            ['done' => $counts['subjects'] > 0, 'label' => 'Add subjects', 'hint' => 'and choose which classes study them', 'route' => 'school.setup.subjects'],
        ];

        return view('school.dashboard', [
            'school' => $school,
            'modules' => $modules,
            'counts' => $counts,
            'session' => AcademicSession::current(),
            'setup' => $setup,
            'announcements' => Announcement::query()->visibleTo($school)->latest()->limit(5)->get(),
            'canSetup' => in_array(auth('school')->user()->role, ['admin', 'principal'], true),
        ]);
    }
}
