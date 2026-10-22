<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Controllers\School\Daily\AttendanceController;
use App\Models\Exam;
use App\Models\FeeInvoice;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\Period;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Services\School\FeeService;
use App\Services\School\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * What a parent sees: their children's attendance, fees, results, homework, timetable and notices.
 */
class ParentPortalController extends Controller
{
    public function home(Request $request): View
    {
        $user = $request->user('school');
        $children = $this->children($user);

        return view('school.parent.home', [
            'children' => $children->map(fn (Student $s) => [
                'student' => $s,
                'attendance' => AttendanceController::summarise(StudentAttendance::query()->where('student_id', $s->id)->where('date', '>=', today()->startOfMonth()->toDateString())->get()),
                'today' => StudentAttendance::query()->where('student_id', $s->id)->whereDate('date', today())->value('status'),
                'balance' => FeeService::balanceOf($s),
                'homework' => $s->section_id ? Homework::query()->where('section_id', $s->section_id)->whereDate('date', today())->count() : 0,
            ]),
            'notices' => $this->notices($children),
        ]);
    }

    public function child(Request $request, Student $student, ResultService $results): View
    {
        $this->authorizeChild($request->user('school'), $student);
        $student->load('schoolClass', 'section.classTeacher');
        $month = today()->startOfMonth();

        $attendance = StudentAttendance::query()->where('student_id', $student->id)->where('date', '>=', today()->subDays(60)->toDateString())->orderByDesc('date')->get();
        $exams = Exam::query()->where('is_published', true)->whereHas('papers', fn ($q) => $q->where('class_id', $student->class_id))->latest('starts_on')->latest('id')->limit(6)->get();

        return view('school.parent.child', [
            'student' => $student,
            'month' => AttendanceController::summarise($attendance->where('date', '>=', $month)),
            'recentAttendance' => $attendance->take(30),
            'invoices' => FeeInvoice::query()->where('student_id', $student->id)->where('status', '!=', 'cancelled')->latest('due_date')->limit(12)->get(),
            'balance' => FeeService::balanceOf($student),
            'results' => $student->section ? $exams->map(fn ($e) => [$e, $results->forStudent($e, $student)]) : collect(),
            'homework' => $student->section_id ? Homework::query()->with('subject')->where('section_id', $student->section_id)->where('date', '>=', today()->subDays(14)->toDateString())->latest('date')->get()->groupBy(fn ($h) => $h->date->toDateString()) : collect(),
            'periods' => Period::query()->ordered()->get(),
            'days' => TimetableEntry::workingDays(),
            'timetable' => $student->section_id ? TimetableEntry::query()->with('subject', 'staff')->where('section_id', $student->section_id)->get()->keyBy(fn ($e) => $e->day.'-'.$e->period_id) : collect(),
        ]);
    }

    public function reportCard(Request $request, Student $student, Exam $exam, ResultService $results): View
    {
        $this->authorizeChild($request->user('school'), $student);
        abort_unless($exam->is_published && $student->section, 404);
        $result = $results->forStudent($exam, $student);
        abort_unless($result['row'], 404);

        return view('school.exams.report-cards', [
            'exam' => $exam,
            'section' => $student->section->load('schoolClass', 'classTeacher'),
            'papers' => $result['papers'],
            'rows' => collect([$result['row']]),
            'classSize' => $result['classSize'],
            'attendance' => collect([$student->id => AttendanceController::summarise(StudentAttendance::query()->where('student_id', $student->id)->get())]),
            'school' => current_school(),
        ]);
    }

    /** Printable open fee vouchers of a child. */
    public function vouchers(Request $request, Student $student): View
    {
        $this->authorizeChild($request->user('school'), $student);
        $invoices = FeeInvoice::query()->with('items', 'student.schoolClass', 'student.section')->open()->where('student_id', $student->id)->get();

        return view('school.fees.print', ['invoices' => $invoices, 'arrears' => collect([$student->id => $invoices]), 'school' => current_school()]);
    }

    protected function children(User $user): Collection
    {
        return $user->children()->with('schoolClass', 'section')->where('status', 'active')->orderBy('name')->get();
    }

    protected function notices(Collection $children): Collection
    {
        return Notice::query()->live()->forParents($children->pluck('section_id')->filter()->all())
            ->orderByDesc('is_pinned')->latest('publish_on')->limit(10)->get();
    }

    protected function authorizeChild(User $user, Student $student): void
    {
        abort_unless($user->children()->whereKey($student->id)->exists(), 404);
    }
}
