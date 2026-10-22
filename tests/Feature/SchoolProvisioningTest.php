<?php

namespace Tests\Feature;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Greenfield Grammar School',
            'slug' => 'greenfield',
            'city' => 'Lahore',
            'plan_id' => Plan::factory()->create()->id,
            'billing_mode' => 'trial',
            'trial_days' => 14,
            'admin_name' => 'Sara Ahmed',
            'admin_email' => 'Sara@Greenfield.test',
            'admin_password' => '',
        ];
    }

    public function test_super_admin_can_add_a_school_on_trial(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $response = $this->post('http://localhost/admin/schools', $this->validPayload());

        $school = School::where('slug', 'greenfield')->firstOrFail();
        $response->assertRedirect(route('admin.schools.show', $school))
            ->assertSessionHas('credentials', fn ($c) => $c['email'] === 'sara@greenfield.test'
                && strlen($c['password']) === 12
                && $c['url'] === 'http://localhost/greenfield/login');

        $this->assertSame(SchoolStatus::Trial, $school->status);
        $this->assertTrue($school->trial_ends_at->isSameDay(now()->addDays(14)));
        $admin = $school->users()->first();
        $this->assertSame('sara@greenfield.test', $admin->email);
        $this->assertSame('admin', $admin->role);
    }

    public function test_school_can_start_with_a_paid_subscription(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $plan = Plan::factory()->yearly(60000)->create();

        $this->post('http://localhost/admin/schools', $this->validPayload([
            'plan_id' => $plan->id,
            'billing_mode' => 'paid',
            'starts_at' => '2026-01-01',
            'payment_status' => 'paid',
            'admin_password' => 'chosen-password',
        ]))->assertSessionHasNoErrors();

        $school = School::where('slug', 'greenfield')->firstOrFail();
        $this->assertSame(SchoolStatus::Active, $school->status);
        $this->assertSame('2026-12-31', $school->subscription_ends_at->toDateString());

        $subscription = $school->subscriptions()->first();
        $this->assertEquals(60000, $subscription->amount);
        $this->assertSame('paid', $subscription->payment_status);
        $this->assertNotNull($subscription->paid_at);
    }

    public function test_school_address_must_be_valid_unique_and_not_reserved(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $this->createSchool('greenfield');

        foreach (['greenfield', 'admin', 'setup', 'Bad Name!', 'ab', '-start', 'a--b'] as $slug) {
            $this->post('http://localhost/admin/schools', $this->validPayload(['slug' => $slug]))
                ->assertSessionHasErrors('slug');
        }

        $this->assertSame(1, School::count());
    }

    public function test_deleting_a_school_requires_confirmation_and_removes_its_data(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');

        $this->delete(route('admin.schools.destroy', $school), ['confirm_slug' => 'wrong'])
            ->assertSessionHasErrors('confirm_slug');
        $this->assertModelExists($school);

        $this->delete(route('admin.schools.destroy', $school), ['confirm_slug' => 'greenfield'])
            ->assertRedirect(route('admin.schools.index'));

        $this->assertSame(0, School::count());
        $this->assertDatabaseMissing('users', ['school_id' => $school->id]);
    }

    public function test_school_pages_render_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');

        $this->get(route('admin.schools.index'))->assertOk()->assertSee('Greenfield School');
        $this->get(route('admin.schools.show', $school))->assertOk()->assertSee('admin@greenfield.test')->assertSee('never signed in');

        // After the school admin has signed in, the page must still render (dates read from the school's DB).
        $school->users()->update(['last_login_at' => now()->subHour()]);
        $this->get(route('admin.schools.show', $school))->assertOk()->assertSee('last login 1 hour ago');
        $this->get(route('admin.schools.edit', $school))->assertOk();
        $this->get(route('admin.schools.index', ['q' => 'nothing-matches']))->assertOk()->assertSee('No schools match');
    }

    public function test_super_admin_can_reset_a_school_admin_password(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $adminId = $school->users()->first()->id;

        $this->post(route('admin.schools.reset-password', $school), ['user_id' => $adminId])
            ->assertSessionHas('credentials');

        $newPassword = session('credentials')['password'];
        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => $newPassword])
            ->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
    }
}
