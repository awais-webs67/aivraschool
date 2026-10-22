<?php

namespace Tests\Feature;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_subscription_activates_a_trial_school(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $plan = Plan::factory()->create(['price' => 5000]);

        $this->post(route('admin.schools.subscriptions.store', $school), [
            'plan_id' => $plan->id,
            'starts_at' => '2026-03-15',
            'payment_status' => 'unpaid',
        ])->assertRedirect(route('admin.schools.show', $school));

        $school->refresh();
        $this->assertSame(SchoolStatus::Active, $school->status);
        $this->assertNull($school->trial_ends_at);
        $this->assertSame('2026-04-14', $school->subscription_ends_at->toDateString());
        $this->assertEquals(5000, $school->subscriptions()->first()->amount);
    }

    public function test_per_student_plan_is_priced_by_student_count(): void
    {
        $school = $this->createSchool('greenfield');
        $plan = Plan::factory()->perStudent(50)->create(['price' => 1000]);

        $subscription = app(SubscriptionManager::class)->assign($school, $plan, ['starts_at' => '2026-01-01', 'student_count' => 400]);

        $this->assertEquals(21000, $subscription->amount); // 1,000 base + 400 × 50
        $this->assertSame('2026-01-31', $subscription->ends_at->toDateString());
    }

    public function test_lifetime_plan_never_expires(): void
    {
        $school = $this->createSchool('greenfield');
        $plan = Plan::factory()->lifetime(300000)->create();

        $subscription = app(SubscriptionManager::class)->assign($school, $plan, ['starts_at' => '2020-01-01']);

        $this->assertNull($subscription->ends_at);
        $this->assertNull($school->fresh()->subscription_ends_at);
        $this->assertTrue($school->fresh()->canAccess());
        $this->assertSame(0.0, $subscription->monthlyValue());
    }

    public function test_custom_amount_and_end_date_override_the_plan(): void
    {
        $school = $this->createSchool('greenfield');
        $plan = Plan::factory()->create(['price' => 5000]);

        $subscription = app(SubscriptionManager::class)->assign($school, $plan, [
            'starts_at' => '2026-01-01', 'ends_at' => '2026-06-30', 'amount' => 25000,
        ]);

        $this->assertEquals(25000, $subscription->amount);
        $this->assertSame('2026-06-30', $subscription->ends_at->toDateString());
    }

    public function test_expire_command_expires_overdue_schools_only(): void
    {
        config(['skooline.grace_days' => 7]);

        $trialOver = $this->createSchool('trialover', adminEmail: 'a@a.test');
        $trialOver->update(['trial_ends_at' => now()->subDay()]);

        $inGrace = $this->createSchool('ingrace', adminEmail: 'b@b.test');
        $inGrace->update(['status' => SchoolStatus::Active, 'subscription_ends_at' => now()->subDays(2)]);

        $overdue = $this->createSchool('overdue', adminEmail: 'c@c.test');
        $overdue->update(['status' => SchoolStatus::Active, 'subscription_ends_at' => now()->subDays(10)]);

        $fine = $this->createSchool('fine', adminEmail: 'd@d.test');

        $this->artisan('skooline:expire-subscriptions')->expectsOutputToContain('2 school(s)')->assertSuccessful();

        $this->assertSame(SchoolStatus::Expired, $trialOver->fresh()->status);
        $this->assertSame(SchoolStatus::Active, $inGrace->fresh()->status);
        $this->assertSame(SchoolStatus::Expired, $overdue->fresh()->status);
        $this->assertSame(SchoolStatus::Trial, $fine->fresh()->status);
    }

    public function test_suspend_and_reactivate(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');

        $this->post(route('admin.schools.suspend', $school), ['reason' => 'Payment overdue'])->assertRedirect();
        $this->assertSame(SchoolStatus::Suspended, $school->fresh()->status);
        $this->assertSame('Payment overdue', $school->fresh()->suspension_reason);

        $this->post(route('admin.schools.activate', $school))->assertRedirect();
        $this->assertSame(SchoolStatus::Trial, $school->fresh()->status);
        $this->assertNull($school->fresh()->suspended_at);
    }

    public function test_mark_subscription_paid(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $subscription = app(SubscriptionManager::class)->assign($school, Plan::factory()->create(), []);

        $this->post(route('admin.subscriptions.paid', $subscription), ['payment_method' => 'jazzcash', 'payment_reference' => 'TX-1'])
            ->assertRedirect();

        $this->assertSame('paid', $subscription->fresh()->payment_status);
        $this->assertSame('jazzcash', $subscription->fresh()->payment_method);
    }

    public function test_dashboard_reports_mrr(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        app(SubscriptionManager::class)->assign($school, Plan::factory()->yearly(120000)->create(), []);

        $this->assertEquals(10000, app(SubscriptionManager::class)->monthlyRecurringRevenue());
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('PKR 10,000');
    }

    public function test_trial_can_be_extended(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield');
        $before = $school->trial_ends_at;

        $this->post(route('admin.schools.extend-trial', $school), ['days' => 10])->assertRedirect();

        $this->assertTrue($school->fresh()->trial_ends_at->isSameDay($before->copy()->addDays(10)));
        $this->assertInstanceOf(School::class, $school->fresh());
    }
}
