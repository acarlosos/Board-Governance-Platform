#!/usr/bin/env bash
# Smoke MySQL — fase 17.7 (subset crítico pré-GO produção).
# Pré-requisitos: MySQL 8+, base dedicada (ex. bgp_smoke_test), .env.testing.mysql (ver .env.testing.mysql.example).
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env.testing.mysql ]]; then
  echo "Falta .env.testing.mysql — execute: cp .env.testing.mysql.example .env.testing.mysql e configure DB_*." >&2
  exit 1
fi

echo "==> migrate:fresh (smoke DB)"
php artisan migrate:fresh --force --env=testing.mysql

echo "==> phpunit (Auth, Policies, MultiTenant, Dashboard, Api)"
vendor/bin/phpunit -c phpunit.mysql.xml \
  tests/Feature/AuthPermissionsTest.php \
  tests/Feature/Api/V1/AuthApiTest.php \
  tests/Unit/Auth/ \
  tests/Feature/SecurityHardeningTest.php \
  tests/Feature/BoardsTest.php \
  tests/Feature/MeetingsTest.php \
  tests/Feature/AuditLogsTest.php \
  tests/Feature/MultitenancyTest.php \
  tests/Feature/Dashboard \
  tests/Feature/Filament/Dashboard \
  tests/Unit/Dashboard \
  tests/Feature/Observers/Dashboard \
  tests/Feature/Api
