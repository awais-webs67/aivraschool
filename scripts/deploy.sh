#!/usr/bin/env bash
# Skooline update script. Runs on the server, from the project folder:
#   bash scripts/deploy.sh
# Called automatically by the "Deploy" GitHub Action on every push to main.
# Pulls the code, installs packages, applies database changes (data is kept), refreshes caches.
set -euo pipefail

cd "$(dirname "$0")/.."

PHP="${PHP:-php}"
BRANCH="${BRANCH:-main}"

# Hostinger ships Composer 2 as "composer2"; most other hosts as "composer".
if [ -z "${COMPOSER:-}" ]; then
    if command -v composer2 >/dev/null 2>&1; then COMPOSER=composer2; else COMPOSER=composer; fi
fi

if [ ! -f .env ]; then
    echo "!! .env is missing. Copy .env.example to .env, fill in APP_URL and DB_*, then run again."
    exit 1
fi

echo "→ Maintenance mode on"
$PHP artisan down --retry=15 || true
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

echo "→ Pulling $BRANCH"
git fetch --quiet origin "$BRANCH"
git reset --hard --quiet "origin/$BRANCH"

echo "→ Installing PHP packages"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

echo "→ Applying database changes"
$PHP artisan migrate --force

echo "→ Refreshing caches"
$PHP artisan optimize:clear >/dev/null
$PHP artisan config:cache
$PHP artisan view:cache

echo "→ Maintenance mode off"
$PHP artisan up

echo "✓ Deployed $(git rev-parse --short HEAD)"
