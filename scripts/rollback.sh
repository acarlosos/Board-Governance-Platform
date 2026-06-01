#!/usr/bin/env bash
# Rollback operacional: após reverter código ou symlink para o release anterior no
# servidor, repõe caches e reinicia workers. Não corre composer nem migrations.
#
# Uso (na raiz do projecto ou com cwd no deploy):
#   bash scripts/rollback.sh
#
# Migrações: só `php artisan migrate:rollback` manual se a release problemática
# tiver introduzido migrações novas e for seguro revertê-las (ver runbook).
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:upgrade
php artisan queue:restart
