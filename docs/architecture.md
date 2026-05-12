# Arquitetura — Board Governance Platform

## Base técnica do repositório

O código da aplicação assenta em **Laravel** (skeleton oficial na raiz do repositório: `artisan`, `app/`, `routes/`, `config/`, etc.). A versão do framework está fixada em `composer.json` / `composer.lock` (actualmente **Laravel 13** com PHP **^8.3**). O painel **Filament**, multi-tenancy explícito e restantes módulos de negócio serão adicionados em fases posteriores sobre esta base — ver [roadmap.md](roadmap.md).

## Produto

SaaS **multi-tenant** de **governança corporativa**: organizações (tenants), conselhos, reuniões, atas, documentos e versionamento, votações, assinaturas digitais, pendências/workflows, notificações, relatórios e integrações futuras (Office 365, OneDrive, DocuSign, e-mail, BI, videoconferência).

## Stack

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel |
| Painel administrativo | Filament v5 (Livewire v4) — pacote instalado; painel a configurar |
| UI reativa (onde aplicável) | Livewire |
| Base de dados | **SQLite** por defeito no `.env.example` (dev) e em testes (`.env.testing`); suporte a **MySQL** e **PostgreSQL** via `config/database.php` |
| Roles / permissões | Spatie Laravel Permission (roles globais; `teams` off — ver [features/auth-permissions.md](features/auth-permissions.md)) |
| API (quando existir) | Laravel + Sanctum (tokens com abilities) |

## Testes e base de dados

Os testes automatizados usam **SQLite** (`database/testing.sqlite`) via **`.env.testing`**, nunca o MySQL principal por defeito. O `phpunit.xml` fixa `APP_ENV=testing` para o Laravel carregar esse ficheiro; o `Tests\TestCase` valida a ligação e usa **`RefreshDatabase`**. Ver [testing.md](testing.md).

## Idiomas

Idiomas suportados (**pt_BR**, **en**, **es**), middleware `SetLocale`, ficheiros em `lang/` e campo `users.locale` — ver [features/localization.md](features/localization.md).

## Multi-tenancy

- Modelo alvo: **uma base única** (MySQL/PostgreSQL conforme ambiente), dados de negócio com **`tenant_id`**, isolamento por **`TenantScope`** / trait **`BelongsToTenant`** e **resolução por utilizador autenticado** (`TenantResolver`; subdomínio fica para evolução futura). Detalhe em [features/multitenancy.md](features/multitenancy.md).
- Tabela `tenants`; `users.tenant_id` (nullable durante transição); `users.is_super_admin` ignora o scope como flag bootstrap até existir role `super_admin` via Spatie (ver ficha).
- Seed inicial: `InitialTenantSeeder` + variáveis `SEED_ADMIN_*` (ver ficha).
- Perfis: **`super_admin`** (acesso global explícito), **`tenant_admin`** e utilizadores do tenant apenas no seu contexto.
- **Nenhuma** consulta ou policy pode assumir dados de outro tenant.

## Camadas de aplicação

- **HTTP / Filament:** finos; validação via Form Requests; autorização via Policies/Gates.
- **Negócio:** Actions, Services; lógica complexa fora de Models quando deixar de caber com clareza.
- **Modelos:** relacionamentos Eloquent, casts, scopes simples; observers quando fizer sentido.
- **Auditoria:** eventos críticos registados em `audit_logs` (ver [features/audit-logs.md](features/audit-logs.md)) via `AuditLoggerService` + observers (Tenant/User nesta fase), com valores filtrados por allowlist e sem dados sensíveis.

## Segurança (resumo)

- Validação sempre no servidor; não confiar no Filament/Livewire sozinhos.
- Senhas com hash nativo Laravel; **2FA** como evolução planeada.
- Logs sem segredos nem PII desnecessária; exclusão física de dados sensíveis só com justificativa documentada.

## API REST

Estrutura preparada para **`/api/v1`** (versionamento) com **Laravel Sanctum** e tokens por dispositivo.

- **Tenancy (v1):** tenant sempre derivado de `auth()->user()->tenant_id` (sem `tenant_id` em header/path; sem switch tenant nesta fase).
- **Autorização:** interseção entre **Policies/Spatie** do utilizador e **abilities** do token (abilities limitam, nunca ampliam).
- **Anti-vazamento:** Actions/Policies validam explicitamente `tenant_id`/ownership; é proibido resolver recursos por `find(id)` antes de aplicar escopo/autorização.
- **Respostas:** envelope JSON consistente (success/data/error/meta) definido na ficha da API.

Detalhes e contratos: ver [features/api.md](features/api.md).

## Executive Dashboard Read Architecture

A camada de leitura para o **dashboard executivo** (Fase 19A) é desenhada para suportar evolução para um endpoint de API (Fase 19B) sem reescrita. As responsabilidades são separadas explicitamente:

| Camada | Responsabilidade | Cache |
|---|---|---|
| `App\Services\Dashboard\DashboardMetricsService` | KPIs **estáveis** por tenant/global (counters, agregações simples) | 90 s, chave `dashboard_metrics:v1:{seg}:{period}` |
| `App\Services\Dashboard\Executive\ExecutiveDashboardReadService` | **Orquestrador (19A.5)** — **`read(User, DashboardMetricsPeriod)`** → snapshot. **KPI**: `KpiStripProvider::build` **fora** de `Cache::flexible`; **Hero+Operations**: `Cache::flexible` sobre chave `dashboard_snapshot:{snapshot_version}:{segmento}:{period}:shared:plain` (payload só arrays — evita `__PHP_Incomplete_Class` ao `unserialize`); **Priorities/Activity** sempre frescos. **`cacheSegment()==none`** → sem L2 (recalcula em memória). | L2 apenas Hero/Operations; L1 KPI separado (`dashboard_metrics:v1`)
| `App\Services\Dashboard\Executive\Providers\*` | **19A.4:** `HeroProvider`, `KpiStripProvider`, `OperationsProvider`, `PrioritiesProvider`, `ActivityFeedProvider`; um `build(User, DashboardMetricsPeriod)` cada; não usam auth()/request()/session(); sem cache próprio — ver `docs/features/dashboard.md`. | só `DashboardMetricsService` cacheia KPI; read service só L2 `:shared:plain` |
| `App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot` (+ sub-DTOs) | **DTO `final readonly`** consumido por widgets Livewire e (futuramente) pelo endpoint API. Shape estável, versionado. | — |
| `App\Services\Reports\ReportsService` | Agregações por período para `OperationalReports` (sem cache) — **fora** do executive dashboard. | — |

### Princípios

- **Dashboard ≠ OperationalReports ≠ BI.** Cada camada responde a uma pergunta distinta (ver [`features/dashboard.md`](features/dashboard.md)).
- **Widgets não consultam Eloquent.** Consomem o DTO devolvido pelo orquestrador.
- **Gate único** `view_executive_dashboard` (registado em `App\Providers\AuthServiceProvider`) controla **só** os widgets executivos (`Executive*Widget::canView()`) **quando** a feature flag `board.dashboard.use_executive_widgets` está activa (**19A.7** — não é permissão Spatie; ver [`features/auth-permissions.md`](features/auth-permissions.md)). A página `Dashboard` aceita qualquer utilizador autenticado (evita 403 pós-login sem roles); mantém-se `view_reports` para `OperationalReports` e para os widgets legacy `*StatsWidget`.
- Não existe `Gate::before` global: cada Gate/Policy trata `super_admin` explicitamente.
- **Tenancy explícita** via `ReportingContext`: `withoutGlobalScopes()` + `restrictToTenant()`. `super_admin` continua como único bypass.
- **Anti-leak por item** em `Priorities` e `Activity`: cada candidato passa por `Gate::forUser($user)->allows('view', $item)` antes de entrar no DTO; items omitidos **somem sem mensagem** (anti-enumeração).
- **Cache versionado** por chave (`v1` → `v2` ao mudar shape), com segmento de tenancy obrigatório, TTL ≤ 120 s e protecção anti-stampede (`Cache::flexible` ou `Cache::lock`). Convenção em [`.cursor/rules/cache.mdc`](../.cursor/rules/cache.mdc).
- **Per-user data nunca cacheada partilhada.** Priorities/Activity dependem das policies do utilizador; cachear partilhado vazaria dados.
- **Listas limitadas** (`take(N)`) em todos os feeds; sem paginação no dashboard.

### Política para `super_admin`

- Hero/KPI/Operations agregados globalmente (`isGlobalScope()`).
- `Priorities` e `Activity` desactivados ou com `LIMIT` agressivo, para não varrer milhões de rows em runtime.

### Pendência futura — projection model (Fase 19B)

Para tenants enterprise (> 50 k tasks/votes/notifications), prevê-se uma **projection table** `tenant_dashboard_snapshots` populada por job a cada N minutos, evitando `COUNT(*)` em runtime. A camada `ExecutiveDashboardReadService` continuará como ponto de entrada — apenas a fonte de dados muda.

Detalhe operacional: [`features/dashboard.md`](features/dashboard.md), secção "Executive Dashboard (Fase 19A)".

### Decisões formais (Fase 19A.1)

> Contrato arquitectural vinculativo para 19A.3 → 19A.7 e 19B. Detalhe completo (rationale, trade-offs, pré-condições) em [`features/dashboard.md`](features/dashboard.md), secção "Formal Decisions — Executive Dashboard (19A.1)".

| ID | Tema | Decisão |
|---|---|---|
| **D1** | TTL snapshot | 60 s lógico via `Cache::flexible(stale=60, expire=120, …)` |
| **D2** | Anti-stampede | Obrigatório; padrão `Cache::flexible`; `Cache::lock` apenas em fallback |
| **D3** | Cache split | Hero/KPI/Operations partilhado por tenant; **Priorities/Activity per-user, NÃO partilhado** |
| **D4** | super_admin global | KPIs globais OK; **Priorities/Activity desactivados** em modo global |
| **D5** | Período | Estado único na page; comunicação `dispatch('dashboard:period-changed')` + `#[On(...)]` |
| **D6** | Widgets Livewire | **Máximo 4** (Hero, KPI Strip, Operations, Priorities) |
| **D7** | `deferLoading()` | Obrigatório em Priorities, Activity e blocos secundários |
| **D8** | Snapshot contract | **Implementado (19A.3):** DTOs `final readonly` em `App\Services\Dashboard\Executive\Snapshot` — shape estável, versionado, **API-ready** (ISO8601 + enum `->value` em `toArray()`). |
| **D9** | Policy filtering | Item-a-item via `Gate::forUser($user)->allows('view', $item)`; sem mensagem "X omitidos" |
| **D10** | Legacy widgets | `*StatsWidget` mantidos como fallback durante 19A; remoção só em **19B.5** |

#### Rationale e trade-offs (resumo)

- **Latência ≤ 120 s entre PATCH e snapshot** (D1) é aceitável para fluxos executivos; trade-off explícito vs invalidação por evento (pendência 19B.1).
- **Per-user sem cache** (D3) escala linearmente com utilizadores activos por tenant; mitigado por `take(N)` (D9) + `deferLoading()` (D7).
- **super_admin sem feeds operacionais** (D4) perde informação accionável global, aceitável para o papel (operacional, não decisor de negócio).
- **DTO versionado** (D8) obriga warm pós-deploy ao bump (`v1` → `v2`); procedimento em `.cursor/rules/cache.mdc`.
- **4 widgets** (D6) força consolidação de Activity num bloco existente — alinha com direcção UX "menos widgets independentes".

#### Riscos conhecidos pós-19A.1

| Risco | Mitigação 19A | Pendência 19B |
|---|---|---|
| **L1** — queries **overdue tasks** (`status IN(pending, in_progress)` + `due_date` &lt; agora) para KPI/dashboard | **Mitigado na 19A.2** — índice composto `tasks_tenant_status_due_date_idx` (`tenant_id`, `status`, `due_date`) | — |
| Stampede em cold start (4 widgets paralelos) | D2 (`flexible`) | Pre-warm por job (19B.4) |
| Vazamento por item em feeds | D9 (policy item-a-item) | — |
| Latência percebida em PATCH → snapshot | D1 (TTL 60 s aceite) | Invalidação por evento (19B.1) |
| Tenants enterprise (> 50 k rows) | Índice overdue em `tasks` (19A.2); índices adicionais avaliados sem over-indexing | Projection model (19B.2) |
| Bump de shape sem warm de cache | D8 (versionamento + procedimento documentado) | — |
| Regressão UX vs Fase 14 | D10 (`*StatsWidget` fallback) | Remoção controlada em 19B.5 |

Estas decisões são **pré-condição** para 19A.2 (índices) e 19A.3 (DTOs). Qualquer desvio futuro a D1–D10 exige alteração desta tabela e bump da chave `dashboard_snapshot:v{n}`.

## Decisões técnicas pendentes de fixação

Registar aqui ou na feature correspondente quando forem tomadas (ex.: pacote de activity log vs. tabela `audit_logs` própria, estratégia exata de storage por tenant).
