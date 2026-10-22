<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DatabaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Server info and one-click database updates, so new versions can be
 * installed on shared hosting without SSH.
 */
class SystemController extends Controller
{
    public function index(): View
    {
        return view('admin.system', [
            'pending' => DatabaseStatus::pending(),
            'checks' => [
                'Skooline version' => config('skooline.version'),
                'PHP version' => PHP_VERSION,
                'Laravel version' => app()->version(),
                'Database' => DB::connection()->getDriverName().' '.$this->databaseVersion(),
                'Environment' => app()->environment(),
                'Debug mode' => config('app.debug') ? 'On (turn off in production)' : 'Off',
                'Storage writable' => is_writable(storage_path()) ? 'Yes' : 'No — make storage/ writable',
            ],
            'cron' => 'cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1',
        ]);
    }

    public function migrate(): RedirectResponse
    {
        abort_unless(auth('web')->user()->isSuperAdmin(), 403);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            DatabaseStatus::forget();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Update failed: '.$e->getMessage());
        }

        return back()->with('success', 'Database is up to date.');
    }

    protected function databaseVersion(): string
    {
        try {
            return (string) DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            return '';
        }
    }
}
