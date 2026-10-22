#!/usr/bin/env bash
# Regenerates database/skooline.sql (fresh-install SQL for phpMyAdmin import)
# from the migrations. Needs a local MySQL/MariaDB the given user can create
# a scratch database on:
#   DB_USERNAME=root DB_PASSWORD= bash scripts/make-sql.sh
set -euo pipefail
cd "$(dirname "$0")/.."

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
SCRATCH="skooline_sql_build_$$"
MYSQL=$(command -v mariadb || command -v mysql)
DUMP=$(command -v mariadb-dump || command -v mysqldump)
AUTH=(-h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME")
[ -n "$DB_PASSWORD" ] && AUTH+=(-p"$DB_PASSWORD")

"$MYSQL" "${AUTH[@]}" -e "CREATE DATABASE \`$SCRATCH\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
trap '"$MYSQL" "${AUTH[@]}" -e "DROP DATABASE IF EXISTS \`$SCRATCH\`"' EXIT

export DB_CONNECTION=mysql DB_HOST DB_PORT DB_USERNAME DB_PASSWORD DB_DATABASE="$SCRATCH"
php artisan migrate --force --quiet
php artisan db:seed --class=PlanSeeder --force --quiet

"$DUMP" "${AUTH[@]}" --skip-comments --skip-dump-date --no-tablespaces --skip-add-locks \
    --skip-lock-tables --single-transaction --default-character-set=utf8mb4 "$SCRATCH" \
  | php scripts/portable-sql.php > database/skooline.sql

echo "✓ database/skooline.sql updated"
