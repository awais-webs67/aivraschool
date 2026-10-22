<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: Section, 2: Section} school, Class 1-A, Class 2-A */
    protected function school(): array
    {
        $school = $this->fullSchool();
        [$a, $b] = $school->run(function () {
            $one = SchoolClass::create(['name' => 'Class 1', 'sort_order' => 1]);
            $two = SchoolClass::create(['name' => 'Class 2', 'sort_order' => 2]);

            return [$one->sections()->create(['name' => 'A']), $two->sections()->create(['name' => 'A'])];
        });

        return [$school, $a, $b];
    }

    protected function student(School $school, Section $section, array $attributes = []): Student
    {
        return $school->run(fn () => Student::create($attributes + ['name' => 'Ali Raza', 'class_id' => $section->class_id, 'section_id' => $section->id, 'father_phone' => '0300 1234567']));
    }

    public function test_admin_admits_a_student_and_siblings_are_linked(): void
    {
        [$school, $a] = $this->school();
        $this->actingAsRole($school);

        $this->get($this->portal('students'))->assertOk()->assertSee('No students yet');
        $this->post($this->portal('students'), ['name' => 'Ali Raza', 'section_id' => $a->id])->assertSessionHasErrors('father_phone');

        $this->post($this->portal('students'), [
            'name' => 'Ali Raza', 'section_id' => $a->id, 'gender' => 'male', 'father_name' => 'Raza Ahmed', 'father_phone' => '0300-1234567',
        ])->assertRedirect();
        $ali = Student::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(now()->format('Y').'-0001', $ali->admission_no);
        $this->assertSame($a->class_id, $ali->class_id);

        $this->get($this->portal('students/create?sibling_of='.$ali->id))->assertSee('Raza Ahmed');
        $this->post($this->portal('students'), ['name' => 'Sara Raza', 'section_id' => $a->id, 'father_phone' => '+92 300 1234567'])->assertSessionHasNoErrors();

        $this->get($this->portal('students/'.$ali->id))->assertOk()->assertSee('Siblings')->assertSee('Sara Raza');
        $this->get($this->portal('students?q=Sara'))->assertSee('Sara Raza')->assertDontSee('Ali Raza');
    }

    public function test_teacher_can_view_but_not_change_students(): void
    {
        [$school, $a] = $this->school();
        $student = $this->student($school, $a);
        $this->actingAsRole($school, 'teacher');

        $this->get($this->portal('students'))->assertOk()->assertSee('Ali Raza')->assertDontSee('Admit student');
        $this->get($this->portal('students/create'))->assertForbidden();
        $this->delete($this->portal('students/'.$student->id))->assertForbidden();
    }

    public function test_module_must_be_in_the_plan(): void
    {
        $school = $this->createSchool(plan: Plan::factory()->create(['modules' => ['attendance']]));
        $this->actingAsRole($school);

        $this->get($this->portal('students'))->assertForbidden();
        $this->get($this->portal('dashboard'))->assertOk()->assertDontSee('href="'.$this->schoolUrl('greenfield', 'students').'"', false);
    }

    public function test_plan_student_limit_is_enforced(): void
    {
        [$school, $a] = $this->school();
        $school->plan->update(['max_students' => 1]);
        $this->student($school, $a);
        $this->actingAsRole($school);

        $this->get($this->portal('students/create'))->assertRedirect()->assertSessionHas('error');
    }

    public function test_csv_import_checks_every_row_first(): void
    {
        [$school] = $this->school();
        $this->actingAsRole($school);

        $bad = UploadedFile::fake()->createWithContent('s.csv', "name,class,section,date_of_birth\nAli,Class 1,A,2016-04-12\nSara,Class 9,A,\nBilal,Class 1,B,31/31/2016\n");
        $this->post($this->portal('students/import'), ['file' => $bad])->assertSessionHas('import_errors', fn ($e) => count($e) === 2);
        $this->assertSame(0, Student::withoutGlobalScopes()->count());

        $good = UploadedFile::fake()->createWithContent('s.csv', "\xEF\xBB\xBFName,Class,Section,Gender,Date of birth,Father phone\nAli,Class 1,A,M,12/04/2016,0300111\nSara,class 1,B,girl,,\n");
        $this->post($this->portal('students/import'), ['file' => $good])->assertRedirect()->assertSessionHas('success');

        $students = Student::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertCount(2, $students);
        $this->assertSame('2016-04-12', $students[0]->date_of_birth->toDateString());
        $this->assertSame('female', $students[1]->gender);
        $this->assertSame('B', Section::withoutGlobalScopes()->find($students[1]->section_id)->name); // section created
    }

    public function test_promote_moves_ticked_students(): void
    {
        [$school, $a, $b] = $this->school();
        $stay = $this->student($school, $a, ['name' => 'Stays Back']);
        $go = $this->student($school, $a, ['name' => 'Moves Up']);
        $this->actingAsRole($school);

        $this->get($this->portal('students/promote?from='.$a->id))->assertOk()->assertSee('Moves Up');
        $this->post($this->portal('students/promote'), ['from' => $a->id, 'action' => 'move', 'to' => $b->id, 'students' => [$go->id]])->assertRedirect();

        $this->assertSame($b->id, $go->fresh()->section_id);
        $this->assertSame($a->id, $stay->fresh()->section_id);

        $this->post($this->portal('students/promote'), ['from' => $a->id, 'action' => 'graduate', 'students' => [$stay->id]]);
        $this->assertSame('graduated', $stay->fresh()->status);
    }

    public function test_enquiry_to_admission_and_public_form(): void
    {
        [$school, $a] = $this->school();

        // Parent applies online (no sign-in)
        $this->post($this->portal('apply'), ['child_name' => 'Zara Khan', 'parent_name' => 'Imran Khan', 'phone' => '0333 5550000', 'class_id' => $a->class_id])
            ->assertSessionHas('success');
        $this->post($this->portal('apply'), ['child_name' => 'Bot', 'parent_name' => 'Bot', 'phone' => '1', 'website' => 'spam.example']);
        $enquiry = Enquiry::withoutGlobalScopes()->sole();
        $this->assertSame('website', $enquiry->source);

        $this->actingAsRole($school, 'receptionist');
        $this->get($this->portal('enquiries'))->assertOk()->assertSee('Zara Khan');
        $this->get($this->portal('students/create?enquiry='.$enquiry->id))->assertOk()->assertSee('Imran Khan');
        $this->post($this->portal('students'), ['name' => 'Zara Khan', 'section_id' => $a->id, 'father_phone' => '0333 5550000', 'enquiry_id' => $enquiry->id]);

        $this->assertSame('admitted', $enquiry->fresh()->status);
        $this->assertNotNull($enquiry->fresh()->student_id);
    }

    public function test_students_of_another_school_are_hidden(): void
    {
        $other = $this->fullSchool('riverside');
        $os = $other->run(function () {
            $sec = SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']);

            return Student::create(['name' => 'Foreign Kid', 'class_id' => $sec->class_id, 'section_id' => $sec->id]);
        });
        [$school] = $this->school();
        $this->actingAsRole($school);

        $this->get($this->portal('students/'.$os->id))->assertNotFound();
        $this->get($this->portal('students'))->assertDontSee('Foreign Kid');
    }
}
