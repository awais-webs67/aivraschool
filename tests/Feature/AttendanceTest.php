<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: School, 1: Section, 2: Section, 3: Student, 4: Student} */
    protected function setUpSchool(): array
    {
        $school = $this->fullSchool();

        return [$school, ...$school->run(function () {
            $a = SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']);
            $b = SchoolClass::create(['name' => 'Class 2'])->sections()->create(['name' => 'A']);
            $ali = Student::create(['name' => 'Ali', 'class_id' => $a->class_id, 'section_id' => $a->id, 'roll_no' => '1']);
            $sara = Student::create(['name' => 'Sara', 'class_id' => $a->class_id, 'section_id' => $a->id, 'roll_no' => '2']);

            return [$a, $b, $ali, $sara];
        })];
    }

    /** A teacher login linked to a staff record. */
    protected function teacher(School $school, ?Section $classOf = null): User
    {
        $user = $this->actingAsRole($school, 'teacher');
        $school->run(function () use ($user, $classOf) {
            $staff = Staff::create(['name' => $user->name, 'user_id' => $user->id]);
            $classOf?->update(['class_teacher_id' => $staff->id]);
        });

        return $user;
    }

    public function test_class_teacher_marks_attendance_for_own_section_only(): void
    {
        [$school, $a, $b, $ali, $sara] = $this->setUpSchool();
        $this->teacher($school, $a);

        $this->get($this->portal('attendance'))->assertOk()->assertSee('Class 1 – A')->assertDontSee('Class 2 – A');
        $this->get($this->portal("attendance/sections/{$b->id}"))->assertForbidden();
        $this->get($this->portal("attendance/sections/{$a->id}"))->assertOk()->assertSee('Ali');

        $this->post($this->portal("attendance/sections/{$a->id}"), [
            'date' => today()->toDateString(), 'status' => [$ali->id => 'present', $sara->id => 'absent'],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('absent', StudentAttendance::withoutGlobalScopes()->where('student_id', $sara->id)->value('status'));

        // Saving again updates the same rows
        $this->post($this->portal("attendance/sections/{$a->id}"), ['date' => today()->toDateString(), 'status' => [$ali->id => 'late', $sara->id => 'present']]);
        $this->assertSame(2, StudentAttendance::withoutGlobalScopes()->count());

        // Old days are locked for teachers, future days for everyone
        $this->post($this->portal("attendance/sections/{$a->id}"), ['date' => today()->subDays(20)->toDateString(), 'status' => [$ali->id => 'absent']])->assertForbidden();
        $this->post($this->portal("attendance/sections/{$a->id}"), ['date' => today()->addDay()->toDateString(), 'status' => [$ali->id => 'absent']])->assertSessionHasErrors('date');
    }

    public function test_register_and_profile_show_percentages(): void
    {
        [$school, $a, , $ali] = $this->setUpSchool();
        $school->run(function () use ($a, $ali) {
            foreach ([1 => 'present', 2 => 'present', 3 => 'absent', 4 => 'present'] as $d => $status) {
                StudentAttendance::create(['student_id' => $ali->id, 'section_id' => $a->id, 'date' => today()->startOfMonth()->addDays($d - 1), 'status' => $status]);
            }
        });
        $this->actingAsRole($school, 'principal');

        $this->get($this->portal("attendance/sections/{$a->id}/register"))->assertOk()->assertSee('75');
        $this->get($this->portal("students/{$ali->id}"))->assertOk()->assertSee('75%');
    }

    public function test_leave_request_and_approval_fills_staff_attendance(): void
    {
        [$school] = $this->setUpSchool();
        $teacher = $this->teacher($school);

        $this->post($this->portal('leave'), ['type' => 'sick', 'from_date' => today()->toDateString(), 'to_date' => today()->addDay()->toDateString(), 'reason' => 'Fever'])
            ->assertSessionHas('success');
        $this->post($this->portal('leave'), ['type' => 'sick', 'from_date' => today()->addDay()->toDateString(), 'to_date' => today()->addDay()->toDateString(), 'reason' => 'Again'])
            ->assertSessionHasErrors('from_date');
        $leave = LeaveRequest::withoutGlobalScopes()->sole();
        $this->assertSame(2, $leave->days);

        // Teachers can't approve
        $this->post($this->portal("leave/{$leave->id}/review"), ['decision' => 'approved'])->assertForbidden();

        $this->actingAsRole($school, 'principal');
        $this->get($this->portal('leave'))->assertOk()->assertSee('Fever');
        $this->post($this->portal("leave/{$leave->id}/review"), ['decision' => 'approved'])->assertSessionHas('success');

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame(2, StaffAttendance::withoutGlobalScopes()->where('status', 'leave')->count());
        $this->get($this->portal('attendance/staff'))->assertOk()->assertSee('On leave');
    }

    public function test_staff_attendance_is_saved_by_the_principal(): void
    {
        [$school] = $this->setUpSchool();
        $member = $school->run(fn () => Staff::create(['name' => 'Bilal']));
        $this->actingAsRole($school, 'principal');

        $this->post($this->portal('attendance/staff'), ['date' => today()->toDateString(), 'status' => [$member->id => 'late'], 'check_in' => [$member->id => '08:20']])
            ->assertRedirect();

        $row = StaffAttendance::withoutGlobalScopes()->sole();
        $this->assertSame('late', $row->status);
        $this->assertStringStartsWith('08:20', $row->check_in);

        $this->actingAsRole($school, 'teacher');
        $this->get($this->portal('attendance/staff'))->assertForbidden();
    }
}
