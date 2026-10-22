<?php

namespace Tests\Feature;

use App\Http\Controllers\RegistrationController;
use App\Mail\NewRegistrationMail;
use App\Mail\RegistrationStatusMail;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\Setting;
use App\Support\EnvFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $trialPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        Setting::put(['currency' => 'PKR', 'company_email' => 'sales@aivrasol.test']);
        $this->trialPlan = Plan::factory()->create(['name' => 'Starter', 'slug' => 'starter', 'cta_mode' => 'trial', 'trial_days' => 14, 'show_on_website' => true, 'is_active' => true]);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'school_name' => 'Alpha Grammar School',
            'slug' => 'alpha',
            'school_email' => 'office@alpha.test',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'currency' => 'PKR',
            'students' => 450,
            'school_photo' => UploadedFile::fake()->image('school.jpg', 400, 300),
            'principal_name' => 'Sara Ahmed',
            'principal_email' => 'Sara@Alpha.test',
            'principal_phone' => '+92 300 1112233',
            'principal_national_id' => '35202-1234567-1',
            'principal_qualification' => 'M.Ed',
            'principal_photo' => UploadedFile::fake()->image('sara.png', 300, 300),
            'password' => 'chosen-pass-1',
            'password_confirmation' => 'chosen-pass-1',
            'plan_id' => $this->trialPlan->id,
            'terms' => 1,
        ];
    }

    public function test_registration_page_and_home_buttons(): void
    {
        $this->get(route('register', ['plan' => 'starter']))->assertOk()->assertSee('Register school')->assertSee('14-day free trial');
        $this->get('/')->assertSee(route('register'), false)->assertSee('Start 14-day free trial');
    }

    public function test_a_school_registers_and_admin_is_notified(): void
    {
        $response = $this->post(route('register.store'), $this->form());

        $registration = SchoolRegistration::firstOrFail();
        $response->assertRedirect();
        $this->assertStringContainsString('/register/status/'.$registration->id, $response->headers->get('Location'));

        $this->assertSame('pending', $registration->status);
        $this->assertSame('trial', $registration->billing_choice);
        $this->assertSame('sara@alpha.test', $registration->principal_email);
        $this->assertTrue(Hash::check('chosen-pass-1', $registration->admin_password));
        Storage::disk('local')->assertExists($registration->principal_photo_path);

        Mail::assertSent(NewRegistrationMail::class, fn ($m) => $m->hasTo('sales@aivrasol.test'));
        Mail::assertSent(RegistrationStatusMail::class, fn ($m) => $m->hasTo('sara@alpha.test') && $m->event === 'received');

        // The status page needs the signed link.
        $this->get('/register/status/'.$registration->id)->assertForbidden();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Registration received');
    }

    public function test_address_must_be_free_and_spam_is_ignored(): void
    {
        $this->createSchool('alpha');

        $this->getJson(route('register.check', ['slug' => 'alpha']))->assertJson(['available' => false]);
        $this->getJson(route('register.check', ['slug' => 'alpha-two']))->assertJson(['available' => true]);
        $this->getJson(route('register.check', ['slug' => 'admin']))->assertJson(['available' => false]);

        $this->post(route('register.store'), $this->form())->assertSessionHasErrors('slug');
        $this->post(route('register.store'), $this->form(['slug' => 'beta', 'website' => 'http://spam.test']))->assertRedirect(route('home'));
        $this->assertSame(0, SchoolRegistration::count());
    }

    public function test_registration_can_be_switched_off(): void
    {
        Setting::put(['registration_enabled' => false]);

        $this->get(route('register'))->assertOk()->assertSee('Registration is closed');
        $this->get('/')->assertDontSee(route('register'), false);
        $this->post(route('register.store'), $this->form())->assertForbidden();
    }

    public function test_admin_approves_and_principal_signs_in_with_chosen_password(): void
    {
        $this->post(route('register.store'), $this->form());
        $registration = SchoolRegistration::firstOrFail();

        $this->actingAs($this->superAdmin(), 'web');
        $this->get(route('admin.registrations.index'))->assertOk()->assertSee('Alpha Grammar School');
        $this->get(route('admin.registrations.show', $registration))->assertOk()->assertSee('35202-1234567-1');
        $this->get(route('admin.registrations.photo', [$registration, 'principal']))->assertOk();

        $this->post(route('admin.registrations.approve', $registration), ['mode' => 'trial', 'trial_days' => 30])->assertRedirect();

        $school = School::where('slug', 'alpha')->firstOrFail();
        $this->assertSame('trial', $school->status->value);
        $this->assertSame('Sara Ahmed', $school->principal_name);
        $this->assertSame('35202-1234567-1', $school->principal_national_id);
        $this->assertSame('approved', $registration->fresh()->status);
        Mail::assertSent(RegistrationStatusMail::class, fn ($m) => $m->event === 'approved');

        auth('web')->logout();
        $this->post($this->schoolUrl('alpha', 'login'), ['email' => 'sara@alpha.test', 'password' => 'chosen-pass-1'])
            ->assertRedirect($this->schoolUrl('alpha', 'dashboard'));
    }

    public function test_admin_rejects_with_reason(): void
    {
        $this->post(route('register.store'), $this->form());
        $registration = SchoolRegistration::firstOrFail();
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.registrations.reject', $registration), ['reason' => 'Duplicate'])->assertRedirect(route('admin.registrations.index'));

        $this->assertSame('rejected', $registration->fresh()->status);
        $this->assertSame(0, School::count());
        Mail::assertSent(RegistrationStatusMail::class, fn ($m) => $m->event === 'rejected');
    }

    public function test_auto_approve_creates_the_school_immediately(): void
    {
        Setting::put(['registration_auto_approve' => true]);

        $this->post(route('register.store'), $this->form());

        $this->assertNotNull(School::where('slug', 'alpha')->first());
        $this->assertSame('approved', SchoolRegistration::first()->status);
        Mail::assertNotSent(NewRegistrationMail::class);
    }

    public function test_paid_plan_goes_to_stripe_and_is_confirmed_on_return(): void
    {
        Setting::put(['registration_auto_approve' => true, 'stripe_enabled' => true, 'stripe_currency' => 'USD']);
        EnvFile::set(['STRIPE_SECRET' => 'sk_test_x']);
        $plan = Plan::factory()->create(['slug' => 'pro', 'billing_model' => 'monthly', 'price' => 8000, 'prices' => ['USD' => 29], 'cta_mode' => 'stripe', 'show_on_website' => true]);

        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/pay/cs_test_1']),
            'api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response(['id' => 'cs_test_1', 'payment_status' => 'paid', 'client_reference_id' => '1']),
        ]);

        $this->post(route('register.store'), $this->form(['plan_id' => $plan->id]))->assertRedirect('https://checkout.stripe.com/pay/cs_test_1');

        $registration = SchoolRegistration::firstOrFail();
        $this->assertSame('pay', $registration->billing_choice);
        $this->assertEquals(29.0, (float) $registration->amount);
        $this->assertSame('USD', $registration->amount_currency);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/checkout/sessions' && $request['line_items[0][price_data][unit_amount]'] === 2900);

        // Not paid yet: no school.
        $this->assertNull(School::where('slug', 'alpha')->first());

        // Back from Stripe with the session id: payment confirmed, school created on a paid subscription.
        $url = RegistrationController::thanksUrl($registration).'&session_id=cs_test_1';
        $this->get($url)->assertOk()->assertSee('Your school is ready');

        $school = School::where('slug', 'alpha')->firstOrFail();
        $this->assertSame('active', $school->status->value);
        $this->assertSame('paid', $school->subscriptions()->first()->payment_status);
    }

    public function test_stripe_webhook_confirms_payment(): void
    {
        Setting::put(['stripe_enabled' => true]);
        EnvFile::set(['STRIPE_SECRET' => 'sk_test_x', 'STRIPE_WEBHOOK_SECRET' => 'whsec_1']);
        $plan = Plan::factory()->create(['slug' => 'pro', 'cta_mode' => 'stripe', 'show_on_website' => true]);
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_9', 'url' => 'https://checkout.stripe.com/x'])]);

        $this->post(route('register.store'), $this->form(['plan_id' => $plan->id]));
        $registration = SchoolRegistration::firstOrFail();

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_9', 'payment_status' => 'paid', 'client_reference_id' => (string) $registration->id]]]);
        $t = time();
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", 'whsec_1')], $payload)->assertOk();

        $this->assertTrue($registration->fresh()->isPaid());
        Mail::assertSent(NewRegistrationMail::class); // manual approval: admin is told it is paid and waiting
    }

    public function test_plan_button_mode_and_usd_price_are_saved(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->put(route('admin.plans.update', $this->trialPlan), [
            'name' => 'Starter', 'slug' => 'starter', 'billing_model' => 'monthly', 'price' => 5000, 'trial_days' => 7,
            'max_branches' => 1, 'storage_gb' => 5, 'modules' => ['students'], 'is_active' => 1, 'show_on_website' => 1,
            'cta_mode' => 'register', 'cta_label' => 'Join now', 'prices' => ['USD' => '19', 'GBP' => ''],
        ])->assertRedirect();

        $plan = $this->trialPlan->fresh();
        $this->assertSame('register', $plan->cta_mode);
        $this->assertEquals(['USD' => 19], $plan->prices);
        $this->assertSame(19.0, $plan->priceIn('USD'));
        $this->assertNull($plan->priceIn('GBP'));

        $this->get('/')->assertSee('Join now')->assertSee('data-price-usd="19"', false);
    }

    public function test_admin_adds_school_with_principal_profile_and_photos(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->post(route('admin.schools.store'), [
            'name' => 'Gamma School', 'slug' => 'gamma', 'plan_id' => $this->trialPlan->id, 'billing_mode' => 'trial', 'trial_days' => 14,
            'admin_name' => 'Ali', 'admin_email' => 'ali@gamma.test',
            'principal_name' => 'Ali Raza', 'principal_national_id' => '12345-6789012-3', 'principal_phone' => '0300',
            'school_photo' => UploadedFile::fake()->image('g.jpg'), 'principal_photo' => UploadedFile::fake()->image('a.jpg'),
        ])->assertRedirect();

        $school = School::where('slug', 'gamma')->firstOrFail();
        $this->assertSame('12345-6789012-3', $school->principal_national_id);
        Storage::disk('local')->assertExists($school->photo_path);
        $this->get(route('admin.schools.photo', [$school, 'principal']))->assertOk();
        $this->get(route('admin.schools.show', $school))->assertSee('Ali Raza');
    }
}
