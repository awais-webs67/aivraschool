<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_matches_rfc_6238_vectors(): void
    {
        // RFC 6238 SHA-1 secret "12345678901234567890" in base32
        $secret = Totp::base32Encode('12345678901234567890');

        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertSame('005924', Totp::code($secret, 1234567890));
        $this->assertTrue(Totp::verify($secret, Totp::code($secret)));
        $this->assertFalse(Totp::verify($secret, '000000x'));
    }

    public function test_super_admin_can_reset_a_forgotten_password(): void
    {
        Mail::fake();
        $admin = $this->superAdmin(['email' => 'owner@aivrasol.com']);

        $this->get('http://localhost/admin/login')->assertSee('Forgot password?');
        $this->post('http://localhost/admin/forgot-password', ['email' => 'owner@aivrasol.com'])->assertSessionHas('success');
        $this->post('http://localhost/admin/forgot-password', ['email' => 'nobody@x.test'])->assertSessionHas('success');

        $url = null;
        Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$url) {
            $url = $mail->url;

            return true;
        });
        Mail::assertSentCount(1);

        $this->get($url)->assertOk()->assertSee('Choose a new password');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->post('http://localhost/admin/reset-password', [
            'token' => $token, 'email' => $query['email'], 'password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9',
        ])->assertRedirect('http://localhost/admin/login');

        $this->assertTrue(Hash::check('brand-new-pass-9', $admin->fresh()->password));
    }

    public function test_two_factor_setup_and_sign_in(): void
    {
        $admin = $this->superAdmin(['email' => 'owner@aivrasol.com', 'password' => 'strong-pass-2026']);

        $this->actingAs($admin)->post('http://localhost/admin/account/two-factor', ['current_password' => 'strong-pass-2026'])->assertRedirect();
        $this->get('http://localhost/admin/account')->assertOk()->assertSee('data-qr="otpauth://totp/', false);

        $secret = session('two_factor_pending') ?? $admin->fresh()->two_factor_secret;
        $this->assertNotEmpty($secret);

        $this->post('http://localhost/admin/account/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('http://localhost/admin/account/two-factor/confirm', ['code' => Totp::code($secret)])->assertSessionHas('recovery_codes');
        $this->assertTrue($admin->fresh()->hasTwoFactor());
        $recovery = $admin->fresh()->two_factor_recovery_codes[0];

        // Password alone is no longer enough
        $this->post('http://localhost/admin/logout');
        $this->post('http://localhost/admin/login', ['email' => 'owner@aivrasol.com', 'password' => 'strong-pass-2026'])
            ->assertRedirect('http://localhost/admin/two-factor');
        $this->assertGuest('web');

        $this->post('http://localhost/admin/two-factor', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->post('http://localhost/admin/two-factor', ['code' => $recovery])->assertRedirect('http://localhost/admin');
        $this->assertAuthenticated('web');

        // A recovery code works only once
        $this->assertNotContains($recovery, $admin->fresh()->two_factor_recovery_codes);
        $this->get('http://localhost/admin/account')->assertOk()->assertSee('Signed in')->assertSee('Signed-in devices');
    }

    public function test_failed_admin_sign_in_is_recorded(): void
    {
        $this->superAdmin(['email' => 'owner@aivrasol.com']);

        $this->post('http://localhost/admin/login', ['email' => 'owner@aivrasol.com', 'password' => 'wrong-password'])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('activity_logs', ['action' => 'login.failed']);
    }

    public function test_school_user_can_reset_a_forgotten_password(): void
    {
        Mail::fake();
        $this->createSchool('greenfield');
        $this->forgetCurrentSchool();

        $this->get($this->schoolUrl('greenfield', 'login'))->assertSee('Forgot password?');
        $this->post($this->schoolUrl('greenfield', 'forgot-password'), ['email' => 'admin@greenfield.test'])->assertSessionHas('success');

        $url = null;
        Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$url) {
            $url = $mail->url;

            return true;
        });
        $this->assertStringStartsWith($this->schoolUrl('greenfield', 'reset-password/'), $url);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->forgetCurrentSchool();

        $this->get($url)->assertOk();
        $this->forgetCurrentSchool();

        $payload = ['email' => 'admin@greenfield.test', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'];
        $this->post($this->schoolUrl('greenfield', 'reset-password'), $payload + ['token' => 'wrong'])->assertSessionHasErrors('email');
        $this->forgetCurrentSchool();
        $this->post($this->schoolUrl('greenfield', 'reset-password'), $payload + ['token' => $token])->assertRedirect($this->schoolUrl('greenfield', 'login'));
        $this->forgetCurrentSchool();

        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'new-pass-123'])
            ->assertRedirect($this->schoolUrl('greenfield', 'dashboard'));
        $this->assertDatabaseCount('school_password_resets', 0);
    }
}
