<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use App\Support\DatabaseBackup;
use App\Support\DatabaseStatus;
use FilesystemIterator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

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
                'OPcache (speed)' => $this->opcacheEnabled() ? 'On' : 'Off — turn it on in php.ini, pages load several times faster',
                'Storage writable' => is_writable(storage_path()) ? 'Yes' : 'No — make storage/ writable',
            ],
            'cron' => 'cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1',
            'maintenance' => (array) Setting::get('maintenance', []),
            'canZip' => class_exists(ZipArchive::class),
        ]);
    }

    /** Download the whole database as a .sql.gz file. */
    public function backup(): BinaryFileResponse|RedirectResponse
    {
        $name = 'skooline-backup-'.now()->format('Y-m-d-His').'.sql.gz';
        $path = storage_path('app/'.$name);

        try {
            DatabaseBackup::write($path);
        } catch (Throwable $e) {
            report($e);
            @unlink($path);

            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        Activity::log('system.backup', 'Downloaded a database backup');

        return response()->download($path, $name, ['Content-Type' => 'application/gzip'])->deleteFileAfterSend();
    }

    /** Download uploaded files (logos, photos) as a .zip. */
    public function files(): BinaryFileResponse|RedirectResponse
    {
        abort_unless(class_exists(ZipArchive::class), 404);

        $name = 'skooline-files-'.now()->format('Y-m-d-His').'.zip';
        $path = storage_path('app/'.$name);
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (['app/public', 'app/private'] as $dir) {
            $root = storage_path($dir);
            if (! is_dir($root)) {
                continue;
            }
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $zip->addFile($file->getPathname(), $dir.'/'.ltrim(substr($file->getPathname(), strlen($root)), DIRECTORY_SEPARATOR));
            }
        }

        if ($zip->numFiles === 0) {
            $zip->addFromString('README.txt', 'No uploaded files yet.');
        }
        $zip->close();
        Activity::log('system.backup', 'Downloaded uploaded files');

        return response()->download($path, $name)->deleteFileAfterSend();
    }

    public function maintenance(Request $request): RedirectResponse
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:500']]);
        $on = $request->boolean('on');

        Setting::put(['maintenance' => ['on' => $on, 'message' => $data['message'] ?? null]]);
        Activity::log('system.maintenance', 'Maintenance mode '.($on ? 'on' : 'off'));

        return back()->with('success', $on ? 'Maintenance mode is on. Visitors and schools see the "back soon" page; you can still use everything.' : 'Maintenance mode is off. The site is live again.');
    }

    public function migrate(): RedirectResponse
    {
        abort_unless(auth('web')->user()->isSuperAdmin(), 403);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            DatabaseStatus::forget();

            // On the live server, pre-build config and page templates so every request is faster.
            if (app()->isProduction()) {
                Artisan::call('config:cache');
                Artisan::call('view:cache');
            }
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Update failed: '.$e->getMessage());
        }

        return back()->with('success', 'Database is up to date.');
    }

    protected function opcacheEnabled(): bool
    {
        if (! function_exists('opcache_get_status')) {
            return false;
        }

        $status = @opcache_get_status(false);

        // Some hosts hide the status but still run OPcache.
        return $status === false ? (bool) ini_get('opcache.enable') : (bool) ($status['opcache_enabled'] ?? false);
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
