<?php

namespace Tests\Feature;

use App\Mail\ExpiryReminderMail;
use App\Mail\InvoiceMail;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\SchoolRegistration;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\BillingReminders;
use App\Services\InvoiceCalculator;
use App\Support\EnvFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_is_taken_before_tax(): void
    {
        Setting::put(['tax_rate' => 10, 'tax_name' => 'VAT']);
        Coupon::create(['code' => 'save20', 'type' => 'percent', 'value' => 20, 'is_active' => true]);

        $bill = InvoiceCalculator::calculate(1000, 'SAVE20');

        $this->assertSame(['subtotal' => 1000.0, 'discount' => 200.0, 'coupon_code' => 'SAVE20', 'tax_name' => 'VAT', 'tax_rate' => 10.0, 'tax_amount' => 80.0, 'total' => 880.0], $bill);
    }

    public function test_unusable_codes_are_refused(): void
    {
        $plan = Plan::factory()->create();
        Coupon::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 50, 'is_active' => true, 'expires_at' => now()->subDay()]);
        Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 50, 'is_active' => true, 'max_uses' => 1, 'used_count' => 1]);
        Coupon::create(['code' => 'OTHER', 'type' => 'fixed', 'value' => 50, 'is_active' => true, 'plan_id' => Plan::factory()->create()->id]);

        foreach (['OLD', 'ONCE', 'OTHER', 'NOPE'] as $code) {
            try {
                InvoiceCalculator::calculate(100, $code, $plan);
                $this->fail("{$code} should be refused");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('coupon_code', $e->errors());
            }
        }

        // Not strict: the code is skipped, the price stays
        $this->assertSame(100.0, InvoiceCalculator::calculate(100, 'OLD', $plan, strict: false)['total']);
    }

    public function test_admin_invoice_shows_breakdown_and_counts_the_code(): void
    {
        Setting::put(['tax_rate' => 5, 'tax_name' => 'GST']);
        $coupon = Coupon::create(['code' => 'TEN', 'type' => 'fixed', 'value' => 10, 'is_active' => true]);
        $school = $this->createSchool();
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.schools.subscriptions.store', $school), [
            'plan_id' => $school->plan_id, 'starts_at' => now()->toDateString(), 'amount' => 110, 'coupon_code' => 'ten', 'payment_status' => 'unpaid',
        ])->assertRedirect();

        $sub = Subscription::firstOrFail();
        $this->assertEquals([110, 10, 5, 105], [(float) $sub->subtotal, (float) $sub->discount, (float) $sub->tax_amount, (float) $sub->amount]);
        $this->assertSame(1, $coupon->fresh()->used_count);

        $this->get(route('admin.subscriptions.invoice', $sub))->assertOk()->assertSee('Discount (TEN)')->assertSee('GST (5%)');
    }

    public function test_coupons_can_be_managed(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.coupons.store'), ['code' => 'welcome20', 'type' => 'percent', 'value' => 20, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->post(route('admin.coupons.store'), ['code' => 'WELCOME20', 'type' => 'percent', 'value' => 20])->assertSessionHasErrors('code');
        $this->post(route('admin.coupons.store'), ['code' => 'BIG', 'type' => 'percent', 'value' => 150])->assertSessionHasErrors('value');

        $coupon = Coupon::firstOrFail();
        $this->assertSame('WELCOME20', $coupon->code);
        $this->get(route('admin.coupons.index'))->assertOk()->assertSee('WELCOME20')->assertSee('20% off');

        $this->delete(route('admin.coupons.destroy', $coupon))->assertRedirect();
        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_invoice_is_emailed_with_a_signed_link(): void
    {
        Mail::fake();
        $school = $this->createSchool(billing: ['mode' => 'paid', 'payment_status' => 'unpaid']);
        $school->update(['principal_email' => 'head@greenfield.test']);
        $sub = $school->subscriptions()->first();
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.subscriptions.send', $sub))->assertSessionHas('success');

        Mail::assertSent(InvoiceMail::class, fn ($m) => $m->hasTo('head@greenfield.test') && $m->kind === 'new');
        $this->assertNotNull($sub->fresh()->sent_at);

        auth('web')->logout();
        $this->get($sub->publicUrl())->assertOk()->assertSee($sub->invoice_number);
        $this->get(route('invoice.show', $sub))->assertForbidden();
    }

    public function test_invoice_can_be_paid_online(): void
    {
        Mail::fake();
        Setting::put(['stripe_enabled' => true]);
        EnvFile::set(['STRIPE_SECRET' => 'sk_test_x']);
        $school = $this->createSchool(billing: ['mode' => 'paid', 'payment_status' => 'unpaid']);
        $sub = $school->subscriptions()->first();
        $this->forgetCurrentSchool();

        Http::fakeSequence('api.stripe.com/*')
            ->push(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay'])
            ->push(['id' => 'cs_1', 'payment_status' => 'paid', 'client_reference_id' => 'inv_'.$sub->id]);

        $this->get($sub->publicUrl())->assertSee('by card');
        $this->get(URL::signedRoute('invoice.pay', $sub))->assertRedirect('https://checkout.stripe.com/pay');
        $this->get(URL::signedRoute('invoice.paid', $sub).'&session_id=cs_1')->assertRedirect($sub->publicUrl());

        $this->assertSame('paid', $sub->fresh()->payment_status);
        Mail::assertSent(InvoiceMail::class, fn ($m) => $m->kind === 'receipt');
    }

    public function test_school_renews_online_and_webhook_is_idempotent(): void
    {
        Mail::fake();
        Setting::put(['stripe_enabled' => true]);
        EnvFile::set(['STRIPE_SECRET' => 'sk_test_x', 'STRIPE_WEBHOOK_SECRET' => 'whsec_1']);
        $school = $this->createSchool();
        $school->update(['status' => 'expired', 'trial_ends_at' => now()->subDays(3)]);
        $this->forgetCurrentSchool();

        // Expired: the portal is closed but the renew page opens
        $this->get($this->schoolUrl('greenfield', 'login'))->assertForbidden()->assertSee('Renew subscription');
        $this->forgetCurrentSchool();
        $this->get($this->schoolUrl('greenfield', 'renew'))->assertOk()->assertSee('Continue to secure payment');
        $this->forgetCurrentSchool();

        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_r', 'url' => 'https://checkout.stripe.com/r'])]);
        $this->post($this->schoolUrl('greenfield', 'renew'), ['plan_id' => $school->plan_id])->assertRedirect('https://checkout.stripe.com/r');
        Http::assertSent(fn ($r) => $r['client_reference_id'] === "renew_{$school->id}_{$school->plan_id}" && $r['metadata[total]'] === '5000');
        $this->forgetCurrentSchool();

        $session = ['id' => 'cs_r', 'payment_status' => 'paid', 'client_reference_id' => "renew_{$school->id}_{$school->plan_id}",
            'metadata' => ['subtotal' => '5000', 'discount' => '0', 'coupon_code' => '', 'tax_rate' => '0', 'tax_amount' => '0', 'total' => '5000']];
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);
        $t = time();
        $headers = ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", 'whsec_1')];

        $this->call('POST', route('stripe.webhook'), [], [], [], $headers, $payload)->assertOk();
        $this->call('POST', route('stripe.webhook'), [], [], [], $headers, $payload)->assertOk();

        $school->refresh();
        $this->assertSame('active', $school->status->value);
        $this->assertSame(1, $school->subscriptions()->where('payment_reference', 'cs_r')->count());
        $this->assertSame('paid', $school->subscriptions()->first()->payment_status);
    }

    public function test_school_admin_sees_billing_page(): void
    {
        $this->createSchool(billing: ['mode' => 'paid', 'payment_status' => 'unpaid']);
        $this->forgetCurrentSchool();
        $this->post($this->schoolUrl('greenfield', 'login'), ['email' => 'admin@greenfield.test', 'password' => 'secret-pass-1']);
        $this->forgetCurrentSchool();

        $this->get($this->schoolUrl('greenfield', 'billing'))->assertOk()->assertSee('INV-')->assertSee('Unpaid');
    }

    public function test_reminders_are_sent_once_per_step(): void
    {
        Mail::fake();
        $trial = $this->createSchool('alpha', adminEmail: 'a@alpha.test');
        $trial->update(['principal_email' => 'head@alpha.test', 'trial_ends_at' => now()->addDays(3)->endOfDay()]);

        $unpaid = $this->createSchool('beta', billing: ['mode' => 'paid', 'payment_status' => 'unpaid'], adminEmail: 'b@beta.test');
        $unpaid->update(['email' => 'office@beta.test', 'subscription_ends_at' => now()->addMonths(2)]);
        $unpaid->subscriptions()->update(['created_at' => now()->subDays(4)]);

        $this->assertSame(['expiry' => 1, 'unpaid' => 1], app(BillingReminders::class)->send());
        $this->assertSame(['expiry' => 0, 'unpaid' => 0], app(BillingReminders::class)->send());

        Mail::assertSent(ExpiryReminderMail::class, fn ($m) => $m->hasTo('head@alpha.test') && $m->days === 3 && $m->trial);
        Mail::assertSent(InvoiceMail::class, fn ($m) => $m->hasTo('office@beta.test') && $m->kind === 'reminder');

        // Turned off in settings
        $this->travel(8)->days();
        Setting::put(['reminders_enabled' => false]);
        $this->assertSame(['expiry' => 0, 'unpaid' => 0], app(BillingReminders::class)->send());
    }

    public function test_registration_applies_discount_code(): void
    {
        Setting::put(['tax_rate' => 10]);
        Coupon::create(['code' => 'HALF', 'type' => 'percent', 'value' => 50, 'is_active' => true]);
        $plan = Plan::factory()->create(['cta_mode' => 'register', 'price' => 1000]);

        $form = [
            'school_name' => 'Delta School', 'slug' => 'delta', 'currency' => 'PKR', 'principal_name' => 'P', 'principal_email' => 'p@delta.test',
            'principal_phone' => '123', 'password' => 'pass-word-1', 'password_confirmation' => 'pass-word-1', 'plan_id' => $plan->id, 'terms' => 1,
        ];

        $this->post(route('register.store'), $form + ['coupon_code' => 'WRONG'])->assertSessionHasErrors('coupon_code');
        $this->post(route('register.store'), $form + ['coupon_code' => 'half'])->assertSessionHasNoErrors();

        $r = SchoolRegistration::firstOrFail();
        $this->assertEquals([1000, 500, 50, 550], [(float) $r->subtotal, (float) $r->discount, (float) $r->tax_amount, (float) $r->amount]);
        $this->assertSame('HALF', $r->coupon_code);
    }
}
