<?php

namespace Tests\Feature;

use App\Enums\SchoolStatus;
use App\Models\School;
use App\Models\School\User as SchoolUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_sign_in_on_their_own_subdomain(): void
    {
        $this->createSchool('greenfield');

        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertRedirect($this->schoolUrl('greenfield', 'login'));
        $this->get($this->schoolUrl('greenfield', 'login'))->assertOk()->assertSee('Greenfield School');

        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'ADMIN@greenfield.test', 'password' => 'secret-pass-1'])
            ->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));

        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk()->assertSee('Your modules');
    }

    public function test_schools_are_isolated_from_each_other(): void
    {
        $this->createSchool('greenfield', adminEmail: 'admin@greenfield.test');
        $this->createSchool('riverside', adminEmail: 'admin@riverside.test');

        // Greenfield's admin does not exist in Riverside's database.
        $this->post($this->schoolUrl('riverside', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('school');

        $greenfield = School::where('subdomain', 'greenfield')->first();
        $riverside = School::where('subdomain', 'riverside')->first();

        $greenfield->run(fn () => SchoolUser::create(['name' => 'Teacher', 'email' => 't@greenfield.test', 'password' => 'x', 'role' => 'teacher']));

        $this->assertSame(2, $greenfield->run(fn () => SchoolUser::count()));
        $this->assertSame(1, $riverside->run(fn () => SchoolUser::count()));
    }

    public function test_unknown_subdomain_is_not_found(): void
    {
        $this->get($this->schoolUrl('nope', 'login'))->assertNotFound();
    }

    public function test_school_routes_are_not_served_on_the_central_domain(): void
    {
        $this->get('http://localhost/dashboard')->assertNotFound();
    }

    public function test_suspended_school_cannot_be_used(): void
    {
        $school = $this->createSchool('greenfield');
        $school->update(['status' => SchoolStatus::Suspended, 'suspended_at' => now()]);

        $this->get($this->schoolUrl('greenfield', 'login'))->assertForbidden()->assertSee('Account suspended');
        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'secret-pass-1'])
            ->assertForbidden();
        $this->assertGuest('school');
    }

    public function test_expired_trial_blocks_access(): void
    {
        $school = $this->createSchool('greenfield');
        $school->update(['trial_ends_at' => now()->subDay()]);

        $this->get($this->schoolUrl('greenfield', 'login'))->assertForbidden()->assertSee('Subscription expired');
    }

    public function test_active_school_keeps_access_during_grace_period(): void
    {
        config(['skooline.grace_days' => 7]);
        $school = $this->createSchool('greenfield');
        $school->update(['status' => SchoolStatus::Active, 'trial_ends_at' => null, 'subscription_ends_at' => now()->subDays(3)]);

        $this->assertTrue($school->fresh()->inGracePeriod());
        $this->get($this->schoolUrl('greenfield', 'login'))->assertOk();

        $school->update(['subscription_ends_at' => now()->subDays(8)]);
        tenancy()->end(); // each real request starts fresh; reset the in-process tenant between test requests
        $this->get($this->schoolUrl('greenfield', 'login'))->assertForbidden();
    }
}
