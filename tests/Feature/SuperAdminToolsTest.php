<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_admin_pages_render(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $school = $this->createSchool('greenfield', billing: ['mode' => 'paid']);
        $lead = DemoRequest::create(['name' => 'Ali', 'school_name' => 'Alpha School', 'phone' => '+1 555 0100']);

        foreach ([
            route('admin.dashboard'), route('admin.reports'), route('admin.reports', ['months' => 24]), route('admin.activity'),
            route('admin.team.index'), route('admin.announcements.index'), route('admin.announcements.create'),
            route('admin.tickets.index'), route('admin.demo-requests.index'), route('admin.demo-requests.show', $lead),
            route('admin.subscriptions.index', ['q' => 'green', 'payment_status' => 'unpaid']), route('admin.settings'),
            route('admin.settings.currencies'), route('admin.schools.show', $school), route('admin.schools.create', ['lead' => $lead->id]),
            route('admin.subscriptions.invoice', $school->subscriptions()->first()),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_roles_limit_what_team_members_can_do(): void
    {
        $support = PlatformUser::factory()->create(['role' => 'support']);
        $this->actingAs($support, 'web');
        $school = $this->createSchool('greenfield');

        $this->get(route('admin.schools.show', $school))->assertOk()->assertDontSee('Delete school permanently');
        $this->get(route('admin.tickets.index'))->assertOk();
        $this->get(route('admin.subscriptions.index'))->assertForbidden();
        $this->get(route('admin.team.index'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
        $this->post(route('admin.schools.suspend', $school))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Billing &amp; invoices', false);

        $accountant = PlatformUser::factory()->create(['role' => 'accountant']);
        $this->actingAs($accountant, 'web');
        $this->get(route('admin.subscriptions.index'))->assertOk();
        $this->get(route('admin.demo-requests.index'))->assertForbidden();
    }

    public function test_team_members_are_managed_and_last_owner_is_protected(): void
    {
        $owner = $this->superAdmin();
        $this->actingAs($owner, 'web');

        $this->post(route('admin.team.store'), ['name' => 'Sara', 'email' => 'Sara@AivraSol.test', 'role' => 'support'])
            ->assertSessionHas('credentials');
        $sara = PlatformUser::where('email', 'sara@aivrasol.test')->firstOrFail();
        $this->assertSame('support', $sara->role);

        // The only owner cannot demote or deactivate themself.
        $this->put(route('admin.team.update', $owner), ['name' => $owner->name, 'email' => $owner->email, 'role' => 'manager', 'is_active' => 1])
            ->assertSessionHasErrors('role');

        $this->put(route('admin.team.update', $sara), ['name' => 'Sara K', 'email' => $sara->email, 'role' => 'manager', 'is_active' => 0])
            ->assertRedirect(route('admin.team.index'));
        $this->assertFalse($sara->fresh()->is_active);

        // A deactivated member is signed out on their next request.
        $this->actingAs($sara->fresh(), 'web')->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_login_as_school_and_return(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'web');
        $school = $this->createSchool('greenfield');

        $this->post(route('admin.schools.impersonate', $school))->assertRedirect($school->url('dashboard'));
        $this->assertAuthenticatedAs($school->users()->first(), 'school');

        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertOk()->assertSee('Return to Super Admin');

        $this->post($this->schoolUrl('greenfield', 'impersonation/leave'))->assertRedirect(route('admin.schools.show', $school));
        $this->assertGuest('school');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertTrue(ActivityLog::where('action', 'impersonation.started')->exists());
    }

    public function test_invoices_are_numbered_printed_and_updated(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        Setting::put(['company_name' => 'AivraSol Ltd', 'invoice_note' => 'Bank: XYZ 123']);
        $school = $this->createSchool('greenfield', billing: ['mode' => 'paid']);
        $invoice = $school->subscriptions()->first();

        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{5}$/', $invoice->invoice_number);

        $this->get(route('admin.subscriptions.invoice', $invoice))
            ->assertOk()->assertSee($invoice->invoice_number)->assertSee('AivraSol Ltd')->assertSee('Bank: XYZ 123');

        $this->patch(route('admin.subscriptions.update', $invoice), ['payment_status' => 'paid', 'payment_method' => 'bank_transfer', 'payment_reference' => 'TX9'])
            ->assertSessionHas('success');
        $this->assertSame('paid', $invoice->fresh()->payment_status);
        $this->assertNotNull($invoice->fresh()->paid_at);

        $csv = $this->get(route('admin.subscriptions.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($invoice->invoice_number, $csv);
    }

    public function test_csv_export_neutralises_formulas(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        DemoRequest::create(['name' => '=HYPERLINK("x")', 'school_name' => 'Evil', 'phone' => '+92 300 1234567']);

        $csv = $this->get(route('admin.demo-requests.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('+92 300 1234567', $csv);
    }

    public function test_lead_pipeline_and_conversion(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $lead = DemoRequest::create(['name' => 'Ali Khan', 'school_name' => 'Alpha School', 'phone' => '0300', 'email' => 'ali@alpha.test']);

        $this->patch(route('admin.demo-requests.update', $lead), ['status' => 'contacted', 'notes' => 'Called, wants demo', 'follow_up_on' => today()->toDateString()])
            ->assertSessionHas('success');
        $this->assertTrue($lead->fresh()->followUpDue());

        $this->get(route('admin.schools.create', ['lead' => $lead->id]))->assertSee('value="Alpha School"', false);

        $plan = Plan::factory()->create();
        $this->post(route('admin.schools.store'), [
            'name' => 'Alpha School', 'slug' => 'alpha', 'plan_id' => $plan->id, 'billing_mode' => 'trial', 'trial_days' => 14,
            'admin_name' => 'Ali Khan', 'admin_email' => 'ali@alpha.test', 'lead_id' => $lead->id,
        ])->assertRedirect();

        $this->assertSame('converted', $lead->fresh()->status);
        $this->assertNotNull($lead->fresh()->school_id);
    }

    public function test_announcements_reach_the_right_schools(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $green = $this->createSchool('greenfield');
        $river = $this->createSchool('riverside', adminEmail: 'admin@riverside.test');

        $this->post(route('admin.announcements.store'), ['title' => 'New feature', 'body' => 'Attendance is live', 'tone' => 'success', 'audience' => 'all', 'is_active' => 1])->assertRedirect();
        $this->post(route('admin.announcements.store'), ['title' => 'Renewal due', 'body' => 'Please pay', 'tone' => 'warning', 'audience' => 'school', 'school_id' => $river->id, 'is_active' => 1]);
        Announcement::create(['title' => 'Old news', 'body' => 'x', 'audience' => 'all', 'ends_on' => now()->subDay()]);

        $this->actingAs($green->users()->first(), 'school');
        $this->get($this->schoolUrl('greenfield', 'dashboard'))->assertSee('New feature')->assertDontSee('Renewal due')->assertDontSee('Old news');

        $this->actingAs($river->users()->first(), 'school');
        $this->get($this->schoolUrl('riverside', 'dashboard'))->assertSee('New feature')->assertSee('Renewal due');
    }

    public function test_support_ticket_conversation(): void
    {
        $green = $this->createSchool('greenfield');
        $river = $this->createSchool('riverside', adminEmail: 'admin@riverside.test');

        // School opens a ticket.
        $this->actingAs($green->users()->first(), 'school');
        $this->post($this->schoolUrl('greenfield', 'support'), ['subject' => 'Cannot add class', 'category' => 'technical', 'priority' => 'high', 'body' => 'Error on save'])
            ->assertRedirect();
        $ticket = SupportTicket::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($green->id, $ticket->school_id);
        $this->assertSame('open', $ticket->status);

        // Another school cannot see it.
        $this->actingAs($river->users()->first(), 'school');
        $this->get($this->schoolUrl('riverside', "support/{$ticket->id}"))->assertNotFound();

        // AivraSol replies.
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'web');
        $this->get(route('admin.tickets.index'))->assertSee('Cannot add class');
        $this->post(route('admin.tickets.reply', $ticket->id), ['body' => 'Fixed, please retry'])->assertSessionHas('success');
        $this->assertSame('answered', $ticket->fresh()->status);
        $this->assertSame($admin->id, $ticket->fresh()->assigned_to);

        // School sees the answer and replies; closing stops replies.
        $this->actingAs($green->users()->first(), 'school');
        $this->get($this->schoolUrl('greenfield', "support/{$ticket->id}"))->assertSee('Fixed, please retry');
        $this->post($this->schoolUrl('greenfield', "support/{$ticket->id}/reply"), ['body' => 'Works now, thanks']);
        $this->assertSame('open', $ticket->fresh()->status);

        $ticket->update(['status' => 'closed']);
        $this->post($this->schoolUrl('greenfield', "support/{$ticket->id}/reply"), ['body' => 'again'])->assertForbidden();
    }

    public function test_general_settings_change_defaults(): void
    {
        $this->actingAs($this->superAdmin(), 'web');

        $this->put(route('admin.settings.update'), ['company_name' => 'AivraSol', 'trial_days' => 30, 'grace_days' => 3])->assertSessionHas('success');

        $this->assertSame(30, (int) Setting::get('trial_days'));
        $this->assertSame(3, (int) Setting::get('grace_days'));
    }

    public function test_actions_are_written_to_the_activity_log(): void
    {
        $this->actingAs($this->superAdmin(['name' => 'Owner']), 'web');
        $school = $this->createSchool('greenfield');

        $this->post(route('admin.schools.suspend', $school), ['reason' => 'Unpaid']);

        $log = ActivityLog::where('action', 'school.suspended')->firstOrFail();
        $this->assertSame('Owner', $log->actor_name);
        $this->assertSame($school->id, $log->school_id);
        $this->get(route('admin.activity', ['area' => 'school']))->assertSee('Suspended Greenfield School: Unpaid');
    }

    public function test_notification_bell_counts(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        DemoRequest::create(['name' => 'A', 'school_name' => 'B', 'phone' => '1']);
        $school = $this->createSchool('greenfield', billing: ['mode' => 'paid']);
        Subscription::query()->update(['payment_status' => 'unpaid']);

        $this->get(route('admin.dashboard'))->assertSee('new demo requests')->assertSee('unpaid invoices');
    }
}
