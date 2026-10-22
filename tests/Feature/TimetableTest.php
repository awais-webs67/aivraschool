<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Subject;
use App\Models\Substitution;
use App\Models\TimetableEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_builds_a_timetable_without_clashes(): void
    {
        $school = $this->fullSchool();
        [$a, $b, $maths, $ayesha] = $school->run(fn () => [
            SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']),
            SchoolClass::create(['name' => 'Class 2'])->sections()->create(['name' => 'A']),
            Subject::create(['name' => 'Maths']),
            Staff::create(['name' => 'Ayesha']),
        ]);
        $this->actingAsRole($school, 'principal');

        $this->post($this->portal('timetable/periods/standard'), ['start' => '08:00', 'minutes' => 40])->assertSessionHas('success');
        $p1 = Period::withoutGlobalScopes()->where('name', 'Period 1')->first();
        $this->assertSame(9, Period::withoutGlobalScopes()->count()); // 8 lessons + break

        $this->put($this->portal("timetable/sections/{$a->id}"), ['cell' => [1 => [$p1->id => ['subject_id' => $maths->id, 'staff_id' => $ayesha->id]]]])->assertRedirect();
        $this->get($this->portal('timetable?section='.$a->id))->assertOk()->assertSee('Maths')->assertSee('Ayesha');

        // Same teacher, same time, another class: refused
        $this->put($this->portal("timetable/sections/{$b->id}"), ['cell' => [1 => [$p1->id => ['subject_id' => $maths->id, 'staff_id' => $ayesha->id]]]])
            ->assertSessionHasErrors('cell');
        $this->assertSame(1, TimetableEntry::withoutGlobalScopes()->count());

        // Clearing a cell removes the lesson
        $this->put($this->portal("timetable/sections/{$a->id}"), ['cell' => [1 => [$p1->id => ['subject_id' => '', 'staff_id' => '']]]]);
        $this->assertSame(0, TimetableEntry::withoutGlobalScopes()->count());
    }

    public function test_teacher_sees_own_timetable_and_absent_teacher_gets_cover(): void
    {
        $school = $this->fullSchool();
        $teacher = $this->actingAsRole($school, 'teacher');
        [$entry, $free] = $school->run(function () use ($teacher) {
            $a = SchoolClass::create(['name' => 'Class 1'])->sections()->create(['name' => 'A']);
            $p = Period::create(['name' => 'Period 1', 'starts_at' => '08:00', 'ends_at' => '08:40']);
            $me = Staff::create(['name' => 'Me', 'user_id' => $teacher->id]);
            $absentee = Staff::create(['name' => 'Absent Ali']);
            $free = Staff::create(['name' => 'Free Fatima']);
            $entry = TimetableEntry::create(['section_id' => $a->id, 'day' => today()->dayOfWeekIso, 'period_id' => $p->id, 'staff_id' => $absentee->id]);
            TimetableEntry::create(['section_id' => SchoolClass::create(['name' => 'Class 2'])->sections()->create(['name' => 'A'])->id, 'day' => today()->dayOfWeekIso, 'period_id' => $p->id, 'staff_id' => $me->id]);
            StaffAttendance::create(['staff_id' => $absentee->id, 'date' => today(), 'status' => 'absent']);

            return [$entry, $free];
        });

        $this->get($this->portal('timetable'))->assertOk()->assertSee('Class 2 – A');
        $this->get($this->portal('timetable/substitutions'))->assertForbidden();

        $this->actingAsRole($school, 'principal');
        $this->get($this->portal('timetable/substitutions'))->assertOk()->assertSee('Absent Ali')->assertSee('Free Fatima (free)')->assertDontSee('Me (free)');
        $this->post($this->portal('timetable/substitutions'), ['date' => today()->toDateString(), 'sub' => [$entry->id => $free->id]])->assertSessionHas('success');

        $this->assertSame($free->id, Substitution::withoutGlobalScopes()->value('substitute_staff_id'));
    }
}
