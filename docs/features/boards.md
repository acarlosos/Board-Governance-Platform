# Boards (conselhos)

## Objetivo

Gerir **conselhos** (Boards) por tenant: criar boards, definir estado e gerir membros (papéis/estado/entrada/saída), com isolamento multi-tenant, policies e auditoria.

## Tabelas envolvidas

- `boards`
- `board_members`

## Models envolvidos

- `App\Models\Board` — `BelongsToTenant`, `SoftDeletes`, `status` (`BoardStatus`).
- `App\Models\BoardMember` — `BelongsToTenant`, `SoftDeletes`, `role` (`BoardMemberRole`), `status` (`BoardMemberStatus`), `joined_at`/`left_at`.

## Policies envolvidas

- `App\Policies\BoardPolicy`
  - `super_admin` vê e gere tudo.
  - `tenant_admin` e utilizadores com `manage_boards` gerem boards do próprio tenant.
  - `board_member` vê apenas boards onde é membro **ativo**.
- `App\Policies\BoardMemberPolicy`
  - `super_admin` vê e gere tudo.
  - `tenant_admin` e `manage_boards` gerem membros no próprio tenant.
  - `board_member` pode ver membros do board onde participa.

## Services / Actions envolvidos

- `App\Actions\Boards\PersistBoardAction` — validação/guards de `tenant_id` e persistência de board.
- `App\Actions\Boards\ArchiveBoardAction` — marca board como `archived`.
- `App\Actions\Boards\PersistBoardMemberAction` — criação/edição/remoção com validações críticas:
  - utilizador deve pertencer ao mesmo tenant do board
  - impedir duplicidade de membro **ativo** no mesmo board
  - reativação de registo soft-deleted para respeitar unique (`tenant_id`, `board_id`, `user_id`)

## Regras de negócio

- `Board.status`: `active`, `inactive`, `archived`.
- Membro (`BoardMember`) tem `role` e `status` (`active`/`inactive`) e datas opcionais `joined_at` / `left_at`.
- Um utilizador não pode existir como **membro ativo duplicado** no mesmo board.

## Regras de segurança

- **Isolamento multi-tenant:** `Board` e `BoardMember` usam `BelongsToTenant` (global scope por `tenant_id`).
- **Anti-vazamento:** policies validam tenant e `BoardResource::getEloquentQuery()` restringe listagem de `board_member` aos boards onde participa.
- **Backend-first:** RelationManager do Filament delega persistência a `PersistBoardMemberAction` (não depende só do formulário).

## Testes relacionados

- `tests/Feature/BoardsTest.php` — isolamento por tenant (create/query), policies (permit/deny), auditoria (Board/BoardMember), bloqueio de membro cross-tenant e duplicidade de membro ativo.

## Pendências futuras

- Meetings: relação `Board -> meetings` será adicionada na Fase 6 (não criar ainda).
- Permissões mais finas por board (ex.: roles por board) se o domínio exigir.
