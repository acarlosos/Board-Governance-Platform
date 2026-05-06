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

## Decisões técnicas pendentes de fixação

Registar aqui ou na feature correspondente quando forem tomadas (ex.: pacote de activity log vs. tabela `audit_logs` própria, estratégia exata de storage por tenant).
