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

---

## Executive Dashboard (Fase 19A)

> Estado: **fundação documental (19A.0) em curso**. Sem alterações de runtime, queries, widgets, services, policies ou rotas. Toda a Fase 14 actual (KPIs Filament + `OperationalReports`) **mantém-se intacta** até à fase 19A.7.

### Objectivo

Substituir a grelha actual de 6 widgets `*StatsWidget` independentes por um **dashboard executivo** orientado a decisão: 1 página, ≤ 4 widgets Livewire, **um único snapshot tipado** consumido por todos.

### Anatomia A–E

| Bloco | Conteúdo | Origem |
|---|---|---|
| **A — Hero** | Saudação + 3 highlights críticos (overdue total, votações abertas, assinaturas pendentes) | `HeroProvider` |
| **B — Priorities** | Top-N items accionáveis pelo utilizador (tasks atribuídas, votos a emitir, assinaturas pendentes), **1 item = 1 link**, com policy `view` por item | `PrioritiesProvider` (per-user) |
| **C — KPI Strip** | 4–6 contadores estáveis por tenant (tasks/meetings/votes/signatures), reaproveitando `DashboardMetricsService` | `KpiStripProvider` (delega) |
| **D — Operations** | Estado operacional condensado (atas em revisão, reuniões agendadas, notificações não lidas) — consolida o que era `*StatsSecondary` | `OperationsProvider` |
| **E — Activity feed** | Lista curta (`take(N)`) de actividade recente, **filtrada item-a-item por policy `view`** | `ActivityFeedProvider` (per-user) |

### Dashboard ≠ OperationalReports ≠ BI

| Camada | Pergunta que responde | Implementação |
|---|---|---|
| Executive Dashboard | "O que devo fazer agora?" | `ExecutiveDashboardReadService` (snapshot ≤ 60 s) |
| OperationalReports | "O que aconteceu nos últimos N?" | `ReportsService` (GROUP BY por período, sem cache) |
| BI / Export | "Tendências, comparativos, dashboards externos" | **fora do escopo** — Looker Studio / pendência futura |

Esta separação é **regra arquitectural** (ver [`.cursor/rules/dashboard.mdc`](../../.cursor/rules/dashboard.mdc)): nenhuma das três camadas pode duplicar lógica das outras.

### Snapshot architecture

- **DTO root:** `App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot` (`final readonly`).
- **Sub-DTOs:** `HeroSummary`, `KpiStrip`, `OperationsBlock`, `PriorityItem`, `ActivityItem` — todos `final readonly`.
- **Shape estável e versionado:** qualquer mudança no shape obriga a bump da chave de cache (`v1` → `v2`).
- **Serializável para JSON** desde o início (datetimes em ISO8601, enums como `->value`, sem objects Eloquent expostos) — preparação para futuro `GET /api/v1/dashboard/snapshot` (Fase 19B).

### Provider architecture

| Provider | Cacheado partilhado? | Aplica policy por item? |
|---|---|---|
| `HeroProvider` | ✅ por tenant + período | ❌ (agregados) |
| `KpiStripProvider` | ✅ delega `DashboardMetricsService` (já cacheado) | ❌ (agregados) |
| `OperationsProvider` | ✅ por tenant + período | ❌ (agregados) |
| `PrioritiesProvider` | ❌ **per-user, não cacheado partilhado** | ✅ `Gate::forUser($user)->allows('view', $item)` |
| `ActivityFeedProvider` | ❌ **per-user, não cacheado partilhado** | ✅ `Gate::forUser($user)->allows('view', $item)` |

`ExecutiveDashboardReadService` é **orquestrador puro**: compõe os providers e devolve o DTO. **Não duplica queries** de KPI já feitas por `DashboardMetricsService`.

### Limites de widgets (Filament/Livewire)

- **Máximo 4 widgets Livewire** no dashboard executivo. `StatsSecondary` consolida-se em `OperationsBlock`/`OperationsWidget`.
- Widgets **não consultam Eloquent** — consomem o snapshot do service.
- `Priorities` e `Activity` carregam com **`deferLoading()`** para não bloquear a renderização inicial.
- `canView()` de cada widget e `canAccess()` da page: **gate único** `view_executive_dashboard` (sem `can('view_reports')` inline).

### Estratégia de cache

| Bloco | Chave | TTL | Anti-stampede |
|---|---|---|---|
| Hero / KPI / Operations | `dashboard_snapshot:v1:{cacheSegment}:{period}:shared` | 60 s | `Cache::flexible(stale=60, expire=120, …)` |
| Priorities / Activity | (sem cache partilhado) | — | acessos per-user; rate limit em widget se necessário |

- Prefix versionado **obrigatório**; bump em mudança de shape.
- Segmento de tenancy obrigatório via `ReportingContext::cacheSegment()` (`t_{id}` / `global` / `none`).
- Driver compatível com `flexible` (Redis recomendado; degradação documentada em outros drivers).
- **Sem invalidação por evento** na 19A — latência ≤ 60 s aceitável para "executive feel". Invalidação por observers fica para **Fase 19B**.

Convenção transversal: ver [`.cursor/rules/cache.mdc`](../../.cursor/rules/cache.mdc).

### Regras de tenancy

- Todas as queries: `Model::query()->withoutGlobalScopes()` + `ReportingContext::restrictToTenant($builder)`.
- `super_admin` (bypass): KPI/Hero/Operations agregados globalmente; `Priorities` e `Activity` **desactivados** ou com `LIMIT 5` (evitar varrer milhões de rows em runtime).
- Reforço pelo checklist [`.cursor/rules/tenant-leakage-critical.mdc`](../../.cursor/rules/tenant-leakage-critical.mdc).

### Policy filtering (anti-leak por item)

- Cada item de `Priorities` e `Activity` passa por `Gate::forUser($user)->allows('view', $item)` **antes** de entrar no DTO ou na render.
- Items sem permissão **somem do feed sem mensagem** ("X items omitidos" é proibido — anti-enumeração).

### Comportamento `super_admin`

- KPI/Hero/Operations: agregação global (`isGlobalScope()`).
- `Priorities` / `Activity`: desactivados ou `LIMIT 5` por desempenho.
- Cache key usa segmento `global`.

### Listas limitadas (N)

- `PrioritiesProvider`: `take(10)` máximo (configurável em `config/board.php`).
- `ActivityFeedProvider`: `take(15)` máximo (configurável em `config/board.php`).
- Sem paginação no dashboard — para listas longas o utilizador segue para o Resource correspondente.

### Performance

- Apenas `COUNT(*)` / `SELECT … LIMIT N` / `GROUP BY` no read path; **sem coleções grandes**.
- Índices DB exigidos (verificar/criar em **Fase 19A.2**):
  - `tasks (tenant_id, status, created_at)` e `tasks (tenant_id, status, due_date)`
  - `meetings (tenant_id, scheduled_at)` e `meetings (tenant_id, status, created_at)`
  - `votes (tenant_id, status, created_at)`
  - `signature_requests (tenant_id, status, created_at)`
  - `notification_center (tenant_id, status, created_at)`
- Anti-stampede obrigatório no snapshot partilhado.

### Riscos conhecidos

| Risco | Mitigação 19A | Pendência 19B |
|---|---|---|
| God service (read service que faz tudo) | Providers internos pequenos (5 classes) | — |
| Stampede no cold start (4 widgets em paralelo) | `Cache::flexible` ou `Cache::lock` | Pre-warm por job em horário de pico |
| Leak por item em feeds | Policy `view` por item + sem mensagem de omissão | — |
| Super_admin agregação cara | `LIMIT` agressivo / desactivar feeds | Projection table dedicada |
| Latência de 60 s entre PATCH e snapshot | Documentada e aceite | Invalidação por evento |
| Tenants enterprise (>50 k tasks) | Índices 19A.2 | Projection table refrescada por job |

### Roadmap 19A → 19B

- **19A.0** (esta fase): documentação + 3 rules novas (`dashboard.mdc`, `cache.mdc`, `livewire.mdc`).
- **19A.1**: decisões formais (TTL, stampede, super_admin, per-user vs partilhado, naming).
- **19A.2**: índices DB críticos (migration mínima).
- **19A.3**: DTOs imutáveis (`ExecutiveDashboardSnapshot` + sub-DTOs) com testes de shape.
- **19A.4**: providers internos (5 classes) com testes unitários.
- **19A.5**: `ExecutiveDashboardReadService` orquestrador + cache + anti-stampede.
- **19A.6**: gate `view_executive_dashboard` registado em `AuthServiceProvider`.
- **19A.7**: 4 widgets Livewire + página `Dashboard` actualizada (mantém Fase 14 desactivada como fallback).
- **19A.8**: testes obrigatórios (multi-tenancy + policies per-item + 4 cenários `tests.mdc`).
- **19A.9**: actualizar esta ficha com estado pós-implementação.
- **19B**: invalidação por evento, projection table, endpoint `GET /api/v1/dashboard/snapshot`, remoção dos `*StatsWidget` legacy.
