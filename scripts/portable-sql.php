<?php

// Makes a MariaDB/MySQL dump importable on both MySQL 5.7+/8 and MariaDB (stdin → stdout).

$sql = stream_get_contents(STDIN);

// MariaDB 10.11+ adds a sandbox-mode line that MySQL and older phpMyAdmin reject.
$sql = preg_replace('#^/\*M!999999.*\R#m', '', $sql);

// MariaDB stores JSON as LONGTEXT + CHECK(json_valid); "json" works on both servers.
$sql = preg_replace_callback(
    '#longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ?(NOT NULL|DEFAULT NULL)? ?CHECK \(json_valid\(`\w+`\)\)#',
    fn (array $m) => trim('json '.($m[1] ?? '')),
    $sql
);

echo <<<'SQL'
-- ---------------------------------------------------------------------------
-- Skooline by AivraSol — database for a fresh installation
--
-- 1. Create an empty MySQL/MariaDB database in cPanel / hPanel
--    (collation utf8mb4_unicode_ci).
-- 2. Import this file with phpMyAdmin (Import tab).
-- 3. Put the database name, user and password in the .env file.
-- 4. Open https://YOURDOMAIN/admin and create your Super Admin account.
--
-- Contains all tables plus the default plans. No user accounts are included.
-- Generated from the Laravel migrations; regenerate with: bash scripts/make-sql.sh
-- ---------------------------------------------------------------------------


SQL;

echo rtrim(ltrim($sql, "\n"))."\n";
