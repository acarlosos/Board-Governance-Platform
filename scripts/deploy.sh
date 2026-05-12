#!/usr/bin/env bash
# Hook típico Forge / deploy zero-downtime (ajustar paths ao servidor).
set -euo pipefail

cd "$(dirname "$0")/.."

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:upgrade
