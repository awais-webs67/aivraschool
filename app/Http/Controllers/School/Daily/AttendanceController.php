<?php

namespace App\Http\Controllers\School\Daily;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Daily student attendance by section, and the monthly register.
 * Teachers see only their own sections and may change the last 7 days.
 */
class AttendanceController extends Controller
{
    /** How many days back a teacher may still change attendance. Leaders can change any day. */
    public const TEACHER_EDIT_DAYS = 7;

    public function index(Request $request): View
    {
        $date = $this->date($request->query('date'));
        $sections = $this->sections();
        $marked = StudentAttendance::query()->whereDate('date', $date)->whereIn('section_id', $sections->pluck('id'))
            ->selectRaw('section_id, count(*) as total, sum(case when status in (\'present\', \'late\', \'half_day\') then 1 else 0 end) as attended')
            ->groupBy('section_id')->get()->keyBy('section_id');
        $strength = Student::query()->active()->whereIn('section_id', $sections->pluck('id'))
            ->selectRaw('section_id, count(*) as n')->groupBy('section_id')->pluck('n', 'section_id');

        return view('school.attendance.index', [
            'date' => $date,
            'sections' => $sections,
            'marked' => $marked,
            'strength' => $strength,
            'totals' => [
                'students' => (int) $strength->sum(),
                'marked' => (int) $marked->sum('total'),
                'attended' => (int) $marked->sum('attended'),
                'absent' => StudentAttendance::query()->whereDate('date', $date)->whereIn('section_id', $sections->pluck('id'))->where('status', 'absent')->count(),
            ],
        ]);
    }

    public function mark(Request $request, Section $section): View
    {
        $this->authorizeSection($section);
        $date = $this->date($request->query('date'));

        return view('school.attendance.mark', [
            'section' => $section->load('schoolClass'),
            'date' => $date,
            'students' => $section->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get(),
            'records' => StudentAttendance::query()->where('section_id', $section->id)->whereDate('date', $date)->get()->keyBy('student_id'),
            'canEdit' => $this->canEditDate($date),
        ]);
    }

    public function save(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeSection($section);
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array'],
            'status.*' => ['required', Rule::in(array_keys(StudentAttendance::STATUSES))],
            'remark' => ['array'],
            'remark.*' => ['nullable', 'string', 'max:150'],
        ]);
        $date = Carbon::parse($data['date'])->startOfDay();
        abort_unless($this->canEditDate($date), 403, 'This day can no longer be changed. Ask the principal.');

        $ids = $section->students()->active()->pluck('id')->all();
        $userId = $request->user('school')->id;

        DB::transaction(function () use ($data, $ids, $section, $date, $userId) {
            foreach ($data['status'] as $studentId => $status) {
                if (! in_array((int) $studentId, $ids, true)) {
                    continue; // not (or no longer) in this section
                }
                StudentAttendance::updateOrCreate(
                    ['student_id' => (int) $studentId, 'date' => $date->toDateString()],
                    ['section_id' => $section->id, 'status' => $status, 'remark' => $data['remark'][$studentId] ?? null, 'marked_by' => $userId],
                );
            }
        });

        $absent = collect($data['status'])->filter(fn ($s) => $s === 'absent')->count();
        Activity::log('attendance.marked', "Attendance for {$section->label()} on {$date->format('d M Y')}: {$absent} absent");

        return redirect()->route('school.attendance.index', ['date' => $date->toDateString()])
            ->with('success', "Attendance saved for {$section->label()}. {$absent} absent.");
    }

    /** Monthly register: one row per student, one column per day, with totals and percentage. */
    public function register(Request $request, Section $section): View
    {
        $this->authorizeSection($section);
        $month = $this->month($request->query('month'));
        $days = collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->copy()->day($d));

        $students = $section->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get();
        $records = StudentAttendance::query()->where('section_id', $section->id)
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()->groupBy('student_id')->map(fn ($rows) => $rows->keyBy(fn ($r) => $r->date->day));

        return view('school.attendance.register', [
            'section' => $section->load('schoolClass'),
            'month' => $month,
            'days' => $days,
            'students' => $students,
            'records' => $records,
            'summary' => $students->mapWithKeys(fn ($s) => [$s->id => self::summarise($records[$s->id] ?? collect())]),
        ]);
    }

    /** @return array{present: int, absent: int, leave: int, total: int, percent: ?int} */
    public static function summarise(Collection $rows): array
    {
        $total = $rows->count();
        $attended = $rows->whereIn('status', StudentAttendance::ATTENDED)->count();

        return [
            'present' => $attended,
            'absent' => $rows->where('status', 'absent')->count(),
            'leave' => $rows->where('status', 'leave')->count(),
            'total' => $total,
            'percent' => $total ? (int) round($attended / $total * 100) : null,
        ];
    }

    /** Sections this user may see, in class order. */
    protected function sections(): Collection
    {
        $ids = auth('school')->user()->teachingSectionIds();

        return Section::query()->with('schoolClass', 'classTeacher')
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->schoolClass?->name, $s->name])->values();
    }

    protected function authorizeSection(Section $section): void
    {
        $ids = auth('school')->user()->teachingSectionIds();
        abort_if($ids !== null && ! in_array($section->id, $ids, true), 403, 'This is not one of your classes.');
    }

    protected function canEditDate(Carbon $date): bool
    {
        if ($date->isFuture()) {
            return false;
        }

        return auth('school')->user()->allows('attendance.all') || $date->gte(today()->subDays(self::TEACHER_EDIT_DAYS));
    }

    protected function date(?string $value): Carbon
    {
        $date = rescue(fn () => Carbon::parse((string) $value)->startOfDay(), today(), false);

        return $value && $date->lte(today()) ? $date : today();
    }

    protected function month(?string $value): Carbon
    {
        return rescue(fn () => Carbon::createFromFormat('!Y-m', (string) $value), today()->startOfMonth(), false) ?: today()->startOfMonth();
    }
}
