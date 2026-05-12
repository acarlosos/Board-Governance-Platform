# Resultado — Fase 18 (Deploy produção — MVP parcial)

Relatório de [`18-production-deploy.md`](./18-production-deploy.md). Data: 2026-05-11.

## 1. Entregue nesta execução

| Item | Estado |
|------|--------|
| 18.1 `.env.production.example` | ✅ |
| 18.1 `scripts/deploy.sh` | ✅ |
| 18.6 `/health` (`HealthCheckController`) + teste | ✅ |
| 18.7 `docs/operations/security-checklist.md` | ✅ |
| Runbook + deploy doc | ✅ `docs/operations/runbook.md`, `docs/operations/deploy.md` |

## 2. Pendente / bloqueado

| Item | Motivo |
|------|--------|
| 18.5 `spatie/laravel-backup` | `composer require spatie/laravel-backup` **falhou**: pacotes analisados exigem `illuminate/console` ^10–12; o projecto usa **Laravel 13** (`laravel/framework ^13`). **Aguardar** release compatível ou alternativa aprovada pelo Arquitecto. |
| 18.3 Supervisor | Apenas documentado em `deploy.md` / runbook (sem ficheiros de servidor no repo). |
| 18.4 Cron | Documentado; validação `schedule:list` em CI/local já coberta por outras fases. |

## 3. Smoke

- `php artisan route:list --path=health` — rota `GET /health` registada.
- `php artisan test --filter=HealthCheck` — verde.

## 4. GO produção

**NO-GO completo** da spec 18 até: (1) backup automatizado resolvido ou explicitamente dispensado por decisão; (2) smoke MySQL da fase 17; (3) checklist S1–S10 assinado em staging/produção.
