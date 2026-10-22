<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\FeeInvoice;
use App\Models\Homework;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_creates_a_parent_login_and_parent_signs_in_with_phone(): void
    {
        $school = $this->fullSchool();
        [$ali, $sara, $other, $section, $exam] = $school->run(function () {
            $class = SchoolClass::create(['name' => 'Class 4']);
            $section = $class->sections()->create(['name' => 'A']);
            $mk = fn ($n, $phone) => Student::create(['name' => $n, 'class_id' => $class->id, 'section_id' => $section->id, 'father_phone' => $phone]);
            $ali = $mk('Ali', '0300-1234567');
            $sara = $mk('Sara', '+92 300 1234567');
            $other = $mk('Other Kid', '0311-0000000');

            StudentAttendance::create(['student_id' => $ali->id, 'section_id' => $section->id, 'date' => today(), 'status' => 'absent']);
            FeeInvoice::create(['student_id' => $ali->id, 'title' => 'October fee', 'issue_date' => today(), 'due_date' => today(), 'subtotal' => 2500, 'total' => 2500]);
            Homework::create(['section_id' => $section->id, 'date' => today(), 'title' => 'Learn table of 7']);
            Notice::create(['title' => 'Parents meeting', 'body' => 'Saturday', 'audience' => 'parents', 'publish_on' => today()]);
            Notice::create(['title' => 'Staff only memo', 'body' => 'x', 'audience' => 'staff', 'publish_on' => today()]);

            $exam = Exam::create(['name' => 'Mid Term', 'is_published' => true]);
            $paper = ExamSubject::create(['exam_id' => $exam->id, 'class_id' => $class->id, 'subject_id' => Subject::create(['name' => 'Maths'])->id, 'max_marks' => 50, 'pass_marks' => 17]);
            ExamMark::create(['exam_subject_id' => $paper->id, 'student_id' => $ali->id, 'marks' => 45]);

            return [$ali, $sara, $other, $section, $exam];
        });

        // School office creates the login from Ali's profile
        $this->actingAsRole($school);
        $this->post($this->portal("students/{$ali->id}/parent-login"), ['name' => 'Raza', 'phone' => '0300 1234567'])->assertSessionHasErrors('password');
        $this->post($this->portal("students/{$ali->id}/parent-login"), ['name' => 'Raza', 'phone' => '0300 1234567', 'password' => 'family1'])->assertSessionHas('success');

        $parent = User::withoutGlobalScopes()->where('role', 'parent')->sole();
        $this->assertSame('3001234567', $parent->phone);
        $this->assertNull($parent->email);
        $this->assertEqualsCanonicalizing([$ali->id, $sara->id], $parent->children()->withoutGlobalScopes()->pluck('students.id')->all());

        // Parent signs in with the phone number in any format
        auth('school')->logout();
        $this->post($this->portal('login'), ['email' => '+92-300-1234567', 'password' => 'family1'])->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
        $this->get($this->portal('dashboard'))->assertRedirect($this->schoolUrl('greenfield', 'my'));

        $this->get($this->portal('my'))->assertOk()->assertSee('Ali')->assertSee('Sara')->assertDontSee('Other Kid')
            ->assertSee('Parents meeting')->assertDontSee('Staff only memo')->assertSee('Absent');
        $this->get($this->portal("my/children/{$ali->id}"))->assertOk()->assertSee('October fee')->assertSee('Learn table of 7')->assertSee('Mid Term')->assertSee('90%');
        $this->get($this->portal("my/children/{$ali->id}/report-card/{$exam->id}"))->assertOk()->assertSee('Report card');
        $this->get($this->portal("my/children/{$ali->id}/vouchers"))->assertOk()->assertSee('Bank copy');

        // Not their child, and no staff pages
        $this->get($this->portal("my/children/{$other->id}"))->assertNotFound();
        foreach (['students', 'staff', 'fees', 'attendance', 'leave', 'notices', 'exams'] as $page) {
            $this->get($this->portal($page))->assertForbidden();
        }
    }

    public function test_unpublished_results_stay_hidden(): void
    {
        $school = $this->fullSchool();
        [$kid, $exam] = $school->run(function () {
            $class = SchoolClass::create(['name' => 'Class 4']);
            $section = $class->sections()->create(['name' => 'A']);
            $kid = Student::create(['name' => 'Kid', 'class_id' => $class->id, 'section_id' => $section->id]);
            $exam = Exam::create(['name' => 'Secret', 'is_published' => false]);
            ExamSubject::create(['exam_id' => $exam->id, 'class_id' => $class->id, 'subject_id' => Subject::create(['name' => 'Maths'])->id]);

            return [$kid, $exam];
        });
        $parent = $this->actingAsRole($school, 'parent');
        $school->run(fn () => $parent->children()->attach($kid->id));

        $this->get($this->portal("my/children/{$kid->id}"))->assertOk()->assertDontSee('Secret');
        $this->get($this->portal("my/children/{$kid->id}/report-card/{$exam->id}"))->assertNotFound();
    }
}
