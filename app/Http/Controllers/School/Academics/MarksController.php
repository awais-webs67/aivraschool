<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\Section;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Marks entry for one paper and one section. */
class MarksController extends Controller
{
    public function edit(Request $request, Exam $exam, ExamSubject $paper, Section $section): View
    {
        $this->authorize($request, $exam, $paper, $section);

        return view('school.exams.marks', [
            'exam' => $exam,
            'paper' => $paper->load('subject'),
            'section' => $section->load('schoolClass'),
            'students' => $section->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get(),
            'marks' => ExamMark::query()->where('exam_subject_id', $paper->id)->get()->keyBy('student_id'),
            'locked' => $exam->is_locked,
        ]);
    }

    public function update(Request $request, Exam $exam, ExamSubject $paper, Section $section): RedirectResponse
    {
        $this->authorize($request, $exam, $paper, $section);
        abort_if($exam->is_locked, 403, 'Marks of this exam are locked.');

        $data = $request->validate([
            'marks' => ['array'],
            'marks.*' => ['nullable', 'numeric', 'min:0'],
            'absent' => ['array'],
            'remarks' => ['array'],
            'remarks.*' => ['nullable', 'string', 'max:150'],
        ]);

        $tooHigh = collect($data['marks'] ?? [])->filter(fn ($m) => $m !== null && (float) $m > (float) $paper->max_marks)->keys();
        if ($tooHigh->isNotEmpty()) {
            throw ValidationException::withMessages(['marks' => 'Marks can\'t be more than '.(float) $paper->max_marks.'. Check the highlighted rows.'])->redirectTo(url()->previous());
        }

        $ids = $section->students()->active()->pluck('id')->all();
        $userId = $request->user('school')->id;
        $saved = 0;

        DB::transaction(function () use ($data, $ids, $paper, $userId, &$saved) {
            foreach ($ids as $studentId) {
                $absent = isset($data['absent'][$studentId]);
                $value = $data['marks'][$studentId] ?? null;
                if ($value === null && ! $absent && blank($data['remarks'][$studentId] ?? null)) {
                    ExamMark::query()->where('exam_subject_id', $paper->id)->where('student_id', $studentId)->delete();

                    continue;
                }
                ExamMark::updateOrCreate(['exam_subject_id' => $paper->id, 'student_id' => $studentId], [
                    'marks' => $absent ? null : $value,
                    'is_absent' => $absent,
                    'remarks' => $data['remarks'][$studentId] ?? null,
                    'entered_by' => $userId,
                ]);
                $saved++;
            }
        });

        Activity::log('exam.marks', "Entered {$paper->subject?->name} marks of {$section->label()} for {$exam->name}");

        return redirect()->route('school.exams.show', $exam)->with('success', "{$saved} marks saved for {$section->label()} · {$paper->subject?->name}.");
    }

    /** Teachers only for their own sections; the paper must belong to the section's class. */
    protected function authorize(Request $request, Exam $exam, ExamSubject $paper, Section $section): void
    {
        abort_unless($paper->exam_id === $exam->id && $paper->class_id === $section->class_id, 404);
        $ids = $request->user('school')->teachingSectionIds();
        abort_if($ids !== null && ! in_array($section->id, $ids, true), 403, 'This is not one of your classes.');
    }
}
