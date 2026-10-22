<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\School\Daily\AttendanceController;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\School\ResultService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Result sheet of a section, report cards and the principal's result analysis. */
class ResultController extends Controller
{
    public function section(Request $request, Exam $exam, Section $section, ResultService $results): View
    {
        $this->authorizeSection($request, $section);

        return view('school.exams.results', ['exam' => $exam, 'section' => $section->load('schoolClass')] + $results->forSection($exam, $section));
    }

    /** Report cards: one student, or every student of the section (for printing). */
    public function reportCards(Request $request, Exam $exam, Section $section, ResultService $results): View
    {
        $this->authorizeSection($request, $section);
        $result = $results->forSection($exam, $section);
        $rows = $request->filled('student') ? $result['rows']->where('student.id', $request->integer('student')) : $result['rows'];

        $from = $exam->starts_on?->copy()->subMonths(3) ?? today()->subMonths(3);
        $to = $exam->ends_on ?? today();
        $attendance = StudentAttendance::query()->whereIn('student_id', $rows->pluck('student.id'))->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()->groupBy('student_id')->map(fn ($r) => AttendanceController::summarise($r));

        return view('school.exams.report-cards', [
            'exam' => $exam,
            'section' => $section->load('schoolClass', 'classTeacher'),
            'papers' => $result['papers'],
            'rows' => $rows,
            'classSize' => $result['rows']->count(),
            'attendance' => $attendance,
            'school' => current_school(),
        ]);
    }

    /** Principal's view: average and pass rate per section and per subject, top students. */
    public function analysis(Exam $exam, ResultService $results): View
    {
        $sections = Section::query()->with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name]);
        $bySection = collect();
        $bySubject = [];
        $all = collect();

        foreach ($sections as $section) {
            $result = $results->forSection($exam, $section);
            $done = $result['rows']->filter(fn ($r) => $r['percent'] !== null);
            if ($done->isEmpty()) {
                continue;
            }
            $all = $all->merge($done->map(fn ($r) => $r + ['section' => $section]));
            $bySection->push([
                'section' => $section,
                'students' => $done->count(),
                'average' => round($done->avg('percent'), 1),
                'passRate' => round($done->where('passed', true)->count() / $done->count() * 100),
            ]);
            foreach ($result['papers'] as $paper) {
                $name = $paper->subject?->name ?? '—';
                foreach ($done as $r) {
                    if (($p = $r['subjects'][$paper->id]['percent'] ?? null) !== null) {
                        $bySubject[$name][] = $p;
                        $bySubject[$name.'#pass'][] = $r['subjects'][$paper->id]['passed'] ? 1 : 0;
                    }
                }
            }
        }

        $subjects = collect($bySubject)->filter(fn ($v, $k) => ! str_ends_with($k, '#pass'))
            ->map(fn ($values, $name) => ['name' => $name, 'average' => round(array_sum($values) / count($values), 1), 'passRate' => round(array_sum($bySubject[$name.'#pass']) / count($values) * 100)])
            ->sortBy('average')->values();

        return view('school.exams.analysis', [
            'exam' => $exam,
            'bySection' => $bySection,
            'subjects' => $subjects,
            'top' => $all->sortByDesc('percent')->take(10)->values(),
            'overall' => $all->isEmpty() ? null : ['average' => round($all->avg('percent'), 1), 'passRate' => round($all->where('passed', true)->count() / $all->count() * 100), 'students' => $all->count()],
        ]);
    }

    protected function authorizeSection(Request $request, Section $section): void
    {
        $ids = $request->user('school')->teachingSectionIds();
        abort_if($ids !== null && ! in_array($section->id, $ids, true), 403, 'This is not one of your classes.');
    }
}
