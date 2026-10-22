#!/usr/bin/env bash
# Skooline deploy/update script — run on the server from the project folder:
#   bash scripts/deploy.sh
# Safe to run on every update (pulls code, installs deps, migrates central + all school DBs).
set -euo pipefail

cd "$(dirname "$0")/.."

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

echo "→ Pulling latest code"
git pull --ff-only

echo "→ Installing PHP dependencies"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ ! -f .env ]; then
    cp .env.example .env
    $PHP artisan key:generate --force
    echo "!! .env created from .env.example — edit it (APP_URL, CENTRAL_DOMAINS, DB_*) and run this script again."
    exit 1
fi

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

echo "→ Migrating central database"
$PHP artisan migrate --force

echo "→ Migrating every school database"
$PHP artisan tenants:migrate --force

echo "→ Caching config and views"
$PHP artisan config:cache
$PHP artisan view:cache

echo "✓ Deploy complete"
