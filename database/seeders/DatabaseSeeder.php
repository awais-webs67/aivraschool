<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the central database: default plans and the first Super Admin.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        $email = env('SUPER_ADMIN_EMAIL', 'admin@skooline.test');

        $password = env('SUPER_ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        // The sample password in .env.example is for localhost only.
        if (app()->isProduction() && $password === 'password123') {
            $this->command?->warn('Skipped Super Admin: set a real SUPER_ADMIN_PASSWORD, or open /admin to create one.');

            return;
        }

        if (! PlatformUser::query()->where('email', $email)->exists()) {

            PlatformUser::create([
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => $email,
                'password' => $password,
                'role' => 'super_admin',
            ]);

            $this->command?->info("Super Admin created: {$email}".(env('SUPER_ADMIN_PASSWORD') ? '' : " / {$password}"));
        }
    }
}
