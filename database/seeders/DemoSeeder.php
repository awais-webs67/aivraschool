<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\SupportTicket;
use App\Services\SchoolProvisioner;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Example schools for local testing:  php artisan db:seed --class=DemoSeeder
 * Never runs in production.
 */
class DemoSeeder extends Seeder
{
    public function run(SchoolProvisioner $provisioner): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder is for local testing only.');
        }

        $this->call(PlanSeeder::class);

        $schools = [
            [
                'school' => ['name' => 'Greenfield Grammar School', 'slug' => 'greenfield', 'city' => 'Lahore', 'principal_name' => 'Mrs. Sana Iqbal'],
                'plan' => 'standard',
                'admin' => ['name' => 'Sana Iqbal', 'email' => 'admin@greenfield.test', 'password' => 'password'],
                'billing' => ['mode' => 'paid', 'student_count' => 820, 'payment_status' => 'paid'],
            ],
            [
                'school' => ['name' => 'Riverside Public School', 'slug' => 'riverside', 'city' => 'Karachi', 'principal_name' => 'Mr. Imran Shah'],
                'plan' => 'premium',
                'admin' => ['name' => 'Imran Shah', 'email' => 'admin@riverside.test', 'password' => 'password'],
                'billing' => ['mode' => 'trial', 'trial_days' => 14],
            ],
        ];

        foreach ($schools as $demo) {
            if (School::where('slug', $demo['school']['slug'])->exists()) {
                continue;
            }

            $provisioner->create($demo['school'], Plan::where('slug', $demo['plan'])->firstOrFail(), $demo['admin'], $demo['billing']);
        }

        $this->sampleActivity();

        $this->command?->table(['School', 'Login URL', 'Email', 'Password'], [
            ['Greenfield', url('greenfield/login'), 'admin@greenfield.test', 'password'],
            ['Riverside', url('riverside/login'), 'admin@riverside.test', 'password'],
        ]);
    }

    /** Leads, a support ticket, an announcement and paid invoices so every admin page has something to show. */
    protected function sampleActivity(): void
    {
        if (DemoRequest::query()->exists()) {
            return;
        }

        foreach ([
            ['Ayesha Malik', 'Bright Future School', '+92 300 1112233', 'Islamabad', 450, 'new', null],
            ['John Carter', 'Oakwood Academy', '+44 20 7946 0000', 'London', 900, 'contacted', today()],
            ['Fatima Noor', 'Al-Huda Model School', '+971 50 123 4567', 'Dubai', 300, 'demo_done', today()->addDays(3)],
        ] as [$name, $school, $phone, $city, $students, $status, $followUp]) {
            DemoRequest::create(['name' => $name, 'school_name' => $school, 'phone' => $phone, 'city' => $city, 'students' => $students,
                'status' => $status, 'follow_up_on' => $followUp, 'message' => 'We would like to see attendance and fees.']);
        }

        $greenfield = School::where('slug', 'greenfield')->first();

        if ($greenfield) {
            // A year of paid monthly invoices so the revenue charts have data.
            for ($m = 11; $m >= 1; $m--) {
                $date = now()->subMonthsNoOverflow($m)->startOfMonth();
                $greenfield->subscriptions()->create([
                    'plan_name' => 'Standard', 'billing_model' => 'per_student', 'starts_at' => $date, 'ends_at' => $date->copy()->endOfMonth(),
                    'amount' => 30000 + $m * 1500, 'payment_status' => 'paid', 'payment_method' => 'bank_transfer', 'paid_at' => $date->copy()->addDays(5),
                ]);
            }

            $greenfield->run(function () use ($greenfield) {
                $admin = $greenfield->users()->first();
                $ticket = SupportTicket::create(['user_id' => $admin->id, 'subject' => 'How do we add a new section?', 'category' => 'general', 'priority' => 'normal']);
                $ticket->reply('We have two Class 6 groups this year. How do we add section B?', 'school', $admin->id, $admin->name);
            });
        }

        Announcement::create([
            'title' => 'Welcome to Skooline',
            'body' => 'School setup is ready: add your classes, sections and subjects from the School setup menu.',
            'tone' => 'success',
            'audience' => 'all',
        ]);
    }
}
