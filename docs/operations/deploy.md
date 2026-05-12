# Deploy (MVP)

## Pré-requisitos

- PHP 8.3+, Composer, MySQL, cron de 1 min (`* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`).
- Variáveis: ver `.env.production.example` na raiz do repositório.

## Script

Na raiz do projecto:

```bash
bash scripts/deploy.sh
```

O script corre `composer install --no-dev`, `migrate --force`, caches de config/route/view, `filament:upgrade` e **`php artisan queue:restart`** (PO4 — workers pick up código novo após deploy).

## Healthcheck

- **Endpoint oficial:** `GET /health` — JSON `status`, `db`, `cache`, `app_env`; **200** se DB + cache OK, **503** caso contrário.
- **Decisão (PO5 / PR):** não existe rota `GET /up`; probes e monitorização devem usar apenas `/health` (evita duplicar semântica com o health check mínimo do skeleton Laravel). A rota está em `PreventRequestsDuringMaintenance::except(['/health'])`.
- Monitor externo (ex.: UptimeRobot): HTTPS a **`/health`** a cada 1–5 min.

## Scheduler

Confirmar `php artisan schedule:list` inclui `dashboard:refresh-projections`, `backup:run`, `backup:clean` e outras entradas em `bootstrap/app.php`.

## Backup (18.5 — `mysqldump` + gzip)

- **Sem Spatie:** backup diário via Artisan `backup:run` (pipe `mysqldump` → `gzip`) e retenção com `backup:clean`. Credenciais apenas via ficheiro temporário `--defaults-file` (nunca `-p` na linha de comando).
- Saída: `storage/app/backups/bgp-{APP_ENV}-{Y-m-d-His}.sql.gz` (directório ignorado pelo git excepto `.gitkeep`).
- Agendamento: `backup:run` 03:00, `backup:clean` 03:30 (`bootstrap/app.php`).
- Servidor: requer `mysqldump` e `gzip` no PATH (Ubuntu/Forge: `mysql-client`).
- **Não inclui** ficheiros em `storage/app/private` (documentos) — ver runbook (D51).

## Fila

- Driver `database`; workers via Supervisor (nome sugerido `bgp-queue-worker`). Ver runbook.
