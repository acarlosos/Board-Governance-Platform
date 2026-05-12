# Runbook — incidentes comuns (MVP)

| Cenário | Sintoma | Acção |
|---------|---------|--------|
| Deploy falha | exit ≠ 0 no `deploy.sh` | Ler `storage/logs/laravel.log`; `git checkout` release anterior; reexecutar migrate se necessário |
| Queue worker parado | jobs na tabela `jobs` sem consumo | `supervisorctl restart bgp-queue-worker:*` (nome ajustado ao servidor) |
| Backup indisponível | alerta ou ausência de ficheiros | Quando Spatie activo: verificar disco `storage/app/backups` e permissões |
| Healthcheck 503 | UptimeRobot alerta | Verificar DB (`php artisan db:show`) e driver de cache; testar `GET /health` e `GET /up` (mesmo controller); reiniciar PHP-FPM |
| Rollback release | regressão funcional | Reverter symlink/release Forge; `php artisan migrate:rollback` só se migration nova falhou |
