# Runbook — incidentes comuns (MVP)

| Cenário | Sintoma | Acção |
|---------|---------|--------|
| Deploy falha | exit ≠ 0 no `deploy.sh` | Ler `storage/logs/laravel.log`; `git checkout` release anterior; reexecutar migrate se necessário |
| Queue worker parado | jobs na tabela `jobs` sem consumo | `supervisorctl restart bgp-queue-worker:*` (nome ajustado ao servidor) |
| Backup indisponível | alerta ou ausência de ficheiros | Ver `storage/logs/laravel.log` (comandos `backup:*`); `ls -lh storage/app/backups/`; `php artisan backup:run` manual em staging; confirmar `mysqldump` no PATH |
| Healthcheck 503 | UptimeRobot alerta | Verificar DB (`php artisan db:show`) e driver de cache; `curl -sf https://…/health`; reiniciar PHP-FPM |
| Rollback release | regressão funcional | Reverter symlink/release Forge; `php artisan migrate:rollback` só se migration nova falhou |
