<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Complaint;
use App\Models\FeeInvoice;
use App\Models\Incident;
use App\Models\Observation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: Section, 2: Student} */
    protected function setUpSchool(): array
    {
        $school = $this->fullSchool();

        return [$school, ...$school->run(function () {
            $section = SchoolClass::create(['name' => 'Class 8'])->sections()->create(['name' => 'A']);

            return [$section, Student::create(['name' => 'Hamza', 'admission_no' => 'A-100', 'gender' => 'male', 'father_name' => 'Tariq', 'class_id' => $section->class_id, 'section_id' => $section->id])];
        })];
    }

    public function test_teacher_records_behaviour_and_it_shows_on_the_profile(): void
    {
        [$school, , $hamza] = $this->setUpSchool();
        $this->actingAsRole($school, 'teacher');

        $this->post($this->portal('discipline'), ['admission_no' => 'NOPE', 'kind' => 'concern', 'category' => 'Late', 'date' => today()->toDateString(), 'description' => 'x'])->assertSessionHasErrors('admission_no');
        $this->post($this->portal('discipline'), ['admission_no' => 'A-100', 'kind' => 'concern', 'category' => 'Late coming', 'severity' => 'medium', 'date' => today()->toDateString(), 'description' => 'Came at 9am'])->assertSessionHas('success');
        $this->assertSame(1, Incident::withoutGlobalScopes()->count());

        $this->get($this->portal('students/'.$hamza->id))->assertSee('Behaviour')->assertSee('Came at 9am');
    }

    public function test_principal_observes_a_lesson(): void
    {
        [$school] = $this->setUpSchool();
        $teacher = $school->run(fn () => Staff::create(['name' => 'Nida']));
        $this->actingAsRole($school, 'principal');

        $ratings = ['planning' => 4, 'knowledge' => 5, 'delivery' => 4, 'engagement' => 3, 'control' => 4, 'assessment' => 4];
        $this->post($this->portal('observations'), ['staff_id' => $teacher->id, 'date' => today()->toDateString(), 'ratings' => $ratings, 'strengths' => 'Clear examples'])->assertSessionHas('success');

        $this->assertEquals(4.0, Observation::withoutGlobalScopes()->value('overall'));
        $this->get($this->portal('observations'))->assertOk()->assertSee('Clear examples');

        $this->actingAsRole($school, 'teacher');
        $this->get($this->portal('observations'))->assertForbidden();
    }

    public function test_leaving_certificate_checks_fees_and_marks_the_student_left(): void
    {
        [$school, , $hamza] = $this->setUpSchool();
        $school->run(fn () => FeeInvoice::create(['student_id' => $hamza->id, 'title' => 'Fee', 'issue_date' => today(), 'due_date' => today(), 'subtotal' => 1000, 'total' => 1000]));
        $this->actingAsRole($school);

        $this->post($this->portal('certificates/students/'.$hamza->id), ['type' => 'leaving', 'reason' => 'Moved'])->assertSessionHasErrors('type');
        $this->post($this->portal('certificates/students/'.$hamza->id), ['type' => 'leaving', 'reason' => 'Moved', 'mark_left' => 1, 'ignore_dues' => 1])->assertRedirect();

        $certificate = Certificate::withoutGlobalScopes()->sole();
        $this->assertSame('SLC-000001', $certificate->serial_no);
        $this->assertSame('left', $hamza->fresh()->status);
        $this->get($this->portal('certificates/'.$certificate->id))->assertOk()->assertSee('School Leaving Certificate')->assertSee('son of');
    }

    public function test_id_cards_print_for_a_section(): void
    {
        [$school, $section] = $this->setUpSchool();
        $this->actingAsRole($school, 'receptionist');

        $this->get($this->portal('certificates/id-cards?section='.$section->id))->assertOk()->assertSee('Hamza')->assertSee('data-qr', false);
    }

    public function test_front_office_visitors_and_complaints(): void
    {
        [$school] = $this->setUpSchool();
        $this->actingAsRole($school, 'receptionist');

        $this->post($this->portal('front-office/visitors'), ['name' => 'Mr Khan', 'purpose' => 'Fee query'])->assertSessionHas('success');
        $visitor = Visitor::withoutGlobalScopes()->sole();
        $this->post($this->portal('front-office/visitors/'.$visitor->id.'/out'));
        $this->assertNotNull($visitor->fresh()->out_at);

        $this->post($this->portal('front-office/complaints'), ['kind' => 'complaint', 'from_name' => 'Parent', 'details' => 'Bus late'])->assertSessionHas('success');
        $complaint = Complaint::withoutGlobalScopes()->sole();
        $this->put($this->portal('front-office/complaints/'.$complaint->id), ['status' => 'resolved', 'response' => 'Spoke to driver']);
        $this->assertNotNull($complaint->fresh()->resolved_at);

        $this->actingAsRole($school, 'teacher');
        $this->get($this->portal('front-office'))->assertForbidden();
    }
}
