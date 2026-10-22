<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\School;
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

        $this->command?->table(['School', 'Login URL', 'Email', 'Password'], [
            ['Greenfield', url('greenfield/login'), 'admin@greenfield.test', 'password'],
            ['Riverside', url('riverside/login'), 'admin@riverside.test', 'password'],
        ]);
    }
}
