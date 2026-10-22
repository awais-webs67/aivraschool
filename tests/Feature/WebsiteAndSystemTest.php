<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteAndSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_and_terms_pages_use_the_built_in_text(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy')->assertSee('What we collect');
        $this->get('/terms')->assertOk()->assertSee('Terms of Service')->assertSee('Plans and payment');
        $this->get('/')->assertSee(route('privacy'))->assertSee(route('terms'));
        $this->get('/sitemap.xml')->assertSee(route('privacy'));
    }

    public function test_admin_edits_website_content(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $this->get(route('admin.settings.website'))->assertOk()->assertSee('Testimonials');

        $this->put(route('admin.settings.website.update'), [
            'hero_title' => 'The school system for', 'hero_highlight' => 'busy principals.',
            'hero_points' => "Fast\n\nFriendly",
            'contact_phone' => '+44 20 1234 5678',
            'faqs' => [['q' => 'Custom question?', 'a' => 'Custom answer.'], ['q' => '', 'a' => '']],
            'testimonials' => [['name' => 'Sara Khan', 'role' => 'Principal', 'school' => 'Sunrise School', 'quote' => 'Fees take minutes now.']],
            'privacy' => "## Our rules\n\n<script>alert(1)</script>",
        ])->assertSessionHasNoErrors();

        auth('web')->logout();
        $this->get('/')->assertSee('busy principals.')->assertSee('Friendly')->assertSee('Custom question?')
            ->assertDontSee('Do we need to install anything?')->assertSee('Fees take minutes now.')->assertSee('+44 20 1234 5678');
        $this->get('/privacy')->assertSee('Our rules')->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/terms')->assertSee('Plans and payment'); // left at the default
    }

    public function test_maintenance_mode_blocks_visitors_but_not_the_admin(): void
    {
        $this->createSchool();
        $admin = $this->superAdmin();
        Setting::put(['maintenance' => ['on' => true, 'message' => 'Upgrading tonight.']]);

        $this->get('/')->assertStatus(503)->assertSee('Upgrading tonight.');
        $this->forgetCurrentSchool();
        $this->get($this->schoolUrl('greenfield', 'login'))->assertStatus(503);
        $this->get('/admin/login')->assertOk();

        $this->actingAs($admin, 'web');
        $this->get('/')->assertOk();

        $this->post(route('admin.system.maintenance'), ['on' => 0])->assertRedirect();
        auth('web')->logout();
        $this->get('/')->assertOk();
    }

    public function test_database_backup_downloads(): void
    {
        $this->createSchool();
        $this->actingAs($this->superAdmin(), 'web');

        $response = $this->post(route('admin.system.backup'))->assertOk()->assertDownload();
        $sql = gzdecode(file_get_contents($response->baseResponse->getFile()->getPathname()));

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('Greenfield School', $sql);
        $this->assertStringNotContainsString('INSERT INTO "sessions"', $sql);
    }

    public function test_only_super_admin_team_with_system_rights_can_back_up(): void
    {
        $this->actingAs($this->superAdmin(['role' => 'support']), 'web');

        $this->post(route('admin.system.backup'))->assertForbidden();
    }
}
