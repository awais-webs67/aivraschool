<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_branding_and_public_plans(): void
    {
        Plan::factory()->create(['name' => 'Starter', 'tagline' => 'For small schools']);
        Plan::factory()->create(['name' => 'Hidden Plan', 'show_on_website' => false]);
        Plan::factory()->create(['name' => 'Retired Plan', 'is_active' => false]);

        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee('Skooline')
            ->assertSee('AivraSol')
            ->assertSee('Starter')
            ->assertSee('For small schools')
            ->assertDontSee('Hidden Plan')
            ->assertDontSee('Retired Plan');
    }

    public function test_visitor_can_request_a_demo(): void
    {
        $this->post('http://localhost/demo-request', [
            'name' => 'Ali Khan',
            'school_name' => 'City Model School',
            'phone' => '03001234567',
            'city' => 'Lahore',
            'students' => 450,
        ])->assertRedirect()->assertSessionHas('demo_success');

        $this->assertDatabaseHas('demo_requests', ['school_name' => 'City Model School', 'status' => 'new']);
    }

    public function test_demo_request_requires_name_school_and_phone(): void
    {
        $this->post('http://localhost/demo-request', [])->assertSessionHasErrors(['name', 'school_name', 'phone']);

        $this->assertSame(0, DemoRequest::count());
    }
}
