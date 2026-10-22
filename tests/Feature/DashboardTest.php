<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\LeaveRequest;
use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TimetableEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_sees_the_school_at_a_glance(): void
    {
        $school = $this->fullSchool();
        $school->run(function () {
            $a = SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']);
            $b = SchoolClass::create(['name' => 'Class 2'])->sections()->create(['name' => 'A']);
            $ali = Student::create(['name' => 'Ali', 'class_id' => $a->class_id, 'section_id' => $a->id, 'date_of_birth' => today()->subYears(9)]);
            Student::create(['name' => 'Sara', 'class_id' => $b->class_id, 'section_id' => $b->id]);
            StudentAttendance::create(['student_id' => $ali->id, 'section_id' => $a->id, 'date' => today(), 'status' => 'present']);
            $t = Staff::create(['name' => 'Away Teacher']);
            StaffAttendance::create(['staff_id' => $t->id, 'date' => today(), 'status' => 'absent']);
            $p = Period::create(['name' => 'P1', 'starts_at' => '08:00', 'ends_at' => '08:40']);
            TimetableEntry::create(['section_id' => $a->id, 'day' => today()->dayOfWeekIso, 'period_id' => $p->id, 'staff_id' => $t->id]);
            LeaveRequest::create(['staff_id' => $t->id, 'type' => 'sick', 'from_date' => today()->addDay(), 'to_date' => today()->addDay(), 'days' => 1, 'reason' => 'Doctor']);
        });
        $this->actingAsRole($school, 'principal');

        $this->get($this->portal('dashboard'))->assertOk()
            ->assertSee('Present today')->assertSee('100%')
            ->assertSee('1 class without attendance today')
            ->assertSee('1 lesson of absent teachers have no cover')
            ->assertSee('1 leave request waiting for you')
            ->assertSee('Ali'); // birthday

        $this->get($this->portal('approvals'))->assertOk()->assertSee('Doctor');
    }

    public function test_teacher_sees_own_day(): void
    {
        $school = $this->fullSchool();
        $teacher = $this->actingAsRole($school, 'teacher');
        $school->run(function () use ($teacher) {
            $staff = Staff::create(['name' => 'Me', 'user_id' => $teacher->id]);
            $a = SchoolClass::create(['name' => 'Class 3'])->sections()->create(['name' => 'B', 'class_teacher_id' => $staff->id]);
            $p = Period::create(['name' => 'P1', 'starts_at' => '08:00', 'ends_at' => '08:40']);
            TimetableEntry::create(['section_id' => $a->id, 'day' => today()->dayOfWeekIso, 'period_id' => $p->id, 'staff_id' => $staff->id]);
        });

        $this->get($this->portal('dashboard'))->assertOk()->assertSee('My lessons today')->assertSee('Class 3 – B')->assertSee('Take attendance')
            ->assertDontSee('Needs your attention')->assertDontSee('Your modules');
        $this->get($this->portal('approvals'))->assertForbidden();
    }

    public function test_reports_show_and_export(): void
    {
        $school = $this->fullSchool();
        $school->run(function () {
            $a = SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']);
            $s = Student::create(['name' => 'Ali', 'gender' => 'male', 'class_id' => $a->class_id, 'section_id' => $a->id]);
            $inv = FeeInvoice::create(['student_id' => $s->id, 'title' => 'Fee', 'issue_date' => today(), 'due_date' => today(), 'subtotal' => 500, 'total' => 500, 'paid' => 500, 'status' => 'paid']);
            FeePayment::create(['receipt_no' => 'RC-000001', 'fee_invoice_id' => $inv->id, 'student_id' => $s->id, 'amount' => 500, 'method' => 'cash', 'paid_on' => today()]);
        });
        $this->actingAsRole($school, 'principal');

        $this->get($this->portal('reports'))->assertOk()->assertSee('Student strength');
        $this->get($this->portal('reports/strength'))->assertOk()->assertSee('Class 1 – A');
        $this->get($this->portal('reports/fees'))->assertOk()->assertSee('500');
        $csv = $this->get($this->portal('reports/strength?export=1'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Class,Boys,Girls,Total', $csv);
        $this->get($this->portal('reports/nope'))->assertNotFound();
    }
}
