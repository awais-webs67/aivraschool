<?php

namespace Tests;

use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\School;
use App\Services\SchoolProvisioner;
use App\Support\DatabaseStatus;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseStatus::forget(); // static per process; reset between tests

        $this->withoutVite();
    }

    protected function superAdmin(array $attributes = []): PlatformUser
    {
        return PlatformUser::factory()->create($attributes + ['role' => 'super_admin']);
    }

    protected function createSchool(string $slug = 'greenfield', ?Plan $plan = null, array $billing = ['mode' => 'trial'], string $adminEmail = 'admin@greenfield.test'): School
    {
        return app(SchoolProvisioner::class)->create(
            school: ['name' => ucfirst($slug).' School', 'slug' => $slug],
            plan: $plan ?? Plan::factory()->create(),
            admin: ['name' => 'School Admin', 'email' => $adminEmail, 'password' => 'secret-pass-1'],
            billing: $billing,
        );
    }

    protected function schoolUrl(string $slug, string $path = ''): string
    {
        return "http://localhost/{$slug}".($path !== '' ? '/'.ltrim($path, '/') : '');
    }

    /** Each test request starts with no current school, like a fresh PHP request. */
    protected function forgetCurrentSchool(): void
    {
        app(CurrentSchool::class)->set(null);
    }
}
