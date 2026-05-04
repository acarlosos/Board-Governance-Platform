# Painel administrativo (Filament)

## Objetivo

Fornecer a **UI administrativa** da Board com **Filament v5**, **autorização** via Policies e **consultas** alinhadas ao tenant, sem módulos de negócio (boards, reuniões, documentos) nesta fase.

## Painel `admin`

- `App\Providers\Filament\AdminPanelProvider` regista o painel com `->id('admin')`, `->path('admin')` e **`->default()`**. Sem painel por defeito, comandos como `php artisan make:filament-user` falham com `NoDefaultPanelSetException`.
- Recursos em `app/Filament/Admin/Resources/` com descoberta automática.

## Resources (Fase 3 — base)

| Resource | Model | Visibilidade / query |
|----------|--------|----------------------|
| `App\Filament\Admin\Resources\Tenants\TenantResource` | `Tenant` | Só quem passa `TenantPolicy` (`isSuperAdmin()`). **Sem** `BelongsToTenant` no model `Tenant`. |
| `App\Filament\Admin\Resources\Users\UserResource` | `User` | `UserPolicy` + `manage_users`. `getEloquentQuery()` restringe a `tenant_id` do actor se **não** for super-admin. **Sem** trait `BelongsToTenant` no `User`. |

### Tenants

- Formulário: `name`, `slug` (único na criação; desactivado na edição), `document`, `status` (`TenantStatus`), soft deletes na tabela.
- Tabela: filtros por `status` e `TrashedFilter`.
- Rotas do resource: **403** para `tenant_admin` (policy `viewAny` negada).

### Users

- Formulário: `name`, `email`, `password` (obrigatório na criação; opcional na edição; cast `hashed` no model), `tenant_id` (só super-admin vê o campo), `locale`, `status` (`UserStatus`), `roles` (Spatie), `is_super_admin` (só super-admin).
- Persistência: `App\Actions\Filament\PersistPanelUserAction` — valida email único, força `tenant_id` / `is_super_admin` para não super-admin, limita roles atribuíveis por `tenant_admin` à lista `ROLES_ASSIGNABLE_BY_TENANT_ADMIN`, `syncRoles` após save.
- Tabela: filtros por tenant (só super-admin), estado, papel Spatie (`spatie_role`), trashed.

## Tabelas envolvidas

- `tenants`, `users`, tabelas Spatie (`roles`, `model_has_roles`, …).

## Models envolvidos

- `App\Models\Tenant`, `App\Models\User`.

## Policies envolvidas

- `TenantPolicy` — ligada ao `TenantResource` (incl. acções em massa / restore quando aplicável).
- `UserPolicy` — `viewAny` / `view` / `create` / `update` / `delete` / `restore` e variantes em massa conforme `manage_users` e `tenant_id`.

## Services / Actions envolvidos

- `App\Actions\Filament\PersistPanelUserAction` — criação e edição de utilizadores no painel com validação de servidor (tenant, roles, super-admin, password).

## Regras de negócio

- `tenant_admin` não gere tenants; não atribui `super_admin` nem `is_super_admin`.
- Password: texto plano atribuído ao atributo `password`; o cast `hashed` do `User` aplica o hash (sem dupla-hash na Action).

## Regras de segurança

- Não confiar só em campos ocultos no formulário: **Action + policy + query** alinhados.
- Listagens de `User` nunca expõem utilizadores de outro tenant a um `tenant_admin`.
- `Tenant` não usa scope global de tenant (modelo de plataforma).

## Testes relacionados

- `tests/Feature/FilamentAdminResourcesTest.php` — HTTP ao `TenantResource`, contagens na query do `UserResource`, regras da `PersistPanelUserAction` (tenant, roles, super-admin, password).
- `tests/Feature/AuthPermissionsTest.php` — policies base (incl. tenants/users).

## Traduções

- Chaves em `lang/{pt_BR,en,es}/`: `tenants.php`, `users.php`, `actions.php`; ver [localization.md](localization.md).

## Pendências futuras

- Perfil do utilizador no painel (Fase 3.3 no roadmap, se mantida).
- Auditoria em alterações a tenants/users (Fase 4 — `audit_logs`).
- Temas / branding por tenant se for requisito.
- Plugins (2FA, media library) documentados aqui quando instalados.
