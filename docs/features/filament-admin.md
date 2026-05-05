# Painel administrativo (Filament)

## Objetivo

Fornecer a **UI administrativa** da Board com **Filament v5**, **autorização** via Policies e **consultas** alinhadas ao tenant, sem módulos de negócio (boards, reuniões, documentos) nesta fase.

## Painel `admin`

- `App\Providers\Filament\AdminPanelProvider` regista o painel com `->id('admin')`, `->path('admin')` e **`->default()`**. Sem painel por defeito, comandos como `php artisan make:filament-user` falham com `NoDefaultPanelSetException`.
- Recursos em `app/Filament/Admin/Resources/` com descoberta automática.

### Formulários e modais

- **`ManageRecords`** (via `ListRecords`) aplica **`columns(2)`** no **schema raiz** dos modais de criar/editar quando o schema ainda não tem colunas custom. Com **uma única `Section`**, o bloco ficava só na **primeira** coluna da grelha do root (a segunda vazia). Nos resources, o `form()` chama **`$schema->columns(1)`** no root e a **`Section`** usa **`->columns(2)`** + **`columnSpanFull()`** para a grelha de campos ser só **dentro** da secção.
- Secções de formulário usam **`->columns(2)`** no conteúdo (Filament: `1` coluna por defeito, **`2` a partir de `lg`** — `--cols-default` / `--cols-lg` com `repeat(2, minmax(0, 1fr))`).
- As secções usam **`->contained(false)`** para o conteúdo ocupar a **largura útil do modal** (o defeito `contained` da `Section` limita a largura e deixa margem vazia à direita).
- O **schema** do formulário e a **secção** usam `extraAttributes(['class' => 'w-full min-w-0'])` e a secção **`->grow()`**, para o bloco do formulário esticar dentro do `flex` do conteúdo do modal (evita o conteúdo ficar com largura “intrínseca” à esquerda).
- Modais de **Criar** / **Editar** usam `modalWidth(Width::FiveExtraLarge)` (compromisso entre largura e leitura; o defeito do Filament é `FourExtraLarge`).

## Resources (Fase 3 — base)

| Resource | Model | Visibilidade / query |
|----------|--------|----------------------|
| `App\Filament\Admin\Resources\Tenants\TenantResource` | `Tenant` | Só quem passa `TenantPolicy` (`isSuperAdmin()`). **Sem** `BelongsToTenant` no model `Tenant`. |
| `App\Filament\Admin\Resources\Users\UserResource` | `User` | `UserPolicy` + `manage_users`. `getEloquentQuery()` restringe a `tenant_id` do actor se **não** for super-admin. **Sem** trait `BelongsToTenant` no `User`. |
| `App\Filament\Admin\Resources\AuditLogs\AuditLogResource` | `AuditLog` | **Somente leitura**. `AuditLogPolicy` + `getEloquentQuery()` restringe por `tenant_id` para não super-admin. |
| `App\Filament\Admin\Resources\Boards\BoardResource` | `Board` | `BoardPolicy` + `BelongsToTenant`. Para `board_member`, listagem restringe aos boards onde participa como membro ativo. |

### Tenants

- Formulário na **Section** «Dados do tenant» (`tenants.section_main`): `name`, `slug`, `document`, `status` (`TenantStatus`), soft deletes na tabela.
- **Slug:** na criação, preenchido automaticamente a partir de `name` (`live(onBlur)` + `Str::slug`); **único** na BD (`Rule::unique` só sem registo); na edição o campo fica **desactivado** e **não desidratado** (o valor não volta no submit do Filament), mantendo o slug original — ver teste `test_edicao_tenant_via_filament_preserva_slug`.
- Tabela: filtros por `status` e `TrashedFilter`.
- Rotas do resource: **403** para `tenant_admin` (policy `viewAny` negada).

### Users

- Formulário em **secções**: Conta (`name`, `email`, `password` com helper na edição a indicar que vazio mantém a senha), Organização (`tenant_id` **só** super-admin), Permissões (`roles` em `CheckboxList` com **2 colunas**, labels `__('roles.{nome})'`; **sem** opção `super_admin` na lista), Preferências (`locale`, `status`).
- **Super-admin da plataforma:** toggle `is_super_admin` **só** visível para quem `isSuperAdmin()`. Marcar o toggle faz `syncRoles` incluir a role Spatie `super_admin`; desmarcar remove essa role e mantém as roles da checkbox. `tenant_admin` **não** vê o toggle nem o `tenant_id`; o servidor força `tenant_id` do actor e `is_super_admin = false` e rejeita `super_admin` na lista ou no payload (validação + guards).
- `password` (obrigatório na criação; opcional na edição com `dehydrated` só quando preenchido; cast `hashed` no model).
- Persistência: `App\Actions\Filament\PersistPanelUserAction` — valida email único, `roles.*` com `Rule::in` nas roles atribuíveis (lista global sem `super_admin` para super-admin), regra **after** «pelo menos um papel ou `is_super_admin`», `syncRoles` após save.
- Tabela: filtros por tenant (só super-admin), estado, papel Spatie (`spatie_role`), trashed.

## Tabelas envolvidas

- `tenants`, `users`, tabelas Spatie (`roles`, `model_has_roles`, …).

## Models envolvidos

- `App\Models\Tenant`, `App\Models\User`.

## Policies envolvidas

- `TenantPolicy` — ligada ao `TenantResource` (incl. acções em massa / restore quando aplicável).
- `UserPolicy` — `viewAny` / `view` / `create` / `update` / `delete` / `restore` e variantes em massa conforme `manage_users` e `tenant_id`.
- `AuditLogPolicy` — leitura global para `super_admin`; leitura por tenant para `tenant_admin`; mutações negadas.
- `BoardPolicy` — gestão por `tenant_admin` / `manage_boards`; leitura para `board_member` apenas nos boards onde participa.
- `BoardMemberPolicy` — gestão por `tenant_admin` / `manage_boards`; leitura para `board_member` no contexto do board.

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

- `tests/Feature/FilamentAdminResourcesTest.php` — HTTP ao `TenantResource`, contagens na query do `UserResource`, regras da `PersistPanelUserAction` (tenant, roles, super-admin, password), opções de roles sem `super_admin`, sincronização toggle/`super_admin`, edição Livewire de tenant preservando `slug`.
- `tests/Feature/AuthPermissionsTest.php` — policies base (incl. tenants/users).

## Traduções

- Chaves em `lang/{pt_BR,en,es}/`: `tenants.php`, `users.php`, `roles.php` (rótulos dos nomes de role Spatie), `actions.php`; ver [localization.md](localization.md).

## Pendências futuras

- Perfil do utilizador no painel (Fase 3.3 no roadmap, se mantida).
- Auditoria em alterações a tenants/users (Fase 4 — `audit_logs`).
- Temas / branding por tenant se for requisito.
- Plugins (2FA, media library) documentados aqui quando instalados.
