<?php

namespace App\Providers;

use App\Support\Tenancy\CurrentSchool;
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
        //
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
