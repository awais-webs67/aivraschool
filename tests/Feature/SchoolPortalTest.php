<?php

namespace Tests\Feature;

use App\Enums\SchoolStatus;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SchoolPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_sign_in_at_their_school_address(): void
    {
        $this->createSchool('greenfield');

        $this->get($this->schoolUrl('greenfield'))->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertRedirect($this->schoolUrl('greenfield', 'login'));
        $this->get($this->schoolUrl('greenfield', 'login'))->assertOk()->assertSee('Greenfield School');

        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'ADMIN@greenfield.test', 'password' => 'secret-pass-1'])
            ->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));

        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk()->assertSee('Your modules');

        $this->post($this->schoolUrl('greenfield', 'logout'))->assertRedirect($this->schoolUrl('greenfield', 'login'));
        $this->assertGuest('school');
    }

    public function test_a_school_cannot_sign_in_with_another_schools_account(): void
    {
        $this->createSchool('greenfield', adminEmail: 'admin@greenfield.test');
        $this->createSchool('riverside', adminEmail: 'admin@riverside.test');

        $this->post($this->schoolUrl('riverside', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('school');
    }

    public function test_signed_in_user_cannot_open_another_schools_portal(): void
    {
        $this->createSchool('greenfield', adminEmail: 'admin@greenfield.test');
        $this->createSchool('riverside', adminEmail: 'admin@riverside.test');

        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'secret-pass-1']);
        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk();

        // Same browser session, other school's address: not signed in there.
        $this->get($this->schoolUrl('riverside', 'dashboard'))->assertRedirect($this->schoolUrl('riverside', 'login'));
    }

    public function test_the_same_email_can_be_used_in_two_schools(): void
    {
        $this->createSchool('greenfield', adminEmail: 'owner@example.com');
        $this->createSchool('riverside', adminEmail: 'owner@example.com');

        $this->assertSame(2, User::withoutGlobalScopes()->where('email', 'owner@example.com')->count());

        $this->post($this->schoolUrl('riverside', 'login'), ['email' => 'owner@example.com', 'password' => 'secret-pass-1'])
            ->assertRedirect($this->schoolUrl('riverside', 'dashboard'));
        $this->assertSame('riverside', auth('school')->user()->school->slug);
    }

    public function test_queries_only_see_the_current_schools_rows(): void
    {
        $greenfield = $this->createSchool('greenfield', adminEmail: 'a@greenfield.test');
        $riverside = $this->createSchool('riverside', adminEmail: 'a@riverside.test');

        $greenfield->run(fn () => User::create(['name' => 'Teacher', 'email' => 't@greenfield.test', 'password' => 'x', 'role' => 'teacher']));

        $this->assertSame(2, $greenfield->run(fn () => User::count()));
        $this->assertSame(1, $riverside->run(fn () => User::count()));
        $this->assertNull($riverside->run(fn () => User::where('email', 't@greenfield.test')->first()));

        // New rows are stamped with the current school automatically.
        $this->assertSame($greenfield->id, User::withoutGlobalScopes()->where('email', 't@greenfield.test')->value('school_id'));
    }

    public function test_school_data_is_hidden_when_no_school_is_current(): void
    {
        $this->createSchool('greenfield');

        $this->assertSame(0, User::count());
        $this->assertSame(1, User::withoutGlobalScopes()->count());

        $this->expectException(LogicException::class);
        User::create(['name' => 'Orphan', 'email' => 'o@x.test', 'password' => 'x']);
    }

    public function test_unknown_school_address_is_not_found(): void
    {
        $this->get($this->schoolUrl('nope', 'login'))->assertNotFound();
    }

    public function test_platform_pages_are_not_treated_as_schools(): void
    {
        $this->get('http://localhost/admin/login')->assertRedirect(route('admin.setup'));
        $this->get('http://localhost/up')->assertOk();
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
        $this->get($this->schoolUrl('greenfield', 'login'))->assertForbidden();
    }

    public function test_school_model_is_not_affected_by_school_scope(): void
    {
        $this->createSchool('greenfield');
        $this->createSchool('riverside', adminEmail: 'r@r.test');

        $this->assertSame(2, School::count());
    }
}
