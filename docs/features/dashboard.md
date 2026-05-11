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
- `tests/Unit/Dashboard/Executive/Snapshot/` (19A.3)
- `tests/Unit/Dashboard/Executive/Providers/` (19A.4)
- `tests/Feature/Dashboard/Executive/ExecutiveDashboardReadServiceTest.php` (19A.5)
- `tests/Unit/Dashboard/Executive/ExecutiveDashboardReadServiceCompositionTest.php` (19A.5)
- `tests/Feature/Filament/Dashboard/ExecutiveDashboardPageTest.php` (19A.7 — gate/flag/canView)
- `tests/Feature/Filament/Dashboard/ExecutiveDashboardSmokeTest.php` (19A.7 — render Page com flag on/off)
- `tests/Feature/Filament/Dashboard/Widgets/Executive{Hero,KpiStrip,Operations,Priorities}WidgetTest.php` (19A.7 — render Livewire + dispatch/On + empty state)

## Pendências futuras

- Export CSV/PDF, gráficos, invalidação granular de cache, relatórios adicionais, integrações com BI (Looker Studio).

---

## Executive Dashboard (Fase 19A)

> Estado: **19A.0–19A.4 + 19A.5 concluídas**. **19A.5**: `ExecutiveDashboardReadService` em `App\Services\Dashboard\Executive\ExecutiveDashboardReadService` (`read()` → `ExecutiveDashboardSnapshot`, L2 `Cache::flexible` só sobre Hero/Operations). **Widgets novos** (19A.7), **gate dedicado** (19A.6). Toda a Fase 14 actual (KPIs Filament + `OperationalReports`) **mantém-se intacta** até à fase 19A.7 salvo trabalho futuro nos widgets executivos.

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
| L2 snapshot shared | `dashboard_snapshot:{snapshot_version}:{segmento}:{period}:shared` | array serializado `{ hero, operations, shared_generated_at }` via `flexible` (stale/expire de `config board.dashboard`) |
| *(nunca na L2)* | — | KPI strip, feeds Priorities / Activity |

### Limites de widgets (Filament/Livewire)

- **Máximo 4 widgets Livewire** no dashboard executivo. `StatsSecondary` consolida-se em `OperationsBlock`/`OperationsWidget`.
- Widgets **não consultam Eloquent** — consomem o snapshot do service.
- `Priorities` e `Activity` carregam com **`deferLoading()`** para não bloquear a renderização inicial.
- `canView()` de cada widget e `canAccess()` da page: **gate único** `view_executive_dashboard` (sem `can('view_reports')` inline).

#### UI implementada (19A.7)

| # | Sort | Classe | Lazy | Conteúdo da view (Blade `executive/*`) |
|---|---|---|---|---|
| A | `10` | `App\Filament\Admin\Widgets\Executive\ExecutiveHeroWidget` | não | tagline, **selector `period`** (owner do dispatch D5), updated_at, tasksOverdue/votesOpen/signaturesPending, nextMeetingAt |
| B | `11` | `…\Executive\ExecutiveKpiStripWidget` | não | 4 grupos: tasks / meetings / votes / signatures |
| C | `12` | `…\Executive\ExecutiveOperationsWidget` | **sim** | minutesPendingReview, meetingsThisMonth, notificationsUnread + CTA `OperationalReports` |
| D | `13` | `…\Executive\ExecutivePrioritiesWidget` | **sim** | secção 1 = Priorities (com `urgency` modifier); secção 2 = Activity recente |

- **Estilos**: `public/css/app/bgp-dashboard.css` (registado como asset `bgp-dashboard` no `AdminPanelProvider`). Convenção BEM `bgp-dashboard__*`. Suporte a tema claro/escuro.
- **i18n**: `lang/{pt_BR,en,es}/dashboard.php`, key root `dashboard.executive.*`. Bloco legacy `dashboard.widgets.*` permanece enquanto a flag puder estar `false`.
- **Feature flag — coexistência com Fase 14**: `config('board.dashboard.use_executive_widgets')` (env `BGP_DASHBOARD_USE_EXECUTIVE_WIDGETS`, default `false`).
  - `false` → 6 `*StatsWidget` (Fase 14) visíveis, 4 executivos ocultos, `Dashboard::canAccess()` apenas exige autenticação.
  - `true` → 4 executivos visíveis, 6 legacy ocultos por `canView()`, `Dashboard::canAccess()` exige gate `view_executive_dashboard`.
  - Activação de produção via env; remoção da flag + dos 6 legacy widgets em **19B.5** (D10).

### Estratégia de cache

| Bloco | Chave | TTL | Anti-stampede |
|---|---|---|---|
| KPI strip (`DashboardMetricsService`) | `dashboard_metrics:v1:{cacheSegment}:{period}` | ~90 s | interno ao serviço (`remember`), **fora** do L2 `:shared` |
| Hero / Operations (**só**) | `dashboard_snapshot:{snapshot_version}:{cacheSegment}:{period}:shared` | `flexible` 60 / 120 s (config `board.dashboard`) | `Cache::flexible(stale=60, expire=120, …)` |
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
| **D10** | Legacy widgets | `*StatsWidget` (Fase 14) **mantidos como fallback** durante toda a 19A. Remoção apenas em **19B.5**, depois de validação em produção | Permite rollback rápido se Hero/KPI Strip não convencer; mitiga risco de UX regression |

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
- **19A.7** (concluída): 4 widgets Livewire em `App\Filament\Admin\Widgets\Executive` + views Blade `bgp-dashboard__*` + asset CSS `public/css/app/bgp-dashboard.css` + i18n `dashboard.executive.*` (pt_BR/en/es) + `Dashboard::canAccess()` via gate `view_executive_dashboard`. Coexistência regida pela feature flag `board.dashboard.use_executive_widgets` (default `false`): `false` mantém os 6 `*StatsWidget` legacy visíveis, `true` activa o conjunto executivo e oculta os legacy. Testes em `tests/Feature/Filament/Dashboard/` (page + 4 widgets + smoke).
- **19A.8**: testes obrigatórios (multi-tenancy + policies per-item + 4 cenários `tests.mdc`).
- **19A.9**: actualizar esta ficha com estado pós-implementação.
- **19B**: invalidação por evento, projection table, endpoint `GET /api/v1/dashboard/snapshot`, remoção dos `*StatsWidget` legacy.

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
