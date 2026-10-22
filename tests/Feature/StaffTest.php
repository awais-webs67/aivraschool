<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function signIn(string $slug = 'greenfield', string $email = 'admin@greenfield.test'): void
    {
        $this->forgetCurrentSchool();
        $this->post($this->schoolUrl($slug, 'login'), ['email' => $email, 'password' => 'secret-pass-1']);
        $this->forgetCurrentSchool();
    }

    protected function url(string $path): string
    {
        $this->forgetCurrentSchool();

        return $this->schoolUrl('greenfield', $path);
    }

    public function test_admin_adds_a_teacher_with_a_login(): void
    {
        $this->createSchool();
        $this->signIn();

        $this->get($this->url('staff'))->assertOk()->assertSee('No staff added yet');
        $this->post($this->url('staff'), [
            'name' => 'Ayesha Malik', 'designation' => 'Teacher', 'department' => 'Teaching', 'status' => 'active',
            'email' => 'Ayesha@Greenfield.test', 'login' => 1, 'role' => 'teacher', 'password' => 'teach-pass-1',
        ])->assertRedirect();

        $member = Staff::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('EMP-0001', $member->employee_no);
        $user = User::withoutGlobalScopes()->findOrFail($member->user_id);
        $this->assertSame('ayesha@greenfield.test', $user->email);
        $this->assertSame('teacher', $user->role);

        $this->get($this->url('staff'))->assertSee('Ayesha Malik')->assertSee('Teacher');
        $this->get($this->url('staff/'.$member->id))->assertOk()->assertSee('EMP-0001');

        // The teacher can sign in with their own password, but can't manage staff
        $this->post($this->url('logout'));
        $this->post($this->url('login'), ['email' => 'ayesha@greenfield.test', 'password' => 'teach-pass-1'])->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
        $this->get($this->url('staff'))->assertForbidden();
    }

    public function test_left_staff_lose_their_login(): void
    {
        $this->createSchool();
        $this->signIn();
        $this->post($this->url('staff'), ['name' => 'Bilal', 'status' => 'active', 'email' => 'b@g.test', 'login' => 1, 'role' => 'accountant', 'password' => 'acc-pass-12']);
        $member = Staff::withoutGlobalScopes()->firstOrFail();

        $this->put($this->url('staff/'.$member->id), ['name' => 'Bilal', 'employee_no' => $member->employee_no, 'status' => 'left', 'email' => 'b@g.test', 'login' => 1, 'role' => 'accountant'])->assertRedirect();

        $this->assertFalse(User::withoutGlobalScopes()->findOrFail($member->user_id)->is_active);
        $this->assertNotNull($member->fresh()->left_on);
    }

    public function test_principal_cannot_create_school_admins_or_lock_themselves_out(): void
    {
        $school = $this->createSchool();
        $school->run(fn () => User::create(['name' => 'P', 'email' => 'p@greenfield.test', 'password' => 'secret-pass-1', 'role' => 'principal']));
        $this->signIn(email: 'p@greenfield.test');

        $this->post($this->url('staff'), ['name' => 'X', 'status' => 'active', 'email' => 'x@g.test', 'login' => 1, 'role' => 'admin', 'password' => 'abc-pass-12'])
            ->assertSessionHasErrors('role');

        $self = $school->run(fn () => Staff::create(['name' => 'P', 'email' => 'p@greenfield.test', 'user_id' => User::where('email', 'p@greenfield.test')->value('id')]));
        $this->put($this->url('staff/'.$self->id), ['name' => 'P', 'employee_no' => $self->employee_no, 'status' => 'active', 'email' => 'p@greenfield.test', 'login' => 0])
            ->assertSessionHasErrors('role');
    }

    public function test_class_teachers_are_saved(): void
    {
        $school = $this->createSchool();
        [$section, $teacher] = $school->run(function () {
            $class = SchoolClass::create(['name' => 'Class 1']);

            return [$class->sections()->create(['name' => 'A']), Staff::create(['name' => 'Sara'])];
        });
        $this->signIn();

        $this->get($this->url('staff/class-teachers'))->assertOk()->assertSee('Class 1');
        $this->put($this->url('staff/class-teachers'), ['teacher' => [$section->id => $teacher->id]])->assertRedirect();

        $this->assertSame($teacher->id, Section::withoutGlobalScopes()->find($section->id)->class_teacher_id);
    }

    public function test_staff_of_another_school_are_not_visible(): void
    {
        $other = $this->createSchool('riverside', adminEmail: 'admin@riverside.test');
        $foreign = $other->run(fn () => Staff::create(['name' => 'Hidden Person']));
        $this->createSchool();
        $this->signIn();

        $this->get($this->url('staff/'.$foreign->id))->assertNotFound();
        $this->get($this->url('staff'))->assertDontSee('Hidden Person');
    }
}
