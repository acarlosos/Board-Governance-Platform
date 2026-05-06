# Dashboard e relatórios operacionais (Fase 14)

## Objetivo

Expor **KPIs agregados** por organização (`tenant_id`) no painel Filament, com cache curto por tenant/global, mais uma **página de relatórios** com agregações simples por período, sem exportação nem BI avançado.

## Componentes principais

| Camada | Ficheiros |
|--------|-----------|
| Períodos | `App\Enums\DashboardMetricsPeriod` (`this_month`, `last_30_days`, `all_time`) |
| Isolamento / global | `App\Services\Reporting\ReportingContext` — `tenant_admin`: sempre tenant explícito; `super_admin` (bypass scope): métricas **globais** (sem filtro por `tenant_id`) |
| KPIs dashboard | `App\Services\Dashboard\DashboardMetricsService` — conta com `withoutGlobalScopes()` + `restrictToTenant()` (ou sem filtro se global); cache Laravel **90s**, chave `dashboard_metrics:v1:{segmento}:{period}` |
| Relatórios | `App\Services\Reports\ReportsService` — `tasksByStatus`, `votesByStatus`, `signaturesByStatus` via `GROUP BY`; `meetingsByMonth` com até 12 contagens por mês |

## KPIs dos widgets

- **Tasks:** total, em aberto, concluídas, em atraso (`due_date` &lt; agora em `pending`/`in_progress`)
- **Reuniões:** total (com filtro por `created_at` conforme período nos widgets usa **mês corrente**), agendadas no **mês calendário** (`scheduled_at`), concluídas (`status`)
- **Atas:** total, em revisão (`in_review`), aprovadas
- **Votações:** total, abertas, encerradas
- **Assinaturas:** total, pendentes (`draft`|`sent`|`failed`), concluídas
- **Notificações:** total, não lidas (`unread`)

## Filament

- Página inicial **`App\Filament\Admin\Pages\Dashboard`** (substitui o `Dashboard` vendor): título/subtítulo via `lang/*/dashboard.php`, grelha responsiva (até 3 colunas de widgets em ecrã grande).
- Widgets (`StatsOverview*` em `App\Filament\Admin\Widgets`) apenas chamam `DashboardMetricsService`; visíveis para quem pode `view_reports`.
- Página **`OperationalReports`** (`/admin/operational-reports`): filtro de período + tabelas a partir de `ReportsService`; acesso só com `view_reports`.

## Regras críticas

- **Consultas:** `withoutGlobalScopes()` apenas em conjunto com **filtro explícito** de tenant via `ReportingContext::restrictToTenant()` (exceto vista global só para utilizadores com bypass de tenancy).
- **Performance:** apenas `COUNT`/`GROUP BY`; sem coleções grandes.
- **Cache:** não obrigatório invalidar nesta fase; TTL fixo baixo mitiga inconsistência temporal.

## Testes relacionados

- `tests/Feature/DashboardTest.php`

## Pendências futuras

- Export CSV/PDF, gráficos, invalidação granular de cache, relatórios adicionais, integrações com BI (Looker Studio).
