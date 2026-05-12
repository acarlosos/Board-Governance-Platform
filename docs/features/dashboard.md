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
- **Cache:** invalidação por TTL continua válida; **19B.1** adiciona invalidação por observers para L1/L2 do dashboard executivo (ver secção 19B.1).

## Testes relacionados

- `tests/Feature/DashboardTest.php`
- `tests/Unit/Dashboard/Executive/Snapshot/` (19A.3)
- `tests/Unit/Dashboard/Executive/Providers/` (19A.4)
- `tests/Unit/Dashboard/Executive/Cache/ExecutiveDashboardCacheKeysTest.php` (19B.1 — testes 1–4)
- `tests/Feature/Dashboard/Executive/Cache/ExecutiveDashboardCacheInvalidatorTest.php` (19B.1 — testes 5–8)
- `tests/Feature/Dashboard/Executive/Cache/CrossTenantCacheLeakageTest.php` (19B.1 — teste 16)
- `tests/Feature/Dashboard/Executive/ExecutiveDashboardMetricsL1CacheHitTest.php` (19B.1)
- `tests/Feature/Observers/Dashboard/*ObserverCacheTest.php`, `SignatureRequestSignerNoInvalidationTest.php`, `AuditLogNoInvalidationTest.php` (19B.1 — testes 9–15)
- `tests/Unit/Dashboard/Executive/Observability/ExecutiveDashboardObservabilityTest.php` (19B.2)
- `tests/Feature/Dashboard/Executive/Observability/L1L2InstrumentationTest.php`, `InvalidatorInstrumentationTest.php`, `CacheStatsCommandTest.php` (19B.2)
- `tests/Unit/Models/TenantDashboardSnapshotTest.php`, `tests/Feature/Dashboard/Executive/Projection/*.php` (19B.3)
- `tests/Feature/Api/V1/Dashboard/{DashboardSnapshotEndpointTest,DashboardSnapshotCrossTenantTest}.php` (19B.4)
- `tests/Feature/Dashboard/Executive/ExecutiveDashboardReadServiceTest.php` (19A.5 + chaves L1/L2)
- `tests/Unit/Dashboard/Executive/ExecutiveDashboardReadServiceCompositionTest.php` (19A.5)
- `tests/Feature/Filament/Dashboard/ExecutiveDashboardPageTest.php` (19A.7 — gate/flag/canView)
- `tests/Feature/Filament/Dashboard/ExecutiveDashboardSmokeTest.php` (19A.7 — render Page com flag on/off)
- `tests/Feature/Filament/Dashboard/Widgets/Executive{Hero,KpiStrip,Operations,Priorities}WidgetTest.php` (19A.7 — render Livewire + dispatch/On + empty state)

## Pendências futuras

- Export CSV/PDF, gráficos, invalidação granular de cache, relatórios adicionais, integrações com BI (Looker Studio).

---

## Executive Dashboard (Fase 19A)

> Estado: **19A.0–19A.7 concluídas**; **19B.1 concluída** (invalidação L1/L2 por tenant via observers); **19B.2 concluída** (observabilidade leve: counters + `dashboard:cache-stats`); **19B.3 concluída** (projection L3 `tenant_dashboard_snapshots`, flag `BGP_DASHBOARD_USE_PROJECTION`). **19A.5**: `ExecutiveDashboardReadService` (`read()` → `ExecutiveDashboardSnapshot`, L2 `Cache::flexible` só sobre Hero/Operations). A Fase 14 (KPIs legados + `OperationalReports`) **mantém-se** como fallback enquanto `board.dashboard.use_executive_widgets` estiver `false` (remoção dos 6 `*StatsWidget` em **19B.6**).

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
- **Urgência (priorities):** enum `App\Services\Dashboard\Executive\Snapshot\Enums\PriorityUrgency` (`overdue` \| `due_today` \| `due_this_week` \| `normal`).
- **Convenções:** propriedades PHP em **camelCase**; **`toArray()`** expõe **snake_case**; datetimes internamente `CarbonImmutable`; em array **ISO8601**; enums em array como **`->value`**; **sem** URLs nem labels traduzidos; `cacheSegment` opaco (ex.: `t_{id}`, `global`), sem `tenant_id` cru no DTO.
- **`ExecutiveDashboardSnapshot::emptyShape()`:** snapshot com contadores zerados e feeds vazios, **shape completo** para cold start / tenant sem dados.
- Versão lógica do snapshot: `config('board.dashboard.snapshot_version')` (alinhar com bump `dashboard_snapshot:v{n}` em cache).
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

`ExecutiveDashboardReadService` é **orquestrador puro** (19A.5): compõe providers e devolve o DTO. O mapeamento do **array KPI** já calculado só entra pela **`KpiStripProvider`** (delegação a `DashboardMetricsService`); Hero/Operations têm perguntas de agregação distintas ligadas aos respectivos sub-DTOs.

### Providers internos — Fase 19A.4

Classes em **`App\Services\Dashboard\Executive\Providers`**; cada uma expõe **um método público** `build(User $actor, DashboardMetricsPeriod $period): T`. Sem helpers globais **`auth()` / `request()` / `session()`** dentro dos providers. **Sem cache** dentro dos providers (19A.5).

| Provider | Produto (`T`) | Per-tenant vs per-user | Cache partilhado (19A.5)? |
|---|---|---|---|
| `HeroProvider` | `HeroSummary` | Per-tenant (+ global `super_admin`) | ✅ banda snapshot partilhada |
| `KpiStripProvider` | `KpiStrip` | Per-tenant (delega métricas) | ✅ via `DashboardMetricsService` existente |
| `OperationsProvider` | `OperationsBlock` | Per-tenant (+ global) | ✅ banda snapshot partilhada |
| `PrioritiesProvider` | `array<int, PriorityItem>` | **Per-user** (D9) | ❌ (D3) |
| `ActivityFeedProvider` | `array<int, ActivityItem>` | **Per-user** (entrada **`AuditLogPolicy::viewAny`** + D9 sobre auditable) | ❌ (D3) |

- **`HeroProvider`**: contagens de destaque (tasks em atraso, votos abertos, pedidos de assinatura pendentes, próxima reunião futura): `withoutGlobalScopes` + **`ReportingContext::restrictToTenant`**, ou agregação global se `super_admin`. **Sem** filtro por item.
- **`KpiStripProvider`**: **único** consumidor autorizado de `DashboardMetricsService` nesta pasta; apenas mapeia o retorno (`tasks`, `meetings`, `votes`, `signatures`) para `KpiStrip`, sem queries adicionais.
- **`OperationsProvider`**: atas em revisão, reuniões com `scheduled_at` no **mês calendário** corrente, notificações `unread` — alinhado ao contrato **`OperationsBlock`**, período aplicado aos blocos cronológicos que usam `created_at`.
- **`PrioritiesProvider`**: **D4** — se `ReportingContext::isGlobalScope()`, retorna `[]`. Sobrefetch **`priorities_max + ceil(priorities_max * 0.5)`** (ex.: **15** com max 10). Fontes: `Task`, `SignatureRequestSigner` (`status` pending), `Vote` (`Open`) com filtros owners/managers conforme políticas efectivas; ordenação por urgência + data; **`Gate::forUser($actor)->allows('view', $item)`** antes do DTO; saída **cortada a `priorities_max`**. Anti-enumeração: não expor totais descartados.
- **`ActivityFeedProvider`**: **D4** — global ⇒ `[]`. Se **`!Gate::forUser($actor)->allows('viewAny', AuditLog::class)`** ⇒ `[]` (mantém só alinhamento a `AuditLogPolicy`, não alarga permissões). Fetch **`activity_max + buffer`**; cada log pode custar até **uma** consulta ao auditable; órfão ⇒ `ActivityItem` com `resourceId = null`; após política **`view`** sobre o modelo resolvido, cortar a **`activity_max`**.

Ver testes: `tests/Unit/Dashboard/Executive/Providers/*.php`.

### Read service — Fase 19A.5

Implementação: **`App\Services\Dashboard\Executive\ExecutiveDashboardReadService`** — método público único **`read(User $actor, DashboardMetricsPeriod $period = DashboardMetricsPeriod::ThisMonth): ExecutiveDashboardSnapshot`**.

Fluxo textual (determinístico):

1. `ReportingContext::fromUser($actor)` (`$ctx`).
2. **`$kpis = KpiStripProvider::build`** — sempre **directo**, **sem** estar dentro do `Cache::flexible` L2 (evita segundo cache sobre o já cacheado no `DashboardMetricsService`, L1 `dashboard_metrics:v1:…`).
3. **`$shared = Cache::flexible(sharedKey, [stale, expire], …)`** — apenas **Hero** + **Operations** + marca `shared_generated_at` **`CarbonImmutable::now()`** no momento da computação persistida pelo `flexible` (métricas de “quando foram calculadas” ficam apenas no payload persistido pelo driver; no DTO-root do snapshot público **`generated_at`** continua sempre `CarbonImmutable::now()` ao fim do `read`, como contrato já definido nos DTOs).
4. **`PrioritiesProvider::build`** e **`ActivityFeedProvider::build`** — **sempre frescos**, por pedido (**D3**).
5. `new ExecutiveDashboardSnapshot(...)` ligando **`version`** a `config('board.dashboard.snapshot_version')`, **`cache_segment`** = `$ctx->cacheSegment()`, **`kpis`** = passo (2).

**Casos especiais**

- **`$ctx->cacheSegment() === 'none'`**: sem `flexible`; Hero e Operations são calculados de imediato (evita escritas L2 quando o segmento é “none”; R4 blueprint).
- **`super_admin` global**: feeds per-user ficam **`[]`** (D4) via providers existentes.

**Diagrama ASCII (deps de cache)**

```text
                    read(actor, period)
                            |
           +----------------+----------------+
           |                                 |
      KpiStripProvider                   loadOrComputeShared
     (delega KPI L1                       (Cache::flexible L2 só
      ~90 s próprio)                      Hero + Operations)
           |                                 |
           +----------------+----------------+
                            |
                   Prioridades + Activity (sempre fresco)
                            |
                  ExecutiveDashboardSnapshot
```

**Tabela de chaves (operacional)**

| Camada | Padrão de chave | O que guarda |
|--------|-----------------|--------------|
| L1 KPI | `dashboard_metrics:v1:{segmento}:{period}` | resultado de {@see DashboardMetricsService} (90 s) |
| L2 snapshot shared | `dashboard_snapshot:{snapshot_version}:{segmento}:{period}:shared:plain` | array serializado `{ hero, operations }` (apenas escalares / sub-arrays — **sem** objectos PHP DTO) via `flexible` (stale/expire de `config board.dashboard`); reidratação com `HeroSummary::fromArray` / `OperationsBlock::fromArray` |
| L3 projection | tabela `tenant_dashboard_snapshots` (`tenant_id`, `period`, `payload` JSON, `is_stale`, `refreshed_at`) | Pré-cálculo Hero+Operations (D19); leitura opcional quando `board.dashboard.use_projection` é `true` e `cacheSegment` é `t_{id}` (D22); válido se `is_stale=false`, `refreshed_at >= now()-10min` e `payload.version` coincide com `board.dashboard.snapshot_version` (D24); refresh via `RefreshTenantDashboardSnapshotJob` + `dashboard:refresh-projections` (schedule 5 min em `bootstrap/app.php`); `ExecutiveDashboardCacheInvalidator` chama `markStale` por tenant (D20) |
| *(nunca na L2)* | — | KPI strip, feeds Priorities / Activity |

### Limites de widgets (Filament/Livewire)

- **Máximo 4 widgets Livewire** no dashboard executivo. `StatsSecondary` consolida-se em `OperationsBlock`/`OperationsWidget`.
- Widgets **não consultam Eloquent** — consomem o snapshot do service.
- `Operations` e `ExecutivePrioritiesWidget` (Priorities + Activity) carregam com **`deferLoading()`** (`$isLazy = true`) para não bloquear a renderização inicial.
- `canView()` de cada widget executivo: **gate** `view_executive_dashboard` **e** `config('board.dashboard.use_executive_widgets')` (sem `can('view_reports')` inline nos executivos). `Dashboard::canAccess()` exige apenas **autenticação** (shell da página; dados executivos ficam atrás do gate nos widgets).

### UI — Fase 19A.7

#### UI implementada (19A.7)

| # | Sort | Classe | Lazy | Conteúdo da view (Blade `executive/*`) |
|---|---|---|---|---|
| A | `10` | `App\Filament\Admin\Widgets\Executive\ExecutiveHeroWidget` | não | tagline, **selector `period`** (owner do dispatch D5), updated_at, tasksOverdue/votesOpen/signaturesPending, nextMeetingAt |
| B | `11` | `…\Executive\ExecutiveKpiStripWidget` | não | 4 grupos: tasks / meetings / votes / signatures |
| C | `12` | `…\Executive\ExecutiveOperationsWidget` | **sim** | minutesPendingReview, meetingsThisMonth, notificationsUnread + CTA `OperationalReports` |
| D | `13` | `…\Executive\ExecutivePrioritiesWidget` | **sim** | secção 1 = Priorities (com `urgency` modifier); secção 2 = Activity recente |

| Widget (classe) | DTOs / blocos do snapshot consumidos |
|---|---|
| `ExecutiveHeroWidget` | `ExecutiveDashboardSnapshot` → `hero`, `generatedAt` (label **«Atualizado às HH:MM»** com `config('app.timezone')`) |
| `ExecutiveKpiStripWidget` | → `kpiStrip` |
| `ExecutiveOperationsWidget` | → `operations` + CTA única para `OperationalReports` (não duplicar conteúdo dessa página) |
| `ExecutivePrioritiesWidget` | → `priorities[]`, `activity[]` (duas secções verticais no mesmo widget) |

- **`cacheSegment`:** presente só no DTO para chaves de cache internas — **proibido** renderizar em Blade ou `json_encode`/`toArray()` nas views executivas (evita fuga de telemetria e padrões `t_*` / `global` na UI).
- **Estilos**: `public/css/app/bgp-dashboard.css` (registado como asset `bgp-dashboard` no `AdminPanelProvider`). Convenção BEM `bgp-dashboard__*`. Suporte a tema claro/escuro.
- **i18n**: `lang/{pt_BR,en,es}/dashboard.php`, key root `dashboard.executive.*`. Bloco legacy `dashboard.widgets.*` permanece enquanto a flag puder estar `false`.
- **Feature flag — coexistência com Fase 14**: `config('board.dashboard.use_executive_widgets')` (env `BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS`, default `false`).
  - `false` → 6 `*StatsWidget` (Fase 14) visíveis, 4 executivos ocultos, `Dashboard::canAccess()` apenas exige autenticação.
  - `true` → 4 executivos visíveis **só** com gate `view_executive_dashboard` (`Executive*Widget::canView()`), 6 legacy ocultos por `canView()`; `Dashboard::canAccess()` apenas autenticação (utilizador sem gate vê página vazia, não 403).
  - Activação de produção via env; remoção da flag + dos 6 legacy widgets em **19B.6** (D10).

### Estratégia de cache

| Bloco | Chave | TTL | Anti-stampede |
|---|---|---|---|
| KPI strip (`DashboardMetricsService`) | `dashboard_metrics:v1:{cacheSegment}:{period}` | ~90 s | interno ao serviço (`remember`), **fora** do L2 `:shared` |
| Hero / Operations (**só**) | `dashboard_snapshot:{snapshot_version}:{cacheSegment}:{period}:shared:plain` | `flexible` 60 / 120 s (config `board.dashboard`) | `Cache::flexible(stale=60, expire=120, …)` — valor = arrays compatíveis com `toArray()` dos DTOs |
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

- `PrioritiesProvider`: máximo `config('board.dashboard.priorities_max')` (default **10**).
- `ActivityFeedProvider`: máximo `config('board.dashboard.activity_max')` (default **15**).
- Sem paginação no dashboard — para listas longas o utilizador segue para o Resource correspondente.

### Performance

- Apenas `COUNT(*)` / `SELECT … LIMIT N` / `GROUP BY` no read path; **sem coleções grandes**.
- Fase 19A.2: criado índice tasks_tenant_status_due_date_idx para a query overdue (status IN(pending, in_progress) AND due_date < now()).
- Outros índices candidatos para o dashboard (meetings, votes, assinaturas, `notifications_center`, etc.) **não** foram criados na 19A.2 (over-indexing evitado nesta fase). Notas de nomenclatura para migrações futuras: votos **`ends_at`** (não `closes_at`); signatários em **`signature_request_signers.user_id`**; tabela **`notifications_center`**.
- Índices ainda avaliados em fases posteriores (quando aplicável): `tasks (tenant_id, status, created_at)`; índices em `meetings`, `votes`, `signature_requests` por `tenant_id`/datas conforme KPIs.
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

### Formal Decisions — Executive Dashboard (19A.1)

> **Contrato arquitectural** das decisões abertas até 19A.0. Estas decisões são vinculativas para 19A.3 (DTOs) → 19A.7 (widgets) e para 19B. Qualquer desvio exige aprovação arquitectural explícita e bump de versão da chave de cache.

| ID | Decisão | Valor formal | Rationale curto |
|---|---|---|---|
| **D1** | TTL do snapshot partilhado | **60 s lógico**, com `Cache::flexible(stale=60, expire=120, …)` | "Real-time feel" para executivo; `stale` evita stampede sem perda visual |
| **D2** | Anti-stampede | **Obrigatório** em snapshots executivos. Padrão: `Cache::flexible`. `Cache::lock` apenas se `flexible` for insuficiente (ex.: cálculo > 30 s) | 4 widgets em paralelo num cold start amplificam o problema |
| **D3** | Estratégia de cache | Hero/KPI/Operations: **partilhado por tenant** + período. Priorities/Activity: **per-user, NÃO partilhado** | Dados filtrados por policy do utilizador não podem ser cacheados partilhados (vazamento) |
| **D4** | Super_admin global | KPIs globais permitidos. Priorities/Activity: **desactivados** no modo global (`isGlobalScope()`) | Agregação cross-tenant em runtime escala mal; feeds com policy per-item não fazem sentido global |
| **D5** | Período do dashboard | **Estado único por página** (`Dashboard::$period`); comunicação para os 4 widgets via `dispatch('dashboard:period-changed', period: …)` + `#[On('dashboard:period-changed')]` | Evita dessincronização entre widgets; alinhado com `livewire.mdc` |
| **D6** | Limite Livewire | **Máximo 4 widgets** executivos: Hero, KPI Strip, Operations, Priorities. Activity entra como sub-bloco de Operations OU consolidado em Priorities | 5+ widgets aumenta latência percebida no cold start sem ganho informativo |
| **D7** | `deferLoading()` | **Obrigatório** para Priorities, Activity e qualquer bloco secundário. Hero e KPI Strip carregam imediatamente | Evita cascata de spinners e bloqueio de render principal |
| **D8** | Snapshot contract | DTO `final readonly`; shape estável; **versionado** na chave de cache (`v1` → `v2` ao mudar shape); **API-ready** (datetimes ISO8601, enums como `->value`, sem objects Eloquent) | Permite reuso pelo endpoint `GET /api/v1/dashboard/snapshot` (Fase 19B) sem reescrita |
| **D9** | Policy filtering | **Obrigatório item-a-item** em Priorities/Activity via `Gate::forUser($user)->allows('view', $item)`. Items omitidos **somem sem mensagem** ("X items ocultos" é proibido — anti-enumeração) | Evita vazamento entre utilizadores do mesmo tenant e enumeração lateral |
| **D10** | Legacy widgets | `*StatsWidget` (Fase 14) **mantidos como fallback** durante toda a 19A. Remoção apenas em **19B.6**, depois de validação em produção | Permite rollback rápido se Hero/KPI Strip não convencer; mitiga risco de UX regression |

#### Trade-offs explícitos

- **D1 (TTL 60 s)**: mudanças num PATCH só refletem ≤ 120 s depois (com `flexible`). Aceitável para "executivo"; não aceitável para fluxos transaccionais (ver "Pendência futura — invalidação por evento").
- **D3 (per-user sem cache)**: Priorities/Activity escalam linearmente com o nº de utilizadores activos no mesmo tenant. Mitigação: `take(N)` agressivo (D9 + secção "Listas limitadas") + `deferLoading()` (D7).
- **D4 (super_admin sem feeds)**: super_admin perde informação accionável global. Aceitável: super_admin é operacional, não decisor de negócio do tenant. Reavaliar em 19B se houver pedido formal.
- **D6 (4 widgets)**: força consolidar Activity em Operations/Priorities. Trade-off: menos cards visuais, mais informação por bloco — alinha com a direcção UX "menos widgets independentes".
- **D8 (DTO versionado)**: bump de versão obriga warm de cache pós-deploy (chaves antigas ficam órfãs até TTL expirar). Procedimento documentado em `cache.mdc`.

#### Pré-condições para iniciar 19A.2 (índices DB) e 19A.3 (DTOs)

- [x] D1–D10 registadas em `docs/features/dashboard.md` (esta secção)
- [x] Tabela consolidada + rationale em `docs/architecture.md`
- [x] `dashboard.mdc`, `cache.mdc`, `livewire.mdc` referenciam D1–D10 explicitamente
- [ ] Aprovação do utilizador (gate humano antes de qualquer alteração de runtime)

### Roadmap 19A → 19B

- **19A.0** (concluída): documentação + 3 rules novas (`dashboard.mdc`, `cache.mdc`, `livewire.mdc`).
- **19A.1** (esta fase): decisões formais D1–D10 (acima); rules expandidas com referências contractuais.
- **19A.2**: índices DB críticos (migration mínima).
- **19A.3** (concluída): DTOs imutáveis (`ExecutiveDashboardSnapshot` + sub-DTOs + `PriorityUrgency`) + `config/board.php` (`dashboard.*`) + testes unitários de shape e serialização (`tests/Unit/Dashboard/Executive/Snapshot`).
- **19A.4** (concluída): providers internos (5 classes em `App\Services\Dashboard\Executive\Providers`) + testes em `tests/Unit/Dashboard/Executive/Providers/`.
- **19A.5** (concluída): `ExecutiveDashboardReadService` em `ExecutiveDashboardReadService.php` + L2 `Cache::flexible` (Hero/Operations); KPI fora do L2 — testes em `ExecutiveDashboardReadServiceTest` e composition.
- **19A.6** (concluída): gate `view_executive_dashboard` registado em `app/Providers/AuthServiceProvider.php` (wrapper sobre `view_reports` + `super_admin`).
- **19A.7** (concluída): 4 widgets Livewire em `App\Filament\Admin\Widgets\Executive` + views Blade `bgp-dashboard__*` + asset CSS `public/css/app/bgp-dashboard.css` + i18n `dashboard.executive.*` (pt_BR/en/es) + gate `view_executive_dashboard` nos `Executive*Widget::canView()`; `Dashboard::canAccess()` = autenticado. Coexistência regida pela feature flag `board.dashboard.use_executive_widgets` (default `false`): `false` mantém os 6 `*StatsWidget` legacy visíveis, `true` activa o conjunto executivo e oculta os legacy. Testes em `tests/Feature/Filament/Dashboard/` (page + 4 widgets + smoke).
- **19A.8** (em curso): validação, hardening e rampa controlada (sem features novas). Pré-trabalho técnico e findings nesta ficha (secção "QA 19A.8 — findings"). Decisão arquitectural §12.C fechada e codificada no gate.
- **19A.9**: actualizar esta ficha com estado pós-implementação.
- **19B.1** (concluída): invalidação L1/L2 por `ExecutiveDashboardCacheInvalidator` + observers (`Task`, `Meeting`, `Vote`, `Minute`, `SignatureRequest`, `NotificationCenter`); chaves centralizadas em `ExecutiveDashboardCacheKeys`; D11 (sem flush `global` por evento de tenant); D12 (`KPI_FIELDS` + `updated` selectivo). Ver secção **19B.1 — Invalidação** abaixo.
- **19B.2** (concluída): observabilidade leve — `ExecutiveDashboardObservability` (counters diários agregados L1 hit/miss, L2 hit/miss, invalidações), instrumentação em `DashboardMetricsService::getMetrics`, `ExecutiveDashboardReadService::loadOrComputeShared` (skip `none`) e `ExecutiveDashboardCacheInvalidator::invalidateForTenant`; comando `php artisan dashboard:cache-stats [--day=][--json]`. Ver secção **19B.2 — Observabilidade** abaixo e `docs/execution/19B.2-dashboard-observability.md`.
- **19B.3** (concluída): projection table `tenant_dashboard_snapshots` + `DashboardProjectionService` + job `RefreshTenantDashboardSnapshotJob` + comando `dashboard:refresh-projections`; leitura condicionada a `BGP_DASHBOARD_USE_PROJECTION`; invalidador marca `is_stale`. Ver secção **19B.3 — Projection** abaixo e `docs/execution/19B.3-projection-table.md`.
- **19B.4** (concluída): `GET /api/v1/dashboard/snapshot` — snapshot executivo JSON (`ExecutiveDashboardSnapshot`); ability `reports:read` + gate `view_executive_dashboard`; ver `docs/features/api.md` (secção Dashboard) e `docs/execution/19B.4-api-dashboard-snapshot.md`.
- **19B.5**: pre-warm de cache por job em horário de pico
- **19B.6**: remoção dos `*StatsWidget` legacy (após validação em produção do dashboard executivo)

### 19B.1 — Invalidação inteligente de cache (D11, D12)

#### Decisões

| ID | Regra |
|----|--------|
| **D11** | Não invalidar o segmento `global` por eventos de mutação de um tenant. O cache `cacheSegment === 'global'` expira apenas por TTL natural (evita invalidar agregados de plataforma em cada PATCH de qualquer organização). |
| **D12** | `created` / `deleted` / `restored` invalidam sempre L1+L2 do tenant (`invalidateForTenant`). Em `updated`, invalidar só se `array_intersect_key($model->getChanges(), array_flip(KPI_FIELDS))` for não vazio. |

#### Chaves (formato exacto)

| Camada | Função | Exemplo (`tenant_id=5`, `this_month`, `snapshot_version=v1`) |
|--------|--------|----------------------------------------------------------------|
| L1 | `ExecutiveDashboardCacheKeys::l1Key('t_5', ThisMonth)` | `dashboard_metrics:v1:t_5:this_month` |
| L2 | `ExecutiveDashboardCacheKeys::l2Key('t_5', ThisMonth)` | `dashboard_snapshot:v1:t_5:this_month:shared:plain` |
| L2 meta (`Cache::flexible`) | prefixo `ExecutiveDashboardCacheInvalidator::FLEXIBLE_CREATED_PREFIX` + chave L2 | `illuminate:cache:flexible:created:dashboard_snapshot:v1:t_5:this_month:shared:plain` |

`invalidateForTenant` percorre os **3** períodos de `DashboardMetricsPeriod::filterOptions()` e faz `forget` em L1, L2 e meta `flexible:created` para cada L2; em seguida marca as projections L3 do tenant como stale (`DashboardProjectionService::markStale`, 19B.3).

#### Mapa evento → observer (§3)

| Observer | Modelo | `KPI_FIELDS` (subset `updated`) | Invalidação |
|----------|--------|--------------------------------|---------------|
| `TaskObserver` | `Task` | `status`, `due_date`, `completed_at`, `deleted_at`, `tenant_id` | `created`, `updated` (se KPI), `deleted`, `restored` |
| `MeetingObserver` | `Meeting` | `status`, `scheduled_at`, `deleted_at`, `tenant_id` | idem |
| `VoteObserver` | `Vote` | `status`, `deleted_at`, `tenant_id` | idem |
| `MinuteObserver` | `Minute` | `status`, `deleted_at`, `tenant_id` | idem |
| `SignatureRequestObserver` | `SignatureRequest` | `status`, `deleted_at`, `tenant_id` | idem |
| `NotificationCenterObserver` | `NotificationCenter` | `status`, `read_at`, `deleted_at`, `tenant_id` | idem + `deleted` / `restored` (soft deletes) |

**Não** invalidar a partir de `AuditLog`, `SignatureRequestSignerObserver` nem qualquer outro call-site em 19B.1.

#### Pendência arquitectural (assinaturas)

O `HeroProvider::countSignaturesPending` conta `SignatureRequest` por `status` (`draft`/`sent`/`failed`). Quando um `SignatureRequestSigner` assina mas o **parent** `SignatureRequest` permanece `sent` (ainda há signers pendentes), só o signer é `updated` — **não** há invalidação L2 nessa fase (por decisão explícita de não observar o signer). O pedido completo dispara `SignatureRequestObserver` quando o parent transita (ex.: `completed`). Até lá, L1/L2 podem mostrar contagem de pedidos “pendentes” coerente com **pedidos** abertos; se no futuro os KPIs passarem a depender de linhas de signer sem `save()` no parent, reabrir desenho (fora do âmbito 19B.1).

#### Código

- `App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys`
- `App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheInvalidator`
- `ExecutiveDashboardReadService::sharedKey()` delega em `ExecutiveDashboardCacheKeys::l2Key()`
- `DashboardMetricsService::getMetrics()` delega em `ExecutiveDashboardCacheKeys::l1Key()`

#### Testes

- Unit: `tests/Unit/Dashboard/Executive/Cache/ExecutiveDashboardCacheKeysTest.php`
- Feature: `ExecutiveDashboardMetricsL1CacheHitTest`, `tests/Feature/Dashboard/Executive/Cache/ExecutiveDashboardCacheInvalidatorTest.php`, `CrossTenantCacheLeakageTest.php`, `tests/Feature/Observers/Dashboard/*` (invalidação por observer e casos negativos §5.5 / `AuditLog`)
- Observabilidade 19B.2: `tests/Unit/Dashboard/Executive/Observability/ExecutiveDashboardObservabilityTest.php`, `tests/Feature/Dashboard/Executive/Observability/{L1L2InstrumentationTest,InvalidatorInstrumentationTest,CacheStatsCommandTest}.php`

### 19B.2 — Observabilidade leve do cache (D13–D18)

#### Decisões

| ID | Regra |
|----|--------|
| **D13** | Observabilidade interna (counters em cache + comando Artisan); sem APM externo. |
| **D14** | Granularidade diária (`:{Y-m-d}`); TTL dos counters **7 dias**. |
| **D15** | Counters **agregados globalmente** — nunca `tenant_id` / `t_*` nas chaves `dashboard:obs:*`. |
| **D15a** | Hit/miss vía `Cache::has()` antes de `remember` / `flexible` (race aceite). |
| **D16** | Instrumentação apenas nos 3 call-sites acordados; `recordInvalidation()` **uma vez** por `invalidateForTenant`. |
| **D17** | Stale-hit do `flexible` não contabilizado (gap). |
| **D18** | Leitura só via `php artisan dashboard:cache-stats` (sem widget/API/DB). |

#### Chaves de counter

Ver `docs/execution/19B.2-dashboard-observability.md` §8 (`dashboard:obs:l1:hit:{Y-m-d}`, …).

#### Código

- `App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability`
- `App\Console\Commands\Dashboard\CacheStatsCommand` (`dashboard:cache-stats`)
- Instrumentação: `DashboardMetricsService`, `ExecutiveDashboardReadService::loadOrComputeShared`, `ExecutiveDashboardCacheInvalidator`

#### Como ler `cache-stats`

Ver exemplo JSON na rule `.cursor/rules/dashboard.mdc` (secção **Observabilidade (19B.2)**) ou executar `php artisan dashboard:cache-stats --json`.

### 19B.3 — Projection table (L3, D19–D25)

#### Decisões

| ID | Regra |
|----|--------|
| **D19** | `payload` guarda apenas **Hero + Operations** (mesmo shape que L2 plain); Priorities/Activity permanecem sempre frescos em cada `read()`. |
| **D20** | Refresh híbrido: job + comando `dashboard:refresh-projections`; após invalidação L1/L2, `markStale($tenantId)` nas linhas do tenant. |
| **D21** | `ExecutiveDashboardReadService::loadOrComputeShared` consulta projection **antes** de `Cache::flexible` quando a flag está activa. |
| **D22** | Sem projection para `global` ou `none`. |
| **D23** | `board.dashboard.use_projection` / env `BGP_DASHBOARD_USE_PROJECTION` (default `false`); com `false` o read service ignora a tabela; o job pode continuar a popular dados. |
| **D24** | Válido: `is_stale=false` e `refreshed_at >= now()-10min` e `payload.version` igual ao config. |
| **D25** | Tabela normal (sem materialised view nativa). |

#### Código

- `App\Models\TenantDashboardSnapshot`
- `App\Services\Dashboard\Executive\Projection\DashboardProjectionService`
- `App\Jobs\Dashboard\RefreshTenantDashboardSnapshotJob`
- `App\Console\Commands\Dashboard\RefreshProjectionsCommand`
- Integração: `ExecutiveDashboardReadService`, `ExecutiveDashboardCacheInvalidator`

#### Testes

- `tests/Unit/Models/TenantDashboardSnapshotTest.php`
- `tests/Feature/Dashboard/Executive/Projection/{DashboardProjectionServiceTest,ProjectionReadServiceTest,RefreshProjectionJobTest,RefreshProjectionsCommandTest,ProjectionCrossTenantTest}.php`

### Exposição via API (19B.4)

O contrato `ExecutiveDashboardSnapshot` está disponível em **`GET /api/v1/dashboard/snapshot`** (ver `docs/features/api.md`, secção **Dashboard**): ability Sanctum **`reports:read`** + gate **`view_executive_dashboard`**; query opcional `period`; rate limit 60/min por utilizador. Esquema OpenAPI em `docs/openapi/v1.yaml` (`paths./dashboard/snapshot`).

### QA 19A.8 — findings (arquivado)

> **Histórico congelado** (evidência estática pré-staging). O relatório operacional oficial da validação em staging é [`docs/execution/19A.8-staging-validation.result.md`](../execution/19A.8-staging-validation.result.md) — preencher após GO QA.

Esta secção é o **relatório operacional** da Fase 19A.8 (validação e rampa controlada). Não substitui o QA em staging real — é a evidência produzida pelo Arquitecto a partir do código + suite de testes, complementada pela checklist a ser confirmada por QA humano antes do GO produção.

#### Pré-trabalho técnico aplicado

| Item | Estado | Onde |
|---|---|---|
| `BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS=false` documentado no `.env.example` | ✅ aplicado | `.env.example` (linha final, com comentário "Fase 19A.7 — true ativa…") |
| 5 perfis seedados (`super_admin`, `tenant_admin`, `board_member`, `executive`, `guest`) | ✅ confirmado | `database/seeders/RolesAndPermissionsSeeder.php` |
| Teste §12.C — user sem `tenant_id` + sem super_admin: sem gate/widgets executivos; shell dashboard OK | ✅ adicionado e verde | `ExecutiveDashboardPageTest::test_user_sem_tenant_sem_super_admin_sem_gate_mas_shell_dashboard_acessivel_decisao_19a8` |
| Decisão §12.C registada na rule | ✅ aplicado | `.cursor/rules/dashboard.mdc` (secção "UI (19A.7)") |
| Logs no `ExecutiveDashboardReadService` | ❌ **não aplicado**, ver justificação abaixo | — |

**Justificação para não adicionar logs** (decisão arquitectural 19A.8): o blueprint admitia "opcionalmente logs mínimos, **somente se necessário** para medir performance em staging". Avaliação: Telescope (`Queries` + `Cache` panels) e `DB::listen` em rota de debug temporária dão visibilidade suficiente no ambiente de staging sem poluir produção. Adicionar `Log::info` permanente no read service introduz ruído em todos os pedidos e exige novos testes de canal de log. **Recomendação**: se QA staging confirmar que precisa de timing dedicado, abrir PR `feat(dashboard): add read service timing log` em 19B com cobertura de teste, **não** em 19A.8. Mantém-se o read service limpo.

#### Hardening — fix de bug pré-existente exposto pelo deploy

Durante a primeira tentativa de deploy do branch `feature/fase19`, o hook `composer.json` → `post-autoload-dump` → `@php artisan filament:upgrade` falhou com `Filament\Support\Commands\AssetsCommand::copyAsset(): Argument #1 ($from) must be of type string, null given`.

**Causa raiz** (bug pré-existente desde Fase 14, latente até hoje): em `AdminPanelProvider`, os 3 assets locais (`bgp-panel`, `bgp-login`, `bgp-dashboard`) usavam `Css::make($id)->relativePublicPath(...)` **sem** passar o caminho de origem `$path`. `Asset::getPath()` devolve `null`; `AssetsCommand::copyAsset()` tipa `$from` como `string` e crasha ao iterar `FilamentAsset::getStyles()`. Bug é exposto em deploy real porque é a primeira vez que `filament:upgrade` (e portanto `filament:assets`) corre num ambiente onde os ficheiros são copiados a cada release Forge. O `bgp-dashboard` apenas adicionou mais uma instância do mesmo defeito.

**Fix aplicado** (19A.8): passar `public_path('css/app/...')` como `$path` para os 3 assets em `AdminPanelProvider::panel()`. `copyAsset` faz copy-to-self (no-op seguro) em vez de crashar. Comentário inline documenta a razão.

**Validação local**:

```bash
php artisan filament:assets   # ✅ Successfully published assets! (lista inclui bgp-panel/bgp-login/bgp-dashboard)
php artisan filament:upgrade  # ✅ Successfully upgraded!
php artisan test              # ✅ 286/286 verde
```

**Convenção daqui em diante**: qualquer CSS/JS local em `public/` registado via `FilamentAsset` (panel `assets([...])`) **tem de** passar `$path = public_path(...)` ou apontar para `resources/dist/...`. Se o source não existir em disco (ex.: gerado por Vite), apontar para o build output ou usar `loadedOnRequest()` — nunca deixar `$path` implícito a null.

#### Cobertura automática (suite verde)

| Domínio | Suite-alvo | Última execução |
|---|---|---|
| Gate, flag, `canAccess`, `canView` | `tests/Feature/Filament/Dashboard/ExecutiveDashboardPageTest` | verde (incl. §12.C + GET `/admin` sem gate) |
| Render dos 4 widgets executivos (Livewire) | `tests/Feature/Filament/Dashboard/Widgets/Executive*WidgetTest` | 12 testes verde |
| Smoke render da Page com flag on/off | `tests/Feature/Filament/Dashboard/ExecutiveDashboardSmokeTest` | 2 testes verde |
| Providers internos (Hero/KPI/Operations/Priorities/Activity) | `tests/Unit/Dashboard/Executive/Providers/` | 21 testes verde (provém 19A.4) |
| Read service (composition + cache flow + anti-leakage) | `tests/Feature/Dashboard/Executive/` + `tests/Unit/Dashboard/Executive/ExecutiveDashboardReadServiceCompositionTest` | verde (provém 19A.5) |
| Snapshot DTOs shape + serialização | `tests/Unit/Dashboard/Executive/Snapshot/` | verde (provém 19A.3) |
| Regressão API + Auth + Filament + multitenancy | `php artisan test` completo | verde (352/352 na última execução local do Executor; actualizar após cada release) |

#### Comportamento por perfil — tabela canónica (validada pelo gate)

Comportamento derivado de `AuthServiceProvider::view_executive_dashboard` + `Dashboard::canAccess()` + `RolesAndPermissionsSeeder`. Cada linha indica o que se observa **com flag ON**; com **flag OFF** o comportamento é "comportamento Fase 14" (qualquer auth abre a page; cada `*StatsWidget` aplica `view_reports`).

| Perfil | `view_reports`? | `tenant_id`? | super_admin? | Gate `view_executive_dashboard` | `Dashboard::canAccess()` (flag ON) | Widgets executivos visíveis? |
|---|---|---|---|---|---|---|
| **super_admin** | sim (todas) | opcional | **sim** (bypass) | ✅ | ✅ (auth) | sim — Hero/KPI/Operations agregados globais; Priorities/Activity vazios por D4 |
| **tenant_admin** | sim | sim | não | ✅ | ✅ (auth) | sim (snapshot do tenant) |
| **board_member** | sim | sim | não | ✅ | ✅ (auth) | sim (Priorities/Activity filtrados por policy item-a-item, D9) |
| **executive** | sim | sim | não | ✅ | ✅ (auth) | sim |
| **guest** | sim (seed actual) | sim | não | ✅ | ✅ (auth) | sim — **ver observação 1** |
| **user com role mas sem `view_reports`** | não | sim | não | ❌ | ✅ (auth) | não (gate nega nos widgets) |
| **user sem `tenant_id`** (não super_admin) | sim ou não | **null** | não | ❌ (§12.C) | ✅ (auth) | não — **decisão fechada e testada** |
| **anónimo** | n/a | n/a | n/a | ❌ | ❌ | não |

**Observação 1 — role `guest` tem `view_reports` na seed actual.** Não é bug; é uma decisão herdada da Fase 14 (`RolesAndPermissionsSeeder` linhas 72-77). Implicação: com flag ON, "guest" tem acesso ao Executive Dashboard. Não bloqueia 19A.8 — apenas é **finding para revisão de produto**: se "guest" não deveria ver indicadores executivos, alterar a seed em PR separado (não escopo desta fase). Recomendação: confirmar com Product antes da rampa.

#### Cache — split confirmado por código + testes

| Camada | Chave | TTL efectivo | Onde se prova |
|---|---|---|---|
| L1 KPIs | `dashboard_metrics:v1:{cacheSegment}:{period}` | 90 s (`DashboardMetricsService`) | Fora do `Cache::flexible` partilhado — confirmado em `ExecutiveDashboardReadService::read()` linha 36 (chamada precede `loadOrComputeShared`) |
| L2 shared | `dashboard_snapshot:v1:{cacheSegment}:{period}:shared:plain` | `flexible(60, 120)` | `ExecutiveDashboardReadService::sharedKey()` + `Cache::flexible()` linha 67-74 |
| Per-user feeds (Priorities/Activity) | sem cache | sempre frescos | `ExecutiveDashboardReadService::read()` linhas 38-39 (após `loadOrComputeShared`, fora do `flexible`) |
| Anti cache duplo | n/a | KPIs ficam **fora** do payload `plain` que entra em L2 | `buildSharedPlain()` (linha 84) inclui só `hero` + `operations`; KPIs nunca entram |
| Anti `cacheSegment` na UI | n/a | nenhum Blade renderiza `cache_segment` | `tests/Feature/Filament/Dashboard/Widgets/Executive*WidgetTest::test_nao_renderiza_cache_segment` (4 widgets cobertos) |
| Caso `cacheSegment === 'none'` | n/a | early-return sem L2 | `ExecutiveDashboardReadService::loadOrComputeShared()` linha 62 |

#### Multi-tenant — cobertura por código + testes

| Cenário | Mecanismo arquitectural | Onde se prova |
|---|---|---|
| Tenant A não vê tenant B | `ReportingContext::restrictToTenant()` aplicado em todos providers; chaves de cache por `cacheSegment` | providers tests + `ExecutiveDashboardReadServiceTest::test_cache_isolation_per_tenant` |
| Super_admin global | `cacheSegment() === 'global'`; Priorities/Activity = `[]` por D4 | `ActivityFeedProvider`/`PrioritiesProvider` short-circuit em `isGlobalScope()` |
| Tenant vazio | shape estável devolvido com defaults zero (`ExecutiveDashboardSnapshot::emptyShape` + factories) | DTO tests cobrem shape; widget Priorities tem teste de empty state |
| Activity sem leak cross-tenant | `Gate::forUser($actor)->allows('viewAny', AuditLog::class)` + filtro de queries por tenant + `view` per item | `ActivityFeedProvider` tests |
| Tenant com volume alto | L2 (`Cache::flexible`) absorve cargas concorrentes; sem coleções grandes (apenas `COUNT`/`LIMIT N`) | a **medir em staging**: ver pendência QA #2 abaixo |

#### Performance — observações estáticas + pendências QA

**Confirmado por leitura de código** (estático):

- Read path usa apenas `COUNT(*)`, `SELECT … LIMIT N` e agregações por tenant — nenhum `with()` que carregue coleções grandes.
- Lazy de Operations/Priorities está em `protected static bool $isLazy = true` (Filament v5 pattern). Hero/KPI carregam imediatamente.
- L2 anti-stampede via `Cache::flexible(stale, expire)`; nenhum `Cache::lock` extra é necessário porque o trabalho cabe < 250 ms em tenants médios.

**Pendências para QA staging** (não validáveis por código):

| # | A medir | Alvo p95 | Ferramenta sugerida |
|---|---|---|---|
| 1 | TTFB cold (L1+L2 frios) | ≤ 1.2 s | Chrome DevTools Network |
| 2 | TTFB warm (L1+L2 quentes) | ≤ 400 ms | Chrome DevTools Network |
| 3 | Queries por carga cold | ≤ 25 | Telescope `Queries` |
| 4 | Queries por carga warm | ≤ 5 | Telescope `Queries` |
| 5 | Lazy widgets — round-trip Livewire | ≤ 300 ms | DevTools Network (filtro `livewire/update`) |
| 6 | Soak 24h staging com flag ON | 0 `ERROR` recorrente nos widgets executivos | `tail -F storage/logs/laravel.log` |

#### Visual — pendências QA staging

Não validáveis por código automático. Checklist a executar e capturar:

| # | Verificação | Critério | Evidência esperada |
|---|---|---|---|
| V1 | Ordem A→B→C→D em desktop ≥ 1024 px | ✅ | screenshot |
| V2 | Ordem mantida em tablet 768–1023 px | ✅ | screenshot |
| V3 | Ordem mantida em mobile < 768 px | ✅ | screenshot |
| V4 | KPI Strip 4 colunas em ≥ 1024 / 2 em tablet / 1 em mobile | ✅ | screenshot |
| V5 | Dark mode com contraste WCAG AA | ✅ | screenshot |
| V6 | Empty states em tenant recém-criado | 4 keys `dashboard.executive.*.empty` renderizam | screenshot |
| V7 | `urgency` modifier visível (borda vermelha/laranja/âmbar/cinza) | ✅ | screenshot |
| V8 | I18n nas 3 línguas | nenhuma key `dashboard.executive.*` falta | screenshot pt_BR/en/es |
| V9 | Selector `period` no Hero altera os 3 widgets restantes | ✅ | clip / GIF |
| V10 | "Atualizado às HH:MM" no Hero respeita `config('app.timezone')` | ✅ | screenshot |

#### Rollback — procedimento testado

```bash
# Em staging/produção, sem deploy de código:
sed -i '' 's/^BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS=true/BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS=false/' .env
php artisan config:clear
php artisan view:clear
php artisan cache:clear
# Confirmar: dashboard volta a apresentar os 6 *StatsWidget legacy
```

Tempo expectável de execução: < 2 minutos. Cobertura automática do rollback path: `ExecutiveDashboardSmokeTest::test_page_renderiza_sem_erro_com_flag_false_legado_intacto` + 6 testes de `canView()` dos `*StatsWidget` legacy.

#### Critérios GO / NO-GO produção

| Critério | Estado actual | Bloqueia produção? |
|---|---|---|
| Suite completa verde | ✅ 286/286 (1941 assertions) | sim |
| Decisão §12.C fechada e testada | ✅ | sim |
| 5 perfis seedados | ✅ | sim |
| Flag documentada em `.env.example` | ✅ | sim |
| §3 (7 perfis em staging) — confirmação humana | ⏳ pendente QA staging | sim |
| §4 (multi-tenant manual) — confirmação humana | ⏳ pendente QA staging | sim |
| §5 (cache split) — confirmação Telescope | ⏳ pendente QA staging | sim |
| §6 (visual em 3 viewports + dark) — sign-off Product | ⏳ pendente QA staging | sim |
| §7 (performance p95) — medições em tenant grande | ⏳ pendente QA staging | sim |
| §8 (segurança/cross-tenant) — exercício manual | ⏳ pendente QA staging | sim |
| Rollback testado em staging | ⏳ pendente QA staging | sim |
| Soak 24h sem `ERROR` recorrente | ⏳ pendente QA staging | sim |
| Revisão de "guest tem `view_reports`" pela Product | ⏳ recomendado | não bloqueador |

**Veredito do Arquitecto (parte automatizada da 19A.8): GO técnico**. A camada de código está pronta. Falta apenas o **GO operacional** (validação humana em staging, conforme tabela acima).

### Anexo — Snapshot v1 — JSON canônico

Contrato estável gerado por `ExecutiveDashboardSnapshot::toArray()` (chaves snake_case). Exemplo ilustrativo; `period` segue **`App\Enums\DashboardMetricsPeriod::value`** (actualmente `this_month`, `last_30_days`, `all_time`; evolução futura pode alargar valores — bump `snapshot_version`). Campo **`cache_segment`**: valor opaco (ex.: `t_123`, `global`); **`generated_at`** em ISO8601.

```json
{
  "version": "v1",
  "period": "this_month",
  "cache_segment": "t_123",
  "generated_at": "2026-05-09T12:00:00+00:00",
  "hero": {
    "tasks_overdue": 0,
    "votes_open": 0,
    "signatures_pending": 0,
    "next_meeting_at": null,
    "next_meeting_id": null
  },
  "kpis": {
    "tasks": {},
    "meetings": {},
    "votes": {},
    "signatures": {}
  },
  "operations": {
    "minutes_pending_review": 0,
    "meetings_this_month": 0,
    "notifications_unread": 0
  },
  "priorities": [],
  "activity": []
}
```
