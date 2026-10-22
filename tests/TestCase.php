<?php

namespace Tests;

use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\DatabaseStatus;
use App\Support\EnvFile;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Both remember things for the rest of the request; tests share one process.
        DatabaseStatus::forget();
        Setting::flushMemory();

        // Integration settings write to .env: give every test its own copy so the real file is never touched.
        $this->envFile = tempnam(sys_get_temp_dir(), 'skooline-env');
        copy(base_path('.env.example'), $this->envFile);
        EnvFile::usePath($this->envFile);

        $this->withoutVite();
    }

    protected string $envFile;

    protected function tearDown(): void
    {
        EnvFile::usePath(null);
        @unlink($this->envFile);

        parent::tearDown();
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

    /** A school whose plan includes every module. */
    protected function fullSchool(string $slug = 'greenfield'): School
    {
        return $this->createSchool($slug, Plan::factory()->create(['modules' => array_keys(config('skooline.modules')), 'max_students' => null]), adminEmail: "admin@{$slug}.test");
    }

    /** Signs in a portal user of the school with the given role (the school admin by default). */
    protected function actingAsRole(School $school, string $role = 'admin', array $attributes = []): User
    {
        $user = $role === 'admin' && $attributes === []
            ? User::withoutGlobalScopes()->where('school_id', $school->id)->where('role', 'admin')->firstOrFail()
            : $school->run(fn () => User::create($attributes + ['name' => ucfirst($role).' User', 'email' => $role.uniqid().'@'.$school->slug.'.test', 'password' => 'secret-pass-1', 'role' => $role]));

        $this->actingAs($user, 'school');

        return $user;
    }

    /** URL inside a school portal; also resets the current school like a fresh request. */
    protected function portal(string $path = '', string $slug = 'greenfield'): string
    {
        $this->forgetCurrentSchool();

        return $this->schoolUrl($slug, $path);
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
