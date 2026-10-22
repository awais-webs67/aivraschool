<?php

namespace Tests;

use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Each provisioned school gets its own SQLite file; remove them between tests.
        foreach (glob(database_path('testing_skooline_*')) as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    protected function superAdmin(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'super_admin']);
    }

    protected function createSchool(string $subdomain = 'greenfield', ?Plan $plan = null, array $billing = ['mode' => 'trial'], string $adminEmail = 'admin@greenfield.test'): School
    {
        return app(SchoolProvisioner::class)->create(
            school: ['name' => ucfirst($subdomain).' School', 'subdomain' => $subdomain],
            plan: $plan ?? Plan::factory()->create(),
            admin: ['name' => 'School Admin', 'email' => $adminEmail, 'password' => 'secret-pass-1'],
            billing: $billing,
        );
    }

    protected function schoolUrl(string $subdomain, string $path = ''): string
    {
        return "http://{$subdomain}.localhost/".ltrim($path, '/');
    }
}
