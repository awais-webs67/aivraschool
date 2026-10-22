<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_per_student_plan(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.plans.store'), [
            'name' => 'Growth Plan',
            'billing_model' => 'per_student',
            'price' => 0,
            'price_per_student' => 45,
            'trial_days' => 14,
            'max_branches' => 2,
            'storage_gb' => 20,
            'modules' => ['students', 'fees', 'exams'],
            'is_active' => '1',
            'show_on_website' => '1',
            'is_featured' => '0',
        ])->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('slug', 'growth-plan')->firstOrFail();
        $this->assertTrue($plan->isPerStudent());
        $this->assertEquals(45, $plan->price_per_student);
        $this->assertSame(['students', 'fees', 'exams'], $plan->modules);
        $this->assertFalse($plan->is_featured);
        $this->assertEquals(4500, $plan->priceFor(100));
    }

    public function test_plan_validation_rejects_unknown_billing_models_and_modules(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.plans.store'), [
            'name' => 'Broken',
            'billing_model' => 'weekly',
            'price' => 100,
            'trial_days' => 0,
            'max_branches' => 1,
            'storage_gb' => 1,
            'modules' => ['teleportation'],
        ])->assertSessionHasErrors(['billing_model', 'modules.0']);
    }

    public function test_plan_can_be_updated(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $plan = Plan::factory()->create(['name' => 'Basic']);

        $this->put(route('admin.plans.update', $plan), [
            'name' => 'Basic',
            'slug' => $plan->slug,
            'billing_model' => 'yearly',
            'price' => 50000,
            'trial_days' => 30,
            'max_branches' => 1,
            'storage_gb' => 10,
            'modules' => ['students'],
        ])->assertRedirect(route('admin.plans.index'));

        $this->assertSame('yearly', $plan->fresh()->billing_model);
        $this->assertSame(12, $plan->fresh()->cycleMonths());
        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_plan_in_use_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $plan = Plan::factory()->create();
        $this->createSchool('greenfield', $plan);

        $this->delete(route('admin.plans.destroy', $plan))->assertSessionHas('error');
        $this->assertModelExists($plan);

        $unused = Plan::factory()->create();
        $this->delete(route('admin.plans.destroy', $unused))->assertRedirect(route('admin.plans.index'));
        $this->assertModelMissing($unused);
    }

    public function test_school_only_gets_modules_from_its_plan(): void
    {
        $school = $this->createSchool('greenfield', Plan::factory()->create(['modules' => ['students', 'fees']]));

        $this->assertTrue($school->hasModule('fees'));
        $this->assertFalse($school->hasModule('transport'));
    }
}
