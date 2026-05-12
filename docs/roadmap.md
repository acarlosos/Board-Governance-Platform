# Roadmap — Board Governance Platform

Índice oficial das fases do produto e a **ordem obrigatória** de entrega. Para detalhe de cada módulo ver as fichas em [`features/`](features/README.md); para visão técnica global ver [`architecture.md`](architecture.md).

## Princípios

- **Base sólida primeiro** (`.cursor/rules/foundation-first.mdc`): só avançar para módulos avançados depois de tenancy, autenticação, permissões e auditoria estarem estáveis.
- **Incremental** (`.cursor/rules/incremental.mdc`): unidades pequenas, testes obrigatórios nas áreas críticas (`.cursor/rules/tests.mdc`).
- **Sem features especulativas**: módulos futuros (Office 365, DocuSign, etc.) só entram quando a fase correspondente abrir.
- **Especificação executável por fase**: blueprint final de cada fase vive em [`execution/{fase}-{slug}.md`](execution/) — fonte única que o Executor consome, sem prompt paralelo no chat. Padrão registado em [`.cursor/rules/documentation.mdc`](../.cursor/rules/documentation.mdc).

## Estado actual

- **Fase 0 — concluída**: regras `.cursor/`, `docs/`, **Laravel 13**, `.env.example` com **SQLite** por defeito (dev), ambiente de testes isolado em SQLite (`.env.testing`), suporte a **MySQL**/**PostgreSQL** via `config/database.php`, **Filament v5** com painel `admin` (`->default()`), i18n **pt_BR / en / es** com middleware `SetLocale` e `users.locale`.
- **Fase 1 — base concluída**: tabela `tenants`, `users` com `tenant_id` / `status` / `is_super_admin` (bootstrap até Spatie) / soft deletes, enums, `TenantScope`, trait `BelongsToTenant`, `TenantResolver`, seed `InitialTenantSeeder`, testes de isolamento (modelo só em `tests/Support`). Pendências: middleware/UI de troca de tenant (multi-tenant “switch” avançado).
- **Fase 2 — base concluída**: Spatie Permission (migrations + `config/permission.php`), roles e permissões iniciais, `RolesAndPermissionsSeeder`, `User` com `HasRoles` / `isSuperAdmin()`, `TenantPolicy` e `UserPolicy`, testes em `AuthPermissionsTest`. Pendências: 2FA, refinamento de permissões por módulo.
- **Fase 3 — painel admin inicial (tenants/users) concluída**: `TenantResource`, `UserResource`, `PersistPanelUserAction`, traduções `tenants` / `users` / `roles` / `actions`, testes `FilamentAdminResourcesTest` (incl. UX de secções, `super_admin` só via toggle, preservação de slug em edição, Livewire no tenant). Pendências no roadmap da Fase 3: perfis na UI (3.3), auditoria em recursos (adiada à Fase 4).
- **Fase 4 — auditoria global concluída**: tabela `audit_logs`, `AuditLoggerService`, observers (Tenant/User), `AuditLogPolicy`, `AuditLogResource` (somente leitura) + traduções e testes.
- **Fase 5 — boards (conselhos) concluída**: `boards`, `board_members`, enums, models com `BelongsToTenant`, policies, `BoardResource` + RelationManager, auditoria (observers) e testes de isolamento/autorização.
- **Fase 6 — reuniões concluída**: `meetings`, `meeting_participants`, `meeting_agenda_items`, enums, models com `BelongsToTenant`, policies, actions (transições de status), `MeetingResource` + RelationManagers, auditoria (observers) e testes.
- **Fase 7 — documentos concluída**: `documents`, `document_versions`, `document_access_logs`, enums, models com `BelongsToTenant`, policies, actions (upload privado + versionamento + logs de acesso), `DocumentResource` + RelationManagers, auditoria (observers) e testes.
- **Fase 8 — atas concluída**: `minutes`, `minute_versions`, `minute_approvals`, enums, models com `BelongsToTenant`, policies, actions (workflow e aprovações), `MinuteResource` + RelationManagers, auditoria (observers) e testes.
- **Fase 9 — votações concluída**: `votes`, `vote_options`, `vote_responses`, enums, models com `BelongsToTenant`, policies, actions (máquina de estados e voto), `VoteResource` + RelationManagers, auditoria (observers) e testes.
- **Fase 14 — dashboard e relatórios operacionais (parcial)**: `DashboardMetricsService` + `ReportsService` (agregações por tenant/global com `super_admin`), cache curto por tenant, widgets no painel, página `OperationalReports`, testes em `DashboardTest`. Pendências: export, gráficos, invalidação fina de cache, BI.
- **Fase 15 — segurança avançada concluída**: 2FA TOTP nativo do Filament, `auth_sessions` auditáveis, revogação remota com `SESSION_DRIVER=database`, listeners de login/logout/failed, `PasswordPolicyService`, `SecuritySettings` page e permissão `manage_security`. Ficha: [`features/security.md`](features/security.md).
- **Fases 10–18 — pendentes** (salvo itens já marcados como concluídos por fase).

## Decisões já fixadas

- **Multi-tenancy:** uma base única (MySQL/PostgreSQL conforme ambiente), dados de negócio com `tenant_id`, isolamento por global scope + trait `BelongsToTenant`. **Resolução do tenant activo por sessão após login** (subdomínio/domínio customizado fica como evolução futura). Ver [`features/multitenancy.md`](features/multitenancy.md).
- **i18n:** `pt_BR` padrão, `en` fallback, `es` suportado; traduções da app em `lang/{locale}/`; vendor (Filament) só com override pontual. Ver [`features/localization.md`](features/localization.md).
- **Painel administrativo:** Filament v5 em `/admin` (painel `admin`, `->default()`). Ver [`features/filament-admin.md`](features/filament-admin.md).

## Fases

### Fase 0 — Setup do repositório

- 0.1 Criar regras `.cursor`
- 0.2 Criar docs internos
- 0.3 Instalar Laravel
- 0.4 Configurar `.env` e drivers de DB (SQLite por defeito no `.env.example`; MySQL/PostgreSQL suportados)
- 0.5 Configurar ambiente de testes isolado
- 0.6 Instalar Filament
- 0.7 Configurar i18n pt_BR, en, es

Fichas: [`features/filament-admin.md`](features/filament-admin.md), [`features/localization.md`](features/localization.md), [`testing.md`](testing.md).

### Fase 1 — Fundação obrigatória (multi-tenancy)

- 1.1 Criar `tenants`
- 1.2 Vincular `users` a `tenants`
- 1.3 Criar `TenantScope`
- 1.4 Criar trait `BelongsToTenant`
- 1.5 Criar seed inicial
- 1.6 Criar testes de isolamento multi-tenant

Ficha: [`features/multitenancy.md`](features/multitenancy.md).

### Fase 2 — Autenticação e permissões

- 2.1 Instalar/configurar Spatie Permission
- 2.2 Criar roles: `super_admin`, `tenant_admin`, `board_member`, `executive`, `guest`
- 2.3 Criar permissions base
- 2.4 Configurar policies
- 2.5 Integrar permissões ao Filament — **feito** com a Fase 3 (`TenantResource`, `UserResource`, policies)
- 2.6 Criar testes de autorização

Ficha: [`features/auth-permissions.md`](features/auth-permissions.md).

### Fase 3 — Painel administrativo inicial

- 3.1 Resource de Tenants — **feito** (`TenantResource`, policy, i18n, testes de acesso).
- 3.2 Resource de Users — **feito** (`UserResource`, `PersistPanelUserAction`, scope por tenant, i18n, testes de policy/query/action); refinamento UX/segurança: secções de formulário, `super_admin` fora da CheckboxList e sincronizado com `is_super_admin`, password na edição sem alterar hash se vazio.
- 3.3 Gestão de perfis — pendente (página ou secção dedicada, se necessário além do resource).
- 3.4 Campo de idioma do utilizador — **feito** no `UserResource` (`locale` + config `localization`).
- 3.5 Controle de status de utilizadores — **feito** no `UserResource` (`UserStatus`).
- 3.6 Auditoria inicial em utilizadores e tenants — **adiado** à Fase 4 (`audit_logs`).

Fichas: [`features/filament-admin.md`](features/filament-admin.md), [`features/audit-logs.md`](features/audit-logs.md).

### Fase 4 — Auditoria global

- 4.1 Criar `audit_logs`
- 4.2 Criar `AuditLoggerService`
- 4.3 Criar observers para models críticos
- 4.4 Resource somente leitura no Filament
- 4.5 Testes de auditoria

Ficha: [`features/audit-logs.md`](features/audit-logs.md).

### Fase 5 — Módulo de Conselhos

- 5.1 Criar `boards`
- 5.2 Criar membros do conselho
- 5.3 Definir papéis por conselho
- 5.4 Resource Filament
- 5.5 Policies por tenant e board
- 5.6 Testes

Ficha: [`features/boards.md`](features/boards.md).

### Fase 6 — Módulo de Reuniões

- 6.1 Criar `meetings`
- 6.2 Status de reunião
- 6.3 Participantes
- 6.4 Agenda/pauta
- 6.5 Convites
- 6.6 Link de videoconferência
- 6.7 Resource Filament
- 6.8 Testes

Ficha: [`features/meetings.md`](features/meetings.md).

### Fase 7 — Documentos

- 7.1 Upload de documentos
- 7.2 Storage separado por tenant
- 7.3 Controle de acesso
- 7.4 Versionamento
- 7.5 Histórico de visualização/download
- 7.6 Testes de segurança

Ficha: [`features/documents.md`](features/documents.md).

### Fase 8 — Atas

- 8.1 Criar `minutes`
- 8.2 Vincular ata à reunião
- 8.3 Status: `draft`, `under_review`, `approved`, `signed`
- 8.4 Aprovação de ata
- 8.5 Histórico de alterações
- 8.6 Testes

Ficha: a criar (`features/minutes.md`) quando a fase iniciar.

### Fase 9 — Votações

- 9.1 Criar `votes`
- 9.2 Criar opções de voto
- 9.3 Registrar votos
- 9.4 Quórum
- 9.5 Voto aberto/secreto
- 9.6 Resultado auditável
- 9.7 Testes críticos

Ficha: [`features/votes.md`](features/votes.md).

### Fase 10 — Workflows e pendências

- 10.1 Criar `tasks` + comentários + histórico — **feito**
- 10.2 Pendências por utilizador
- 10.3 Aprovações
- 10.4 Prazos
- 10.5 Notificações internas
- 10.6 Testes

Ficha: [`features/tasks.md`](features/tasks.md).

### Fase 11 — Integrações configuráveis via Admin

- 11.1 Criar `integrations` + `integration_logs` — **feito**
- 11.2 Config criptografado (`encrypted:array`) — **feito**
- 11.3 `IntegrationResource` no Filament — **feito**
- 11.4 Drivers base (fake): SMTP, Office 365, OneDrive, DocuSign, Teams, Zoom, Looker Studio — **feito**
- 11.5 Testar conexão (validação de obrigatórios) — **feito**
- 11.6 Logs de integração (sanitizados) — **feito**

Ficha: [`features/integrations.md`](features/integrations.md).

### Fase 12 — Assinatura digital

- 12.1 Fluxo interno de solicitação (Document/Minute) — **feito**
- 12.2 Integração DocuSign (fake, sem chamada externa) — **feito**
- 12.3 Status de assinatura + state machine — **feito**
- 12.4 Webhook de retorno — pendente (fase futura)
- 12.5 Auditoria (sem metadata/segredos) — **feito**
- 12.6 Testes — **feito**

Ficha: [`features/signatures.md`](features/signatures.md).

### Fase 13 — Notificações

- 13.1 Notificação por e-mail (fake, sem SMTP) — **feito**
- 13.2 Notificação interna — **feito**
- 13.3 Pendências documentais — pendente (gatilhos futuros)
- 13.4 Convites de reunião — pendente (gatilhos futuros)
- 13.5 Lembretes — pendente (gatilhos futuros)
- 13.6 Templates multi-idioma (fallback global + override por tenant) — **feito**

Ficha: [`features/notifications.md`](features/notifications.md).

### Fase 14 — Relatórios e dashboard

- 14.1 Dashboard interno — **feito** (`DashboardMetricsService`, widgets Filament, permissão `view_reports`)
- 14.2 Indicadores por módulo — **feito** (tasks, meetings, minutes, votes, signatures, notifications); documentos agregados ficam para refinamento futuro
- 14.3 Página de relatórios operacionais (`OperationalReports`) — **feito**
- 14.4 Exportação — pendente
- 14.5 Gráficos — pendente (fase futura)
- 14.6 Preparação Looker Studio — pendente

Ficha: [`features/dashboard.md`](features/dashboard.md).

### Fase 15 — Segurança avançada

- 15.1 2FA — **feito** (Filament `AppAuthentication`, `users.two_factor_*` encriptados, contratos `HasAppAuthentication`/`HasAppAuthenticationRecovery`)
- 15.2 Sessões ativas — **feito** (`auth_sessions` + `SESSION_DRIVER=database`, revogação remota via `RevokeAuthSessionAction`)
- 15.3 Logs de login — **feito** (`AuditAction::Login/Logout/FailedLogin/SessionRevoked/SessionExpired/TwoFactorEnabled/TwoFactorDisabled/PasswordChanged`)
- 15.4 Rate limiting — **feito** (login Filament + `UpdateOwnPasswordAction` 5/min + `RevokeAuthSessionAction` 30/min)
- 15.5 Políticas de senha — **feito** (`PasswordPolicyService`)
- 15.6 Revisão OWASP — **feito** (headers de segurança, CSP report-only, hardening de cookies/sessão, sanitização de auditoria, dependency scan documentado, testes)

Ficha: [`features/security.md`](features/security.md); ver também `.cursor/rules/security.mdc`.

### Fase 16 — API REST

- 16.1 Laravel Sanctum
- 16.2 Endpoints internos
- 16.3 Autorização por tenant
- 16.4 Versionamento de API
- 16.5 Documentação
- 16.6 Testes de API

Fase 16 iniciada com especificação antes de código. Ficha: [`features/api.md`](features/api.md).

### Fase 17 — Refinamento final

- 17.1 Revisão de performance
- 17.2 Índices e performance (MySQL/PostgreSQL)
- 17.3 Revisão de queries
- 17.4 Revisão de policies
- 17.5 Revisão de logs
- 17.6 Revisão de documentação
- 17.7 Testes finais

### Fase 18 — Deploy

- 18.1 Configurar ambiente produção
- 18.2 Storage
- 18.3 Queue
- 18.4 Scheduler
- 18.5 Backup
- 18.6 Monitoramento
- 18.7 Checklist de segurança

### Fase 19 — Executive Dashboard

Evolução do dashboard interno (Fase 14) para um **dashboard executivo** orientado a decisão: 1 página, ≤ 4 widgets Livewire, **um único snapshot tipado**. Sub-dividida em **19A** (foundation + implementação) e **19B** (operacionalização avançada). Detalhe técnico: [`features/dashboard.md`](features/dashboard.md), secção "Executive Dashboard (Fase 19A)".

#### Fase 19A — Foundation + implementação

- 19A.0 Documentação + rules arquitecturais (`docs/features/dashboard.md`, `docs/architecture.md`, `.cursor/rules/dashboard.mdc`, `.cursor/rules/cache.mdc`, `.cursor/rules/livewire.mdc`) — **concluída**
- 19A.1 **Formal architecture decisions** (D1–D10: TTL/`Cache::flexible`, anti-stampede, cache split per-user/per-tenant, super_admin, período via dispatch/On, 4 widgets, deferLoading, snapshot DTO `final readonly` versionado, policy filtering item-a-item, legacy `*StatsWidget` como fallback) — **em curso**. Detalhe: [`features/dashboard.md`](features/dashboard.md) → "Formal Decisions" e [`architecture.md`](architecture.md) → "Decisões formais (Fase 19A.1)"
- 19A.2 Índice DB para query **overdue** em `tasks` — **concluída**: `tasks_tenant_status_due_date_idx` (`tenant_id`, `status`, `due_date`). **Sem** índices adicionais em `meetings`, `votes`, `signature_requests`, `signature_request_signers`, `notifications_center`, `audit_logs`, `minutes`, `documents` nesta sub-fase (over-indexing evitado). Detalhe: [`features/dashboard.md`](features/dashboard.md) → Performance.
- 19A.3 DTOs imutáveis (`ExecutiveDashboardSnapshot`, `HeroSummary`, `KpiStrip`, `OperationsBlock`, `PriorityItem`, `ActivityItem`, enum `PriorityUrgency`) — `final readonly`, `config/board.php` (`dashboard.*`), testes em `tests/Unit/Dashboard/Executive/Snapshot/` — **concluída** (namespace `App\Services\Dashboard\Executive\Snapshot`)
- 19A.4 Providers internos (`HeroProvider`, `KpiStripProvider`, `OperationsProvider`, `PrioritiesProvider`, `ActivityFeedProvider`) — **concluída** (`tests/Unit/Dashboard/Executive/Providers/`)
- 19A.5 `ExecutiveDashboardReadService` orquestrador + L2 `Cache::flexible` (Hero/Operations; KPI fora do L2) — **concluída** (`ExecutiveDashboardReadService.php`, testes feature + composition).
- 19A.6 Gate único `view_executive_dashboard` registado em `AuthServiceProvider` — **concluída**
- 19A.7 4 widgets Livewire executivos (`Hero` / `KpiStrip` / `Operations` / `Priorities` com `deferLoading` em C/D) + page `Dashboard` via gate `view_executive_dashboard` + feature flag `board.dashboard.use_executive_widgets` (default `false`) para coexistência com os 6 `*StatsWidget` legacy + assets CSS `bgp-dashboard.css` + i18n `dashboard.executive.*` (pt_BR/en/es) — **concluída**
- 19A.8 Testes obrigatórios (multi-tenancy, policies por item, anti-stampede, super_admin, shape estável) — **em curso**
- 19A.9 Documentação final pós-implementação (sincronizar `features/dashboard.md`)

#### Fase 19B — Operacionalização avançada

- 19B.1 Invalidação de cache por evento (`ExecutiveDashboardCacheKeys`, `ExecutiveDashboardCacheInvalidator`, observers em `Task`/`Meeting`/`Vote`/`Minute`/`SignatureRequest`/`NotificationCenter`) — **concluída** (ver `docs/features/dashboard.md` → 19B.1)
- 19B.2 Observabilidade leve do cache executivo (`ExecutiveDashboardObservability`, comando `dashboard:cache-stats`) — **concluída** (ver `docs/execution/19B.2-dashboard-observability.md`)
- 19B.3 Projection table `tenant_dashboard_snapshots` (refresh por job a cada N minutos) para tenants enterprise
- 19B.4 Endpoint `GET /api/v1/dashboard/snapshot` com ability `dashboard:read` (requer `features/api-write.md` + OpenAPI)
- 19B.5 Pre-warm de cache por job em horário de pico
- 19B.6 Remoção dos `*StatsWidget` legacy (após validação em produção do dashboard executivo)

## Ordem obrigatória

1. Base Laravel
2. Testes isolados
3. Filament
4. i18n
5. Multi-tenancy
6. Permissões
7. Auditoria
8. Tenants/Users Admin
9. Boards
10. Meetings
11. Documents
12. Minutes
13. Votes
14. Workflows
15. Integrations
16. Reports
17. Security hardening
18. API
19. Executive Dashboard (19A → 19B)
20. Deploy
