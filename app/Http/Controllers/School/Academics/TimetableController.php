<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bell schedule (periods), weekly timetable per section and per teacher.
 */
class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('school');
        $canManage = $user->allows('timetable.manage');
        $periods = Period::query()->ordered()->get();

        // Teachers open their own timetable by default; managers pick a section or a teacher.
        $teacher = $request->filled('teacher') ? Staff::find($request->integer('teacher')) : null;
        $section = $request->filled('section') ? Section::with('schoolClass')->find($request->integer('section')) : null;
        if (! $teacher && ! $section) {
            $teacher = $canManage ? null : $user->staff;
            $section = $teacher ? null : Section::query()->with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])->first();
        }

        $entries = TimetableEntry::query()->with('subject', 'staff', 'section.schoolClass')
            ->when($section, fn ($q) => $q->where('section_id', $section->id))
            ->when($teacher, fn ($q) => $q->where('staff_id', $teacher->id))
            ->when(! $section && ! $teacher, fn ($q) => $q->whereRaw('1 = 0'))
            ->get()->keyBy(fn ($e) => $e->day.'-'.$e->period_id);

        return view('school.timetable.index', [
            'periods' => $periods,
            'days' => TimetableEntry::workingDays(),
            'entries' => $entries,
            'section' => $section,
            'teacher' => $teacher,
            'canManage' => $canManage,
            'groups' => Section::grouped(),
            'teachers' => Staff::query()->active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function edit(Section $section): View
    {
        return view('school.timetable.edit', [
            'section' => $section->load('schoolClass'),
            'periods' => Period::query()->ordered()->where('is_break', false)->get(),
            'days' => TimetableEntry::workingDays(),
            'entries' => TimetableEntry::query()->where('section_id', $section->id)->get()->keyBy(fn ($e) => $e->day.'-'.$e->period_id),
            'subjects' => ($section->schoolClass?->subjects()->pluck('name', 'subjects.id') ?? collect())->whenEmpty(fn () => Subject::query()->orderBy('name')->pluck('name', 'id')),
            'teachers' => Staff::query()->active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** Saves the whole week of one section. A teacher can't be in two classes in the same period. */
    public function update(Request $request, Section $section): RedirectResponse
    {
        $schoolId = current_school()->id;
        $data = $request->validate([
            'cell' => ['array'],
            'cell.*.*.subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'cell.*.*.staff_id' => ['nullable', Rule::exists('staff', 'id')->where('school_id', $schoolId)],
            'cell.*.*.room' => ['nullable', 'string', 'max:30'],
        ]);
        $days = array_keys(TimetableEntry::workingDays());
        $periodIds = Period::query()->pluck('id')->all();

        $cells = [];
        foreach ($data['cell'] ?? [] as $day => $row) {
            foreach ($row as $periodId => $cell) {
                if (in_array((int) $day, $days, true) && in_array((int) $periodId, $periodIds, true)) {
                    $cells[(int) $day][(int) $periodId] = $cell;
                }
            }
        }

        // Clash check against other sections.
        $busy = TimetableEntry::query()->with('section.schoolClass', 'staff', 'period')->where('section_id', '!=', $section->id)->whereNotNull('staff_id')->get()
            ->keyBy(fn ($e) => $e->staff_id.'-'.$e->day.'-'.$e->period_id);
        $clashes = [];
        foreach ($cells as $day => $row) {
            foreach ($row as $periodId => $cell) {
                if (filled($cell['staff_id'] ?? null) && ($other = $busy[$cell['staff_id'].'-'.$day.'-'.$periodId] ?? null)) {
                    $clashes[] = "{$other->staff->name} already teaches {$other->section->label()} on ".TimetableEntry::DAYS[$day]." ({$other->period->name}).";
                }
            }
        }
        if ($clashes) {
            throw ValidationException::withMessages(['cell' => array_slice($clashes, 0, 8)]);
        }

        DB::transaction(function () use ($section, $cells) {
            foreach ($cells as $day => $row) {
                foreach ($row as $periodId => $cell) {
                    $key = ['section_id' => $section->id, 'day' => $day, 'period_id' => $periodId];
                    if (blank($cell['subject_id'] ?? null) && blank($cell['staff_id'] ?? null)) {
                        TimetableEntry::query()->where($key)->delete();

                        continue;
                    }
                    TimetableEntry::updateOrCreate($key, [
                        'subject_id' => $cell['subject_id'] ?: null,
                        'staff_id' => $cell['staff_id'] ?: null,
                        'room' => $cell['room'] ?? null,
                    ]);
                }
            }
        });

        Activity::log('timetable.updated', "Updated the timetable of {$section->label()}");

        return redirect()->route('school.timetable.index', ['section' => $section->id])->with('success', 'Timetable saved.');
    }

    public function periods(): View
    {
        return view('school.timetable.periods', [
            'periods' => Period::query()->ordered()->get(),
            'days' => (array) current_school()->setting('timetable.days', [1, 2, 3, 4, 5, 6]),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'is_break' => ['nullable', 'boolean'],
        ]);
        Period::create($data + ['is_break' => $request->boolean('is_break')]);

        return back()->with('success', "{$data['name']} added.");
    }

    public function destroyPeriod(Period $period): RedirectResponse
    {
        $period->delete();

        return back()->with('success', "{$period->name} removed.");
    }

    /** One click: 8 lessons of 40 minutes from the given start time with a 20-minute break after the 4th. */
    public function standardPeriods(Request $request): RedirectResponse
    {
        $data = $request->validate(['start' => ['required', 'date_format:H:i'], 'minutes' => ['required', 'integer', 'min:20', 'max:90']]);
        abort_if(Period::query()->exists(), 422, 'Remove the current periods first.');

        $time = Carbon::createFromFormat('H:i', $data['start']);
        $order = 0;
        foreach (range(1, 8) as $n) {
            Period::create(['name' => "Period {$n}", 'starts_at' => $time->format('H:i'), 'ends_at' => $time->addMinutes((int) $data['minutes'])->format('H:i'), 'sort_order' => $order++]);
            if ($n === 4) {
                Period::create(['name' => 'Break', 'starts_at' => $time->format('H:i'), 'ends_at' => $time->addMinutes(20)->format('H:i'), 'is_break' => true, 'sort_order' => $order++]);
            }
        }

        return back()->with('success', 'Periods added. Change any of them as you like.');
    }

    public function saveDays(Request $request): RedirectResponse
    {
        $data = $request->validate(['days' => ['required', 'array', 'min:1'], 'days.*' => ['integer', 'between:1,7']]);
        current_school()->putSettings(['timetable.days' => array_values(array_map('intval', $data['days']))]);

        return back()->with('success', 'School days saved.');
    }
}
