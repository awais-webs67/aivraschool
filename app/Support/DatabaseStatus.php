<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migrator;
use Throwable;

/**
 * Whether the database is behind the code (new version uploaded, "Run updates" not pressed yet).
 */
class DatabaseStatus
{
    protected static ?array $pending = null;

    /** @return list<string> migration names not run yet */
    public static function pending(): array
    {
        if (static::$pending !== null) {
            return static::$pending;
        }

        try {
            $migrator = app(Migrator::class);

            if (! $migrator->repositoryExists()) {
                return static::$pending = ['(migrations table missing — import database/skooline.sql first)'];
            }

            $files = $migrator->getMigrationFiles([database_path('migrations')]);

            return static::$pending = array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
        } catch (Throwable) {
            return static::$pending = [];
        }
    }

    public static function isOutdated(): bool
    {
        return static::pending() !== [];
    }

    public static function forget(): void
    {
        static::$pending = null;
    }
}
