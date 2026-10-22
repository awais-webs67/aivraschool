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

        if (! PlatformUser::query()->where('email', $email)->exists()) {
            $password = env('SUPER_ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

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
