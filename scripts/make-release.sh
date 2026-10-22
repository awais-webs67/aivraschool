#!/usr/bin/env bash
# Builds an upload-ready ZIP for cPanel / hPanel (no SSH or Composer needed on the server):
#   bash scripts/make-release.sh            → dist/skooline-<version>.zip
# Contains the app, vendor/ (production packages), built assets and database/skooline.sql.
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION=$(grep -oP "'version' => '\K[^']+" config/skooline.php)
BUILD="dist/build/skooline"
ZIP="dist/skooline-${VERSION}.zip"

rm -rf dist/build "$ZIP"
mkdir -p "$BUILD"

echo "→ Exporting committed files"
git archive HEAD | tar -x -C "$BUILD"

echo "→ Installing production PHP packages"
(cd "$BUILD" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --quiet)

echo "→ Removing development-only files"
# Packages installed from source (when dist downloads aren't available) carry .git folders.
find "$BUILD/vendor" -type d -name .git -prune -exec rm -rf {} +
rm -rf "$BUILD"/{tests,.github,node_modules,phpunit.xml,.editorconfig,.gitattributes,package.json,package-lock.json,vite.config.js,.npmrc}
mkdir -p "$BUILD"/storage/{app/private,app/public,framework/{cache/data,sessions,views},logs}

echo "→ Zipping"
(cd dist/build && zip -qr "../skooline-${VERSION}.zip" skooline)
rm -rf dist/build

echo "✓ $ZIP"
