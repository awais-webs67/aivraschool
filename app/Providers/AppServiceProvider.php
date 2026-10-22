<?php

namespace App\Providers;

use App\Models\PlatformUser;
use App\Models\Setting;
use App\Support\MailSettings;
use App\Support\Tenancy\CurrentSchool;
use App\View\Composers\AdminNotifications;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One per request / queued job, so a school never leaks into the next one.
        $this->app->scoped(CurrentSchool::class);

        $this->ensureAppKey();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Platform permissions (see PlatformUser::PERMISSIONS): @can('billing'), ->middleware('can:billing')
        foreach (array_keys(PlatformUser::PERMISSION_LABELS) as $permission) {
            Gate::define($permission, fn ($user) => $user instanceof PlatformUser && ($user->is_active ?? true) && $user->hasPermission($permission));
        }

        // Defaults the Super Admin can change under Settings.
        $overrides = array_filter([
            'skooline.default_trial_days' => Setting::get('trial_days'),
            'skooline.grace_days' => Setting::get('grace_days'),
        ], fn ($value) => $value !== null && $value !== '');

        if ($overrides) {
            config(array_map('intval', $overrides));
        }

        View::composer('components.layouts.admin', AdminNotifications::class);

        // SMTP settings saved from the Super Admin panel.
        MailSettings::apply();
    }

    /**
     * Shared hosting without SSH can't run "php artisan key:generate". On the first
     * web request with an empty APP_KEY, generate one and save it to .env.
     */
    protected function ensureAppKey(): void
    {
        if ($this->app->runningInConsole() || filled(config('app.key'))) {
            return;
        }

        $path = $this->app->environmentFilePath();

        if (! is_file($path) || ! is_writable($path)) {
            return;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $contents = (string) file_get_contents($path);

        $contents = preg_match('/^APP_KEY=.*$/m', $contents)
            ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents)
            : rtrim($contents)."\nAPP_KEY={$key}\n";

        file_put_contents($path, $contents);
        config(['app.key' => $key]);
    }
}
