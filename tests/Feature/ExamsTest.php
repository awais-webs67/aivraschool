<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Services\School\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_exam_flow_with_positions_and_report_cards(): void
    {
        $school = $this->fullSchool();
        [$section, $maths, $english, $ali, $sara, $bilal] = $school->run(function () {
            $class = SchoolClass::create(['name' => 'Class 5']);
            $section = $class->sections()->create(['name' => 'A']);
            $maths = Subject::create(['name' => 'Maths']);
            $english = Subject::create(['name' => 'English']);
            $class->subjects()->attach([$maths->id, $english->id]);
            $mk = fn ($n, $r) => Student::create(['name' => $n, 'roll_no' => $r, 'class_id' => $class->id, 'section_id' => $section->id]);

            return [$section, $maths, $english, $mk('Ali', 1), $mk('Sara', 2), $mk('Bilal', 3)];
        });
        $this->actingAsRole($school, 'principal');

        $this->post($this->portal('exams'), ['name' => 'Mid Term'])->assertRedirect();
        $exam = Exam::withoutGlobalScopes()->sole();
        $this->put($this->portal("exams/{$exam->id}/classes/{$section->class_id}/papers"), ['papers' => [
            $maths->id => ['on' => 1, 'max_marks' => 100, 'pass_marks' => 33],
            $english->id => ['on' => 1, 'max_marks' => 50, 'pass_marks' => 17],
        ]])->assertSessionHas('success');
        [$pm, $pe] = [ExamSubject::withoutGlobalScopes()->where('subject_id', $maths->id)->first(), ExamSubject::withoutGlobalScopes()->where('subject_id', $english->id)->first()];

        $this->put($this->portal("exams/{$exam->id}/papers/{$pm->id}/sections/{$section->id}"), ['marks' => [$ali->id => 120]])->assertSessionHasErrors('marks');
        $this->put($this->portal("exams/{$exam->id}/papers/{$pm->id}/sections/{$section->id}"), ['marks' => [$ali->id => 90, $sara->id => 80, $bilal->id => 20]])->assertRedirect();
        $this->put($this->portal("exams/{$exam->id}/papers/{$pe->id}/sections/{$section->id}"), ['marks' => [$ali->id => 40, $sara->id => 50], 'absent' => [$bilal->id => 1]]);

        $rows = $school->run(fn () => app(ResultService::class)->forSection($exam, $section)['rows'])->keyBy(fn ($r) => $r['student']->name);
        $this->assertSame(1, $rows['Ali']['position']);   // 130
        $this->assertSame(1, $rows['Sara']['position']);  // 130, shares 1st
        $this->assertSame(3, $rows['Bilal']['position']);
        $this->assertEquals(86.67, $rows['Ali']['percent']);
        $this->assertFalse($rows['Bilal']['passed']);
        $this->assertSame('A', $rows['Ali']['grade']);

        $this->get($this->portal("exams/{$exam->id}/sections/{$section->id}/results"))->assertOk()->assertSee('Fail');
        $this->get($this->portal("exams/{$exam->id}/sections/{$section->id}/report-cards?student={$ali->id}"))->assertOk()->assertSee('Report card')->assertSee('1 of 3');
        $this->get($this->portal("exams/{$exam->id}/analysis"))->assertOk()->assertSee('Top 10 students');
        $this->get($this->portal("students/{$ali->id}"))->assertSee('Exam results');

        // Locking stops changes
        $this->post($this->portal("exams/{$exam->id}/toggle"), ['field' => 'is_locked']);
        $this->put($this->portal("exams/{$exam->id}/papers/{$pm->id}/sections/{$section->id}"), ['marks' => [$ali->id => 10]])->assertForbidden();
        $this->assertEquals(90, ExamMark::withoutGlobalScopes()->where('student_id', $ali->id)->where('exam_subject_id', $pm->id)->value('marks'));
    }

    public function test_teacher_enters_marks_only_for_own_section(): void
    {
        $school = $this->fullSchool();
        $teacher = $this->actingAsRole($school, 'teacher');
        [$exam, $paper, $mine, $other] = $school->run(function () use ($teacher) {
            $staff = Staff::create(['name' => 'T', 'user_id' => $teacher->id]);
            $class = SchoolClass::create(['name' => 'Class 5']);
            $mine = $class->sections()->create(['name' => 'A', 'class_teacher_id' => $staff->id]);
            $other = $class->sections()->create(['name' => 'B']);
            $exam = Exam::create(['name' => 'Final']);
            $paper = ExamSubject::create(['exam_id' => $exam->id, 'class_id' => $class->id, 'subject_id' => Subject::create(['name' => 'Maths'])->id, 'max_marks' => 100, 'pass_marks' => 33]);

            return [$exam, $paper, $mine, $other];
        });

        $this->get($this->portal("exams/{$exam->id}/papers/{$paper->id}/sections/{$mine->id}"))->assertOk();
        $this->get($this->portal("exams/{$exam->id}/papers/{$paper->id}/sections/{$other->id}"))->assertForbidden();
        $this->post($this->portal('exams'), ['name' => 'X'])->assertForbidden();
    }
}
