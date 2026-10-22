<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sets_billing_and_website_currencies(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->get(route('admin.settings'))->assertOk();

        $this->put(route('admin.settings.update'), [
            'currency' => 'USD',
            'enabled' => ['GBP', 'EUR'],
            'website' => ['GBP' => '1.25', 'EUR' => '', 'AED' => '0.27'], // EUR has no rate, AED not ticked
        ])->assertSessionHas('success');

        $this->assertSame('USD', Setting::currency());
        $this->assertSame(['USD', 'GBP'], array_column(Setting::websiteCurrencies(), 'code'));
        $this->assertEqualsWithDelta(0.8, Setting::websiteCurrencies()[1]['rate'], 0.0001);
        $this->assertSame('USD 1,500', money(1500));
    }

    public function test_settings_reject_unknown_currency(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->put(route('admin.settings.update'), ['currency' => 'XYZ'])->assertSessionHasErrors('currency');
    }

    public function test_school_currency_is_used_inside_its_portal(): void
    {
        Setting::put(['currency' => 'PKR']);
        $school = $this->createSchool('greenfield');
        $school->update(['currency' => 'AED']);

        $this->assertSame('AED 250', $school->run(fn () => money(250)));
        $this->assertSame('PKR 250', money(250));
        $this->assertSame('PKR', $this->createSchool('riverside', adminEmail: 'a@r.test')->currencyCode());
    }

    public function test_home_page_has_seo_tags_and_currency_switcher(): void
    {
        Plan::factory()->create(['name' => 'Starter']);
        Setting::put(['currency' => 'PKR', 'website_currencies' => ['USD' => 280]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('og:image', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"priceCurrency":"PKR"', false)
            ->assertSee('id="currency-data"', false)
            ->assertSee('data-currency="USD"', false)
            ->assertDontSee('noindex', false);
    }

    public function test_private_pages_are_not_indexed(): void
    {
        $this->superAdmin();
        $this->createSchool('greenfield');

        $this->get(route('admin.login'))->assertSee('noindex', false);
        $this->get($this->schoolUrl('greenfield', 'login'))->assertSee('noindex', false);
    }

    public function test_robots_and_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('sitemap.xml'));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('<loc>'.url('/').'</loc>', false);
    }
}
