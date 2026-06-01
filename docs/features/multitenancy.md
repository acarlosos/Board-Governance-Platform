# Multi-tenancy

## Objetivo

Garantir que **toda** a informação sensível de governança pertence a **um tenant** e que **não há vazamento** de dados entre organizações, incluindo painel Filament, API, filas e ficheiros.

## Tabelas envolvidas

| Tabela | Notas |
|--------|--------|
| `tenants` | `name`, `slug` único, `document` opcional, `status` (`TenantStatus`), `softDeletes`. |
| `users` | `tenant_id` FK nullable (legado / convites), `locale` default `pt_BR`, `status` (`UserStatus`), `is_super_admin` (flag bootstrap global — ver secção abaixo), `softDeletes`. |

_Não existe tabela de domínio “canário” na aplicação._ Os testes de `BelongsToTenant` usam o model `Tests\Support\Models\TestingTenantScopedItem` e criam a tabela `testing_tenant_scoped_items` **só em runtime** em `MultitenancyTest` (sem migration em `database/migrations`).

## Flag `is_super_admin` (bootstrap)

- Coluna booleana **`users.is_super_admin`** (default `false`): bypass de `TenantScope` e políticas alinhadas a `User::isSuperAdmin()`, que devolve **true** se a flag estiver activa **ou** se o utilizador tiver a role Spatie **`super_admin`**.
- Manter a flag como rede de segurança / contas de bootstrap até consolidar apenas roles (ver `auth-permissions.md`).
- O seed inicial (`InitialTenantSeeder`) define `is_super_admin = false` e atribui **`tenant_admin`** ao administrador do tenant.

## Models envolvidos

| Model | Notas |
|--------|--------|
| `App\Models\Tenant` | Soft deletes; `hasMany` `User`. |
| `App\Models\User` | `belongsTo` `Tenant`; **não** usa `BelongsToTenant` (evita duplo filtro; listagens de utilizadores tratam-se noutra camada na Fase 3). |
| `Tests\Support\Models\TestingTenantScopedItem` | **Apenas testes** — usa `BelongsToTenant` para validar scope e preenchimento de `tenant_id`. |

## Enums

- `App\Enums\TenantStatus`: `active`, `inactive`, `suspended`.
- `App\Enums\UserStatus`: `active`, `inactive`, `suspended`.

## Services / Actions envolvidos

- `App\Services\Tenancy\TenantResolver` — singleton: `currentId()` e `current()` com base no utilizador autenticado; devolve `null` sem sessão (seguro).
- `App\Models\Scopes\TenantScope` — global scope: filtra por `auth()->user()->tenant_id` quando há sessão e o utilizador **não** é super (`User::isSuperAdmin()` = flag `is_super_admin` **ou** role Spatie `super_admin`); sem autenticação **não** aplica filtro (migrations, `artisan db:seed`, testes sem `actingAs`).
- `App\Models\Concerns\BelongsToTenant` — regista `TenantScope` e preenche `tenant_id` no `creating` a partir do utilizador autenticado.

## Seed inicial

- `Database\Seeders\InitialTenantSeeder` — cria tenant `slug=principal` e utilizador administrador ligado ao tenant.
- Variáveis de ambiente (opcional): `SEED_ADMIN_EMAIL` (default `admin@localhost`), `SEED_ADMIN_PASSWORD` (default `AlterarEstaSenha1!`). **Alterar em produção.**
- `Database\Seeders\SuperAdminSeeder` — super administrador da plataforma sem tenant (`SEED_SUPER_ADMIN_EMAIL` default `root@localhost`, `SEED_SUPER_ADMIN_PASSWORD` default `AlterarEstaSenhaRoot1!`). Ver `auth-permissions.md`.

## Policies envolvidas

_Nesta fase ainda não há policies específicas por tenant; Fase 2/3._

## Regras de negócio

- **Resolução de tenant:** contexto a partir do utilizador autenticado (sessão); sem subdomínio nesta fase.
- Utilizadores com **`is_super_admin = true`** **ignoram** `TenantScope` (bootstrap até role `super_admin` via Spatie).
- Utilizador autenticado com `tenant_id` nulo e sem bypass: modelos com `BelongsToTenant` devolvem **conjunto vazio** (evita vazamento).
- Utilizador **tenant_admin** (futuro) e restantes roles **só** veem dados do tenant actual; `super_admin` / plataforma só onde código e policy o permitirem.
- Regras `unique` e `exists` em validações **sempre** filtradas por `tenant_id` quando aplicável.

## Regras de segurança

- Proibir `withoutGlobalScopes` aberto sem revisão e testes.
- Uploads com **prefixo ou disco lógico por tenant**; URLs não podem expor IDs de outros tenants.
- Jobs e notificações devem transportar contexto de tenant explícito.

## Testes relacionados

- `tests/Feature/MultitenancyTest.php` — criação de tenant, relação user–tenant, preenchimento automático de `tenant_id`, isolamento entre tenants, consultas sem auth, bypass com `is_super_admin`, `TenantResolver`.
- Modelo e tabela de apoio: `Tests\Support\Models\TestingTenantScopedItem` + `tests/Support/Database/Factories/TestingTenantScopedItemFactory.php` (tabela criada em `setUp` do teste).

## Pendências futuras

- Subdomínio/domínio customizado por tenant (evolução futura).
- Middleware explícito para “tenant activo na sessão” se o modelo de negócio exigir troca de tenant sem novo login.
- Resources Filament para `Tenant` / `User` com filtros explícitos (Fase 3).
- Spatie: mapear role `super_admin` e eventual deprecação da coluna `is_super_admin`.
- Documentar estratégia de backup/restore por tenant se aplicável.

## Migração de compatibilidade

- `*_rename_is_platform_admin_to_is_super_admin_on_users_table` — só corre se existir a coluna antiga `is_platform_admin` (bases já migradas na primeira versão da Fase 1).
