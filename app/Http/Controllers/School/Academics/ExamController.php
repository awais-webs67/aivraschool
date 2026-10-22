<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Exams: create a term, choose the papers of each class, lock and publish.
 */
class ExamController extends Controller
{
    public function index(Request $request): View
    {
        return view('school.exams.index', [
            'exams' => Exam::query()->withCount('papers')->latest('starts_on')->latest('id')->get(),
            'canManage' => $request->user('school')->allows('exams.manage'),
            'grades' => Exam::gradeScale(),
            'passPercent' => (float) current_school()->setting('exams.pass_percent', 33),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);
        $exam = Exam::create($data + ['academic_session_id' => AcademicSession::current()?->id]);
        Activity::log('exam.created', "Created exam {$exam->name}", $exam);

        return redirect()->route('school.exams.show', $exam)->with('success', "{$exam->name} created. Now add the papers for each class.");
    }

    public function show(Request $request, Exam $exam): View
    {
        $user = $request->user('school');
        $ids = $user->teachingSectionIds();
        $sections = Section::query()->with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])->values();
        $papers = ExamSubject::query()->with('subject')->where('exam_id', $exam->id)->get()->groupBy('class_id');
        $entered = ExamMark::query()->whereIn('exam_subject_id', $papers->flatten()->pluck('id'))
            ->join('students', 'students.id', '=', 'exam_marks.student_id')
            ->selectRaw('exam_subject_id, students.section_id, count(*) as n')->groupBy('exam_subject_id', 'students.section_id')->get()
            ->mapWithKeys(fn ($r) => [$r->exam_subject_id.'-'.$r->section_id => (int) $r->n]);

        return view('school.exams.show', [
            'exam' => $exam,
            'sections' => $sections,
            'papers' => $papers,
            'entered' => $entered,
            'strength' => Student::query()->active()->selectRaw('section_id, count(*) as n')->groupBy('section_id')->pluck('n', 'section_id'),
            'classes' => SchoolClass::query()->ordered()->with('subjects')->get(),
            'subjects' => Subject::query()->orderBy('name')->get(),
            'canManage' => $user->allows('exams.manage'),
        ]);
    }

    /** Sets the papers of one class: tick subjects and give max / pass marks. */
    public function savePapers(Request $request, Exam $exam, SchoolClass $class): RedirectResponse
    {
        $schoolId = current_school()->id;
        $data = $request->validate([
            'papers' => ['array'],
            'papers.*.on' => ['nullable', 'boolean'],
            'papers.*.max_marks' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'papers.*.pass_marks' => ['nullable', 'numeric', 'min:0', 'lte:papers.*.max_marks'],
            'papers.*.paper_date' => ['nullable', 'date'],
        ]);
        $subjectIds = Subject::query()->pluck('id')->all();

        DB::transaction(function () use ($data, $exam, $class, $subjectIds) {
            foreach ($data['papers'] ?? [] as $subjectId => $paper) {
                if (! in_array((int) $subjectId, $subjectIds, true)) {
                    continue;
                }
                $key = ['exam_id' => $exam->id, 'class_id' => $class->id, 'subject_id' => (int) $subjectId];
                if (empty($paper['on'])) {
                    // Keep a paper that already has marks; removing it would lose them.
                    ExamSubject::query()->where($key)->whereDoesntHave('marks')->delete();

                    continue;
                }
                $max = (float) ($paper['max_marks'] ?? 100);
                ExamSubject::updateOrCreate($key, [
                    'max_marks' => $max,
                    'pass_marks' => (float) ($paper['pass_marks'] ?? round($max * (float) current_school()->setting('exams.pass_percent', 33) / 100)),
                    'paper_date' => $paper['paper_date'] ?? null,
                ]);
            }
        });

        return back()->with('success', "Papers of {$class->name} saved.");
    }

    public function toggle(Request $request, Exam $exam): RedirectResponse
    {
        $field = $request->validate(['field' => ['required', Rule::in(['is_locked', 'is_published'])]])['field'];
        $exam->update([$field => ! $exam->{$field}]);
        Activity::log('exam.'.$field, ($exam->{$field} ? 'Turned on ' : 'Turned off ').str_replace('is_', '', $field)." for {$exam->name}", $exam);

        return back()->with('success', match (true) {
            $field === 'is_locked' && $exam->is_locked => 'Marks are locked. Teachers can no longer change them.',
            $field === 'is_locked' => 'Marks are open for changes again.',
            $exam->is_published => 'Results are published.',
            default => 'Results are hidden again.',
        });
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        abort_if($exam->is_locked, 422, 'Unlock the exam before deleting it.');
        $exam->delete();

        return redirect()->route('school.exams.index')->with('success', "{$exam->name} deleted.");
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pass_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.grade' => ['nullable', 'string', 'max:5'],
        ]);
        $grades = collect($data['grades'])->filter(fn ($g) => filled($g['grade'] ?? null) && ($g['min'] ?? null) !== null)
            ->map(fn ($g) => [(float) $g['min'], trim($g['grade'])])->sortByDesc(0)->values()->all();

        current_school()->putSettings(['exams.pass_percent' => (float) $data['pass_percent'], 'exams.grades' => $grades]);

        return back()->with('success', 'Grading saved.');
    }
}
