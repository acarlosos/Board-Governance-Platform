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
| 18.5 Backup `mysqldump` | ✅ substituído Spatie — ver [`18.5-backup-mysqldump.result.md`](./18.5-backup-mysqldump.result.md) |
| 18.3 Supervisor | Apenas documentado em `deploy.md` / runbook (sem ficheiros de servidor no repo). |
| 18.4 Cron | Documentado; validação `schedule:list` em CI/local já coberta por outras fases. |

## 3. Parecer técnico v1.0.0 — PO4, PO5, PO6, PO8

| PO | Entrega |
|----|---------|
| **PO4** | `scripts/deploy.sh` — `php artisan queue:restart` no final (workers recarregam código). |
| **PO5** | Endpoint oficial **só** `GET /health`; rota `GET /up` removida; `PreventRequestsDuringMaintenance::except(['/health'])` (ver PR `feat/18.5-backup-po456`). |
| **PO6** | Comentários `// reason:` em `ReportingContext`, métricas, providers executivos, projection, `RefreshProjectionsCommand`, download, login/auditoria, `CastVoteAction`, Filament (soft deletes), **Actions API v1**, actions de domínio e `DashboardMetricsService` (linhas secundárias). |
| **PO8** | Working tree organizado em **commits separados** por fase (PO4 → PO5 → PO6 → docs/result). |

## 4. Smoke

- `php artisan route:list --path=health` — rota `GET /health` registada.
- **Sem** rota `GET /up` (PO5).
- `php artisan test --filter=HealthCheck` — verde.

## 5. GO produção

**NO-GO completo** da spec 18 até: (1) backup automatizado resolvido ou explicitamente dispensado por decisão; (2) smoke MySQL da fase 17; (3) checklist S1–S10 assinado em staging/produção.
