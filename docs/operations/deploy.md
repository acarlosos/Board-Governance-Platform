# Deploy (MVP)

## Pré-requisitos

- PHP 8.3+, Composer, MySQL, cron de 1 min (`* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`).
- Variáveis: ver `.env.production.example` na raiz do repositório.

## Script

Na raiz do projecto:

```bash
bash scripts/deploy.sh
```

O script corre `composer install --no-dev`, `migrate --force`, caches de config/route/view e `filament:upgrade`.

## Healthcheck

- `GET /health` — JSON `status`, `db`, `cache`, `app_env`; **200** se DB + cache OK, **503** caso contrário.
- Monitor externo (ex.: UptimeRobot): ping HTTPS a `/health` a cada 1–5 min.

## Scheduler

Confirmar `php artisan schedule:list` inclui `dashboard:refresh-projections` e outras entradas definidas em `bootstrap/app.php`.

## Backup

**Pendente:** pacote `spatie/laravel-backup` sem release compatível com **Laravel 13** no momento da execução da fase 18 (ver `docs/execution/18-production-deploy.result.md`). Quando disponível: `composer require spatie/laravel-backup`, publicar config, agendar `backup:run` / `backup:clean`.

## Fila

- Driver `database`; workers via Supervisor (nome sugerido `bgp-queue-worker`). Ver runbook.
