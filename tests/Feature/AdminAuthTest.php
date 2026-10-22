<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_asks_to_create_the_first_super_admin(): void
    {
        $this->get('http://localhost/admin/login')->assertRedirect('http://localhost/admin/setup');
        $this->get('http://localhost/admin/setup')->assertOk()->assertSee('Create Super Admin');

        $this->post('http://localhost/admin/setup', [
            'name' => 'Awais',
            'email' => 'owner@aivrasol.com',
            'password' => 'strong-pass-2026',
            'password_confirmation' => 'strong-pass-2026',
        ])->assertRedirect('http://localhost/admin');

        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('platform_users', ['email' => 'owner@aivrasol.com', 'role' => 'super_admin']);
    }

    public function test_setup_is_closed_once_a_super_admin_exists(): void
    {
        $this->superAdmin();

        $this->get('http://localhost/admin/setup')->assertRedirect('http://localhost/admin/login');
        $this->post('http://localhost/admin/setup', [
            'name' => 'Intruder',
            'email' => 'x@evil.test',
            'password' => 'strong-pass-2026',
            'password_confirmation' => 'strong-pass-2026',
        ])->assertForbidden();

        $this->assertDatabaseMissing('platform_users', ['email' => 'x@evil.test']);
    }

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->superAdmin();
        $this->get('http://localhost/admin')->assertRedirect('http://localhost/admin/login');
        $this->get('http://localhost/admin/schools')->assertRedirect('http://localhost/admin/login');
    }

    public function test_super_admin_can_sign_in_and_out(): void
    {
        $admin = $this->superAdmin(['email' => 'boss@aivrasol.com']);

        $this->post('http://localhost/admin/login', ['email' => 'boss@aivrasol.com', 'password' => 'password'])
            ->assertRedirect('http://localhost/admin');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertNotNull($admin->fresh()->last_login_at);

        $this->get('http://localhost/admin')->assertOk()->assertSee('Dashboard');

        $this->post('http://localhost/admin/logout')->assertRedirect('http://localhost/admin/login');
        $this->assertGuest('web');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->superAdmin(['email' => 'boss@aivrasol.com']);

        $this->post('http://localhost/admin/login', ['email' => 'boss@aivrasol.com', 'password' => 'nope'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_inactive_platform_user_cannot_sign_in(): void
    {
        $this->superAdmin(['email' => 'old@aivrasol.com', 'is_active' => false]);

        $this->post('http://localhost/admin/login', ['email' => 'old@aivrasol.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $this->superAdmin(['email' => 'boss@aivrasol.com']);

        foreach (range(1, 5) as $ignored) {
            $this->post('http://localhost/admin/login', ['email' => 'boss@aivrasol.com', 'password' => 'wrong']);
        }

        $this->post('http://localhost/admin/login', ['email' => 'boss@aivrasol.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_admin_panel_pages_render(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        foreach (['/admin', '/admin/schools', '/admin/schools/create', '/admin/plans', '/admin/plans/create', '/admin/subscriptions', '/admin/demo-requests', '/admin/account', '/admin/system'] as $path) {
            $this->get('http://localhost'.$path)->assertOk();
        }
    }

    public function test_super_admin_can_run_database_updates(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->get('http://localhost/admin/system')->assertOk()->assertSee('Up to date');
        $this->post('http://localhost/admin/system/migrate')->assertRedirect()->assertSessionHas('success');
    }
}
