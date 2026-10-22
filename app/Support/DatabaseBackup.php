<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Plain SQL dump written by PHP (no mysqldump needed on shared hosting), gzip-compressed.
 * Import it in phpMyAdmin to restore. Sessions and cache are left out.
 */
class DatabaseBackup
{
    protected const SKIP = ['sessions', 'cache', 'cache_locks'];

    protected const ROWS_PER_INSERT = 200;

    /** Writes the dump to $path (.sql.gz). */
    public static function write(string $path): void
    {
        $db = DB::connection();
        $pdo = $db->getPdo();
        $mysql = $db->getDriverName() !== 'sqlite';
        $gz = gzopen($path, 'w6');

        $put = fn (string $sql) => gzwrite($gz, $sql);
        $put('-- Skooline database backup '.now()->toDateTimeString()."\n-- Import into an empty database (phpMyAdmin → Import).\n\n");
        if ($mysql) {
            $put("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        }

        foreach (self::tables() as $table) {
            if (in_array($table, self::SKIP, true)) {
                continue;
            }

            $q = $mysql ? "`{$table}`" : "\"{$table}\"";
            $create = $mysql
                ? (array) DB::selectOne("SHOW CREATE TABLE {$q}")
                : ['sql' => DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])->sql];
            $put("DROP TABLE IF EXISTS {$q};\n".end($create).";\n\n");

            $buffer = [];
            foreach (DB::table($table)->cursor() as $row) {
                $values = array_map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), (array) $row);
                $buffer[] = '('.implode(',', $values).')';

                if (count($buffer) === self::ROWS_PER_INSERT) {
                    $put("INSERT INTO {$q} VALUES\n".implode(",\n", $buffer).";\n");
                    $buffer = [];
                }
            }
            if ($buffer) {
                $put("INSERT INTO {$q} VALUES\n".implode(",\n", $buffer).";\n");
            }
            $put("\n");
        }

        if ($mysql) {
            $put("SET FOREIGN_KEY_CHECKS=1;\n");
        }
        gzclose($gz);
    }

    /** @return list<string> */
    protected static function tables(): array
    {
        return collect(DB::connection()->getSchemaBuilder()->getTables())
            ->pluck('name')
            ->reject(fn ($name) => str_starts_with($name, 'sqlite_'))
            ->values()->all();
    }
}
