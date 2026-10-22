<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\Stripe;
use App\Support\EnvFile;
use App\Support\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_settings_are_saved_to_env_and_password_kept_when_blank(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $this->get(route('admin.settings.email'))->assertOk();

        $form = ['MAIL_HOST' => 'smtp.gmail.com', 'MAIL_PORT' => 587, 'MAIL_ENCRYPTION' => 'tls', 'MAIL_USERNAME' => 'me@gmail.com',
            'MAIL_PASSWORD' => 'app pass "1"', 'MAIL_FROM_ADDRESS' => 'me@gmail.com', 'MAIL_FROM_NAME' => 'Sky School'];
        $this->put(route('admin.settings.email.update'), $form)->assertSessionHas('success');

        $this->assertSame('smtp', EnvFile::get('MAIL_MAILER'));
        $this->assertSame('app pass "1"', EnvFile::get('MAIL_PASSWORD'));
        $this->assertTrue(MailSettings::configured());

        // Saving again with an empty password keeps the old one.
        $this->put(route('admin.settings.email.update'), ['MAIL_PASSWORD' => ''] + $form);
        $this->assertSame('app pass "1"', EnvFile::get('MAIL_PASSWORD'));

        // The page never prints the password itself.
        $this->get(route('admin.settings.email'))->assertDontSee('app pass')->assertSee(EnvFile::mask('app pass "1"'));
    }

    public function test_test_email_is_sent(): void
    {
        Mail::fake();
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.settings.email.test'), ['to' => 'owner@aivrasol.test'])->assertSessionHas('success');

        Mail::assertSent(TestMail::class, fn ($mail) => $mail->hasTo('owner@aivrasol.test'));
    }

    public function test_smtp_errors_are_explained(): void
    {
        $this->assertStringContainsString('App Password', MailSettings::explain('Expected response code 235 but got 535 Authentication failed'));
        $this->assertStringContainsString('host', MailSettings::explain('php_network_getaddrinfo failed: Name or service not known'));
    }

    public function test_pin_protects_viewing_secrets(): void
    {
        $admin = $this->superAdmin(['password' => 'owner-password-1']);
        $this->actingAs($admin, 'web');
        EnvFile::set(['STRIPE_SECRET' => 'sk_test_abcdefghijklmnop']);

        // No PIN yet.
        $this->postJson(route('admin.settings.reveal'), ['name' => 'STRIPE_SECRET', 'pin' => '1234'])->assertStatus(422);

        $this->put(route('admin.settings.pin'), ['pin' => '4321', 'pin_confirmation' => '4321', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->put(route('admin.settings.pin'), ['pin' => '4321', 'pin_confirmation' => '4321', 'current_password' => 'owner-password-1'])->assertSessionHas('success');

        $this->postJson(route('admin.settings.reveal'), ['name' => 'STRIPE_SECRET', 'pin' => '0000'])->assertStatus(422)->assertJson(['error' => 'Wrong PIN.']);
        $this->postJson(route('admin.settings.reveal'), ['name' => 'STRIPE_SECRET', 'pin' => '4321'])->assertOk()->assertJson(['value' => 'sk_test_abcdefghijklmnop']);

        // Only integration secrets can be revealed, never e.g. the app key or database password.
        $this->postJson(route('admin.settings.reveal'), ['name' => 'APP_KEY', 'pin' => '4321'])->assertNotFound();
        $this->postJson(route('admin.settings.reveal'), ['name' => 'DB_PASSWORD', 'pin' => '4321'])->assertNotFound();
    }

    public function test_stripe_settings_and_connection_test(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->put(route('admin.settings.payments.update'), ['STRIPE_SECRET' => 'not-a-key', 'stripe_currency' => 'USD'])->assertSessionHasErrors('STRIPE_SECRET');
        $this->put(route('admin.settings.payments.update'), ['stripe_currency' => 'USD', 'stripe_enabled' => 1])->assertSessionHasErrors('STRIPE_SECRET');

        $this->put(route('admin.settings.payments.update'), ['STRIPE_KEY' => 'pk_test_1', 'STRIPE_SECRET' => 'sk_test_2', 'stripe_currency' => 'USD', 'stripe_enabled' => 1])
            ->assertSessionHas('success');
        $this->assertSame('sk_test_2', EnvFile::get('STRIPE_SECRET'));
        $this->assertTrue((bool) Setting::get('stripe_enabled'));

        Http::fakeSequence('api.stripe.com/*')
            ->push(['object' => 'balance'])
            ->push(['error' => ['message' => 'Invalid API Key provided']], 401);

        $this->post(route('admin.settings.payments.test'))->assertSessionHas('success', 'Connected to Stripe (test mode).');
        $this->post(route('admin.settings.payments.test'))->assertSessionHas('error');
        $this->assertStringContainsString('secret key is wrong', session('error'));
    }

    public function test_stripe_webhook_signature_is_verified(): void
    {
        EnvFile::set(['STRIPE_WEBHOOK_SECRET' => 'whsec_test']);
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_1']]]);
        $t = time();

        $good = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');
        $this->assertNotNull(Stripe::verifyWebhook($payload, $good));
        $this->assertNull(Stripe::verifyWebhook($payload, 't='.$t.',v1=bad'));
        $this->assertNull(Stripe::verifyWebhook($payload, 't='.($t - 3600).',v1='.hash_hmac('sha256', ($t - 3600).'.'.$payload, 'whsec_test')));

        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.$t.',v1=bad', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $good, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
    }
}
