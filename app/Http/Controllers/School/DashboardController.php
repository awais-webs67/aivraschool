<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\Complaint;
use App\Models\Enquiry;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Homework;
use App\Models\LeaveRequest;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\Substitution;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The school's home screen. The principal gets the whole school at a glance; teachers, the
 * accountant and the receptionist get their own day.
 */
class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        /** @var School $school */
        $school = current_school();
        /** @var User $user */
        $user = auth('school')->user();

        if ($user->isParent()) {
            return redirect()->route('school.parent.home');
        }

        return view('school.dashboard', [
            'school' => $school,
            'user' => $user,
            'session' => AcademicSession::current(),
            'announcements' => Announcement::query()->visibleTo($school)->latest()->limit(5)->get(),
            'notices' => Notice::query()->live()->forStaff()->orderByDesc('is_pinned')->latest('publish_on')->limit(4)->get(),
            'setup' => $user->allows('setup') ? $this->setupSteps($school) : [],
            'modules' => collect(config('skooline.modules'))->map(fn (array $m, string $key) => $m + ['key' => $key, 'enabled' => $school->hasModule($key)]),
            'overview' => $user->allows('approvals') || $user->role === 'vice_principal' ? $this->overview($school, $user) : null,
            'teaching' => $user->staff ? $this->teacherDay($user->staff) : null,
            'fees' => $user->allows('fees.manage') ? $this->fees() : null,
            'office' => $user->allows('front_office') ? $this->office() : null,
            'birthdays' => $this->birthdays(),
        ]);
    }

    /** Principal's morning view: numbers, alerts and trends. */
    protected function overview(School $school, User $user): array
    {
        $today = today()->toDateString();
        $students = Student::query()->active()->count();
        $marked = StudentAttendance::query()->whereDate('date', $today)->count();
        $attended = StudentAttendance::query()->whereDate('date', $today)->whereIn('status', StudentAttendance::ATTENDED)->count();
        $staff = Staff::query()->active()->count();
        $staffAway = StaffAttendance::query()->whereDate('date', $today)->whereIn('status', ['absent', 'leave'])->count();

        $sections = Section::query()->with('schoolClass', 'classTeacher')->get();
        $markedSections = StudentAttendance::query()->whereDate('date', $today)->distinct()->pluck('section_id');
        $withStudents = Student::query()->active()->distinct()->pluck('section_id');
        $notMarked = $sections->whereIn('id', $withStudents)->whereNotIn('id', $markedSections);

        // Lessons of absent teachers today with no substitute.
        $absentIds = StaffAttendance::query()->whereDate('date', $today)->whereIn('status', ['absent', 'leave'])->pluck('staff_id');
        $uncovered = $absentIds->isEmpty() ? 0 : TimetableEntry::query()->where('day', today()->dayOfWeekIso)->whereIn('staff_id', $absentIds)
            ->whereDoesntHave('substitutions', fn ($q) => $q->whereDate('date', $today))->count();

        // Attendance % for the last 14 school days with records.
        $trend = StudentAttendance::query()->where('date', '>=', today()->subDays(20)->toDateString())
            ->selectRaw('date, count(*) as total, sum(case when status in (\'present\', \'late\', \'half_day\') then 1 else 0 end) as attended')
            ->groupBy('date')->orderBy('date')->get()->take(-14)
            ->map(fn ($r) => ['date' => Carbon::parse($r->date), 'percent' => $r->total ? round($r->attended / $r->total * 100) : 0]);

        $strength = Student::query()->active()->join('classes', 'classes.id', '=', 'students.class_id')
            ->selectRaw('classes.name as name, classes.sort_order as sort_order, count(*) as n')->groupBy('classes.id', 'classes.name', 'classes.sort_order')
            ->orderBy('sort_order')->get();

        $alerts = array_filter([
            $notMarked->isNotEmpty() ? ['alert', 'warning', $notMarked->count().' '.str('class')->plural($notMarked->count()).' without attendance today', route('school.attendance.index')] : null,
            $uncovered > 0 && $user->allows('timetable.manage') ? ['users', 'danger', $uncovered.' '.str('lesson')->plural($uncovered).' of absent teachers have no cover', route('school.timetable.substitutions')] : null,
            ($pending = LeaveRequest::query()->where('status', 'pending')->count()) ? ['calendar', 'brand', $pending.' leave '.str('request')->plural($pending).' waiting for you', route('school.leave.index')] : null,
            ($complaints = Complaint::query()->where('status', 'open')->count()) && $user->allows('front_office') ? ['chat', 'warning', $complaints.' open '.str('complaint')->plural($complaints), route('school.visitors.complaints')] : null,
            ($followUps = Enquiry::query()->whereNotIn('status', ['admitted', 'lost'])->whereDate('follow_up_on', '<=', $today)->count()) && $user->allows('admissions') ? ['clipboard', 'info', $followUps.' admission '.str('enquiry')->plural($followUps).' to follow up', route('school.enquiries.index')] : null,
            ($noDiary = $sections->whereIn('id', $withStudents)->whereNotIn('id', Homework::query()->whereDate('date', $today)->pluck('section_id'))->count()) && now()->hour >= 12 && $school->hasModule('homework') ? ['book-open', 'neutral', $noDiary.' '.str('class')->plural($noDiary).' with no diary today', route('school.homework.index')] : null,
        ]);

        return [
            'students' => $students,
            'attendance' => $marked ? round($attended / $marked * 100) : null,
            'absentToday' => $marked - $attended,
            'staff' => $staff,
            'staffAway' => $staffAway,
            'newAdmissions' => Student::query()->where('admission_date', '>=', today()->startOfMonth()->toDateString())->count(),
            'alerts' => $alerts,
            'trend' => $trend,
            'strength' => $strength,
            'notMarked' => $notMarked->take(6),
        ];
    }

    /** A teacher's lessons and classes today. */
    protected function teacherDay(Staff $staff): array
    {
        $day = today()->dayOfWeekIso;
        $lessons = TimetableEntry::query()->with('period', 'section.schoolClass', 'subject')->where('day', $day)->where('staff_id', $staff->id)->get();
        $covers = Substitution::query()->with('entry.period', 'entry.section.schoolClass', 'entry.subject')->whereDate('date', today())->where('substitute_staff_id', $staff->id)->get();
        $classOf = Section::query()->with('schoolClass')->where('class_teacher_id', $staff->id)->get();

        return [
            'lessons' => $lessons->map(fn ($e) => ['entry' => $e, 'cover' => false])
                ->merge($covers->map(fn ($s) => ['entry' => $s->entry, 'cover' => true]))
                ->sortBy(fn ($l) => (string) $l['entry']?->period?->starts_at)->values(),
            'classOf' => $classOf->map(fn ($s) => [$s, StudentAttendance::query()->where('section_id', $s->id)->whereDate('date', today())->exists()]),
            'myLeave' => LeaveRequest::query()->where('staff_id', $staff->id)->latest('id')->first(),
        ];
    }

    protected function fees(): array
    {
        $month = today()->startOfMonth()->toDateString();
        $billed = (float) FeeInvoice::query()->where('status', '!=', 'cancelled')->whereDate('month', $month)->sum('total');
        $collected = (float) FeePayment::query()->where('paid_on', '>=', $month)->sum('amount');

        return [
            'today' => (float) FeePayment::query()->whereDate('paid_on', today())->sum('amount'),
            'month' => $collected,
            'billed' => $billed,
            'outstanding' => (float) FeeInvoice::query()->open()->sum(DB::raw('total - paid')),
        ];
    }

    protected function office(): array
    {
        return [
            'visitors' => Visitor::query()->whereDate('in_at', today())->count(),
            'enquiries' => Enquiry::query()->whereDate('created_at', today())->count(),
            'followUps' => Enquiry::query()->whereNotIn('status', ['admitted', 'lost'])->whereDate('follow_up_on', '<=', today())->count(),
        ];
    }

    /** Students and staff born on today's day and month. */
    protected function birthdays(): Collection
    {
        $md = today()->format('m-d');
        $match = fn ($q) => DB::connection()->getDriverName() === 'sqlite'
            ? $q->whereRaw("strftime('%m-%d', date_of_birth) = ?", [$md])
            : $q->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$md]);

        return $match(Student::query()->active()->with('schoolClass', 'section'))->limit(10)->get()->map(fn ($s) => [$s->name, $s->classLabel()])
            ->merge($match(Staff::query()->active())->limit(5)->get()->map(fn ($s) => [$s->name, $s->designation ?? 'Staff']));
    }

    protected function setupSteps(School $school): array
    {
        return [
            ['done' => filled($school->phone) && filled($school->address), 'label' => 'Complete the school profile', 'hint' => 'Phone, address and logo', 'route' => 'school.setup.profile'],
            ['done' => AcademicSession::current() !== null, 'label' => 'Set the current academic session', 'hint' => 'e.g. 2026-27', 'route' => 'school.setup.sessions'],
            ['done' => SchoolClass::query()->exists(), 'label' => 'Add classes and sections', 'hint' => 'Playgroup to Class 10 in one click', 'route' => 'school.setup.classes'],
            ['done' => Subject::query()->exists(), 'label' => 'Add subjects', 'hint' => 'and choose which classes study them', 'route' => 'school.setup.subjects'],
            ['done' => Staff::query()->exists(), 'label' => 'Add your staff', 'hint' => 'and give teachers a login', 'route' => 'school.staff.index'],
            ['done' => ! $school->hasModule('students') || Student::query()->exists(), 'label' => 'Add students', 'hint' => 'one by one or import from Excel', 'route' => $school->hasModule('students') ? 'school.students.index' : 'school.dashboard'],
        ];
    }
}
