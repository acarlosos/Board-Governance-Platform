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

- `GET /health` — canónico BGP: JSON `status`, `db`, `cache`, `app_env`; **200** se DB + cache OK, **503** caso contrário.
- `GET /up` — **alias** do mesmo `HealthCheckController` (compatibilidade Laravel/Forge e probes antigos). **Decisão (parecer v1):** remover a rota built-in `health: '/up'` do `bootstrap/app.php` para evitar dois comportamentos distintos; ambos os caminhos expõem o mesmo probe (DB+cache) e ficam na lista de excepções de maintenance (`PreventRequestsDuringMaintenance::except`).
- Monitor externo (ex.: UptimeRobot): preferir HTTPS a **`/health`** a cada 1–5 min; `/up` só se o template de infra ainda o exigir.

## Scheduler

Confirmar `php artisan schedule:list` inclui `dashboard:refresh-projections` e outras entradas definidas em `bootstrap/app.php`.

## Backup

**Pendente:** pacote `spatie/laravel-backup` sem release compatível com **Laravel 13** no momento da execução da fase 18 (ver `docs/execution/18-production-deploy.result.md`). Quando disponível: `composer require spatie/laravel-backup`, publicar config, agendar `backup:run` / `backup:clean`.

## Fila

- Driver `database`; workers via Supervisor (nome sugerido `bgp-queue-worker`). Ver runbook.
