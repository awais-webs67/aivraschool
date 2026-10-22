<?php

namespace Tests\Feature;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use App\Models\School\User;
use App\Models\School\User as SchoolUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Greenfield Grammar School',
            'subdomain' => 'greenfield',
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

        $school = School::where('subdomain', 'greenfield')->firstOrFail();
        $response->assertRedirect(route('admin.schools.show', $school))
            ->assertSessionHas('credentials', fn ($c) => $c['email'] === 'sara@greenfield.test'
                && strlen($c['password']) === 12
                && $c['url'] === 'http://greenfield.localhost/login');

        $this->assertSame(SchoolStatus::Trial, $school->status);
        $this->assertTrue($school->trial_ends_at->isSameDay(now()->addDays(14)));
        $this->assertSame('greenfield', $school->domains()->first()->domain);
        $this->assertFileExists(database_path($school->database()->getName()));

        $admin = $school->run(fn () => SchoolUser::first());
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

        $school = School::where('subdomain', 'greenfield')->firstOrFail();
        $this->assertSame(SchoolStatus::Active, $school->status);
        $this->assertSame('2026-12-31', $school->subscription_ends_at->toDateString());

        $subscription = $school->subscriptions()->first();
        $this->assertEquals(60000, $subscription->amount);
        $this->assertSame('paid', $subscription->payment_status);
        $this->assertNotNull($subscription->paid_at);
    }

    public function test_subdomain_must_be_valid_unique_and_not_reserved(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $this->createSchool('greenfield');

        foreach (['greenfield', 'admin', 'Bad Name!', 'ab', '-start'] as $subdomain) {
            $this->post('http://localhost/admin/schools', $this->validPayload(['subdomain' => $subdomain]))
                ->assertSessionHasErrors('subdomain');
        }

        $this->assertSame(1, School::count());
    }

    public function test_deleting_a_school_requires_confirmation_and_drops_its_database(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $file = database_path($school->database()->getName());

        $this->delete(route('admin.schools.destroy', $school), ['confirm_subdomain' => 'wrong'])
            ->assertSessionHasErrors('confirm_subdomain');
        $this->assertFileExists($file);

        $this->delete(route('admin.schools.destroy', $school), ['confirm_subdomain' => 'greenfield'])
            ->assertRedirect(route('admin.schools.index'));

        $this->assertSame(0, School::count());
        $this->assertFileDoesNotExist($file);
    }

    public function test_school_pages_render_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');

        $this->get(route('admin.schools.index'))->assertOk()->assertSee('Greenfield School');
        $this->get(route('admin.schools.show', $school))->assertOk()->assertSee('admin@greenfield.test')->assertSee('never signed in');

        // After the school admin has signed in, the page must still render (dates read from the school's DB).
        $school->run(fn () => User::query()->update(['last_login_at' => now()->subHour()]));
        $this->get(route('admin.schools.show', $school))->assertOk()->assertSee('last login 1 hour ago');
        $this->get(route('admin.schools.edit', $school))->assertOk();
        $this->get(route('admin.schools.index', ['q' => 'nothing-matches']))->assertOk()->assertSee('No schools match');
    }

    public function test_super_admin_can_reset_a_school_admin_password(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $adminId = $school->run(fn () => SchoolUser::first()->id);

        $this->post(route('admin.schools.reset-password', $school), ['user_id' => $adminId])
            ->assertSessionHas('credentials');

        $newPassword = session('credentials')['password'];
        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => $newPassword])
            ->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
    }
}
