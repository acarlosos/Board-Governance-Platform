# Autenticação e permissões

## Objetivo

Controlar **quem** pode fazer **o quê** com **Spatie Laravel Permission** (`roles` / `permissions` globais, guard `web`), mantendo o **isolamento de dados** por `tenant_id`, `TenantScope` e policies. A flag `users.is_super_admin` continua como **bootstrap** até consolidar só role `super_admin`.

## Pacote

- `spatie/laravel-permission` (^7.4) — `teams` **desligado**; sem team permissions nesta fase.

## Tabelas envolvidas

- Tabelas Spatie publicadas: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` (migration `*_create_permission_tables.php`).
- `users` — relação morph com roles; `is_super_admin`; `tenant_id`.

## Models envolvidos

- `User` — trait `Spatie\Permission\Traits\HasRoles`; métodos `isSuperAdmin()`, `hasTenantAccess(Tenant)`, `shouldBypassTenantScope()` (via `isSuperAdmin()`).
- Models Spatie: `Spatie\Permission\Models\Role`, `Permission` (config em `config/permission.php`).

## Roles iniciais

| Role | Uso resumido |
|------|----------------|
| `super_admin` | Todas as permissões; equivalente à flag `is_super_admin` para bypass de tenant. |
| `tenant_admin` | Gestão no tenant **sem** `manage_tenants`. |
| `board_member` | `view_reports`, `manage_meetings`. |
| `executive` | `manage_meetings`, `manage_documents`, `manage_votes`, `view_reports`. |
| `guest` | `view_reports`. |

## Permissões iniciais

`manage_tenants`, `manage_users`, `manage_boards`, `manage_meetings`, `manage_documents`, `manage_votes`, `manage_minutes`, `view_reports`, `manage_settings`.

Mapeamento exacto: `Database\Seeders\RolesAndPermissionsSeeder`.

## Policies

| Policy | Regras principais |
|--------|-------------------|
| `TenantPolicy` | `viewAny` / CRUD: **só** `isSuperAdmin()` (flag ou role `super_admin`). A permissão `manage_tenants` **não** concede acesso a tenants (evita `tenant_admin` com permissão atribuída por engano). |
| `UserPolicy` | Requer `manage_users`; fora de `isSuperAdmin()`, só utilizadores **do mesmo `tenant_id`**. |

## Seeders

- `RolesAndPermissionsSeeder` — cria permissões e roles e faz `syncPermissions`.
- `DatabaseSeeder` — `RolesAndPermissionsSeeder` **antes** de `InitialTenantSeeder`.
- `InitialTenantSeeder` — administrador inicial recebe role `tenant_admin`.

## Regras de negócio

- Autorização no servidor (Policies + `can()`); UI não substitui checagens.
- Roles são **globais**; o limite por tenant em listagens vem de **queries** + `UserPolicy` (Filament ajusta-se na Fase 3).

## Regras de segurança

- Não enumerar utilizadores de outro tenant: `UserPolicy::view` / `update` / `delete` validam `tenant_id`.
- `is_super_admin` e role `super_admin` são equivalentes para `isSuperAdmin()` até unificar num único mecanismo.

## Testes relacionados

- `tests/Feature/AuthPermissionsTest.php` — seeder, tenants, users, guest, bootstrap e role `super_admin`.

## Pendências futuras

- 2FA, SSO se requisito.
- Filament: `modifyQueryUsing` em resources para alinhar listagens ao tenant.
- Opcional: remover dependência da coluna `is_super_admin` quando só roles bastarem.
