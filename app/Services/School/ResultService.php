<?php

namespace App\Services\School;

use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Works out results of one exam for a section: totals, percentage, grade, pass / fail and position.
 */
class ResultService
{
    /**
     * @return array{papers: Collection<int, ExamSubject>, rows: Collection<int, array<string, mixed>>}
     */
    public function forSection(Exam $exam, Section $section): array
    {
        $papers = ExamSubject::query()->with('subject')->where('exam_id', $exam->id)->where('class_id', $section->class_id)
            ->get()->sortBy(fn ($p) => $p->subject?->name)->values();
        $students = $section->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get();
        $marks = ExamMark::query()->whereIn('exam_subject_id', $papers->pluck('id'))->whereIn('student_id', $students->pluck('id'))
            ->get()->groupBy('student_id')->map(fn ($rows) => $rows->keyBy('exam_subject_id'));

        $rows = $students->map(fn (Student $s) => $this->row($s, $papers, $marks[$s->id] ?? collect()));

        return ['papers' => $papers, 'rows' => $this->rank($rows)];
    }

    /** Result of a single student (for the report card). */
    public function forStudent(Exam $exam, Student $student): array
    {
        $result = $this->forSection($exam, $student->section);
        $row = $result['rows']->firstWhere('student.id', $student->id);

        return ['papers' => $result['papers'], 'row' => $row, 'classSize' => $result['rows']->count()];
    }

    /** @param  Collection<int, ExamMark>  $marks  keyed by exam_subject_id */
    protected function row(Student $student, Collection $papers, Collection $marks): array
    {
        $obtained = $max = 0.0;
        $failed = [];
        $entered = 0;
        $subjects = [];

        foreach ($papers as $paper) {
            $mark = $marks[$paper->id] ?? null;
            $value = $mark && ! $mark->is_absent && $mark->marks !== null ? (float) $mark->marks : null;
            if ($mark) {
                $entered++;
            }
            $max += (float) $paper->max_marks;
            $obtained += $value ?? 0;
            $passed = $value !== null && $value >= (float) $paper->pass_marks;
            if ($mark && ! $passed) {
                $failed[] = $paper->subject?->name;
            }
            $subjects[$paper->id] = [
                'marks' => $value,
                'absent' => (bool) $mark?->is_absent,
                'remarks' => $mark?->remarks,
                'percent' => $value !== null && (float) $paper->max_marks > 0 ? round($value / (float) $paper->max_marks * 100, 1) : null,
                'passed' => $passed,
            ];
        }

        $percent = $max > 0 && $entered > 0 ? round($obtained / $max * 100, 2) : null;

        return [
            'student' => $student,
            'subjects' => $subjects,
            'obtained' => $obtained,
            'max' => $max,
            'percent' => $percent,
            'grade' => Exam::gradeFor($percent),
            'passed' => $entered > 0 && $failed === [],
            'failed' => array_values(array_filter($failed)),
            'complete' => $entered === $papers->count() && $papers->isNotEmpty(),
            'position' => null,
        ];
    }

    /** Positions by total marks; equal totals share a position (1, 2, 2, 4). */
    protected function rank(Collection $rows): Collection
    {
        $ranked = $rows->filter(fn ($r) => $r['percent'] !== null)->sortByDesc('obtained')->values();
        $positions = [];
        foreach ($ranked as $i => $r) {
            $positions[$r['student']->id] = $i > 0 && $r['obtained'] === $ranked[$i - 1]['obtained'] ? $positions[$ranked[$i - 1]['student']->id] : $i + 1;
        }

        return $rows->map(fn ($r) => ['position' => $positions[$r['student']->id] ?? null] + $r);
    }
}
