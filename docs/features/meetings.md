# Meetings (reuniões)

## Objetivo

Gerir **reuniões** por board (conselho), com participantes e pauta, estado (máquina de estados), link de videoconferência (apenas URL), isolamento multi-tenant, auditoria e painel Filament. No admin: **Criar**, **Editar**, **Participantes** e **Pauta** usam **modal** na listagem (RelationManagers embebidos via Livewire).

## Tabelas envolvidas

- `meetings`
- `meeting_participants`
- `meeting_agenda_items`

## Models envolvidos

- `App\Models\Meeting` — `BelongsToTenant`, `SoftDeletes`, `status` (`MeetingStatus`), datas (`scheduled_at`, `starts_at`, `ends_at`).
- `App\Models\MeetingParticipant` — `BelongsToTenant`, `SoftDeletes`, `role`/`status` (enums), `responded_at`.
- `App\Models\MeetingAgendaItem` — `BelongsToTenant`, `SoftDeletes`, `status` (enum), `order_column`.

## Policies envolvidas

- `App\Policies\MeetingPolicy`
  - `super_admin` pode tudo.
  - `tenant_admin` / `manage_meetings` gerem no próprio tenant.
  - `board_member` visualiza apenas reuniões do board onde é membro ativo.
  - participante visualiza reunião onde está vinculado.
- `App\Policies\MeetingParticipantPolicy`, `App\Policies\MeetingAgendaItemPolicy` — mesmas bases de isolamento e visibilidade; mutações apenas para `tenant_admin` / `manage_meetings` no tenant.

## Services / Actions envolvidos

- `App\Actions\Meetings\PersistMeetingAction` — valida `tenant_id` (autofill p/ não-super), valida `board_id` no mesmo tenant; na **criação**, em transacção, chama `SyncMeetingParticipantsFromBoardAction`.
- `App\Actions\Meetings\SyncMeetingParticipantsFromBoardAction` — para cada membro **activo** do `board_id` da reunião, cria `MeetingParticipant` via `PersistMeetingParticipantAction` (estado `invited`, papel mapeado por `App\Support\Meetings\BoardMemberToMeetingParticipantRole`).
- Transições de status (controladas por actions):
  - `StartMeetingAction` (`scheduled` -> `in_progress`)
  - `CompleteMeetingAction` (`in_progress` -> `completed`)
  - `CancelMeetingAction` (`draft|scheduled` -> `cancelled`)
- `PersistMeetingParticipantAction` — impede participante de outro tenant e duplicidade de participante “ativo” (`invited|confirmed`).
- `PersistMeetingAgendaItemAction` — itens de pauta apenas no tenant da reunião.

## Regras de negócio

- `MeetingStatus`: `draft`, `scheduled`, `in_progress`, `completed`, `cancelled`.
- Transições permitidas (enforced):
  - `scheduled` -> `in_progress`
  - `in_progress` -> `completed`
  - `draft|scheduled` -> `cancelled`
- Participante “ativo” na reunião = status em `invited` ou `confirmed` (não pode duplicar); no Filament, o select de **Criar participante** exclui utilizadores já activos (`MeetingParticipant::activeUserIdsForMeetingSubquery`).
- **Criação da reunião:** participantes iniciais = todos os `board_members` com `status` **active** do conselho seleccionado. Mapeamento de papéis: `chairperson`→`chairperson`, `secretary`→`secretary`, `member`→`participant`, `observer`→`guest`. Conselho sem membros activos → reunião sem participantes (atas em revisão exigem participantes adicionados manualmente ou membros no conselho antes de criar). **Edição** da reunião (incl. mudar `board_id`) **não** reimporta membros.
- Membro activo do conselho continua a poder **ver** a reunião pela policy mesmo sem registo em `meeting_participants`; a sincronização na criação alinha convites/atas com a composição do conselho.

## Regras de segurança

- **Isolamento multi-tenant:** `Meeting`, `MeetingParticipant`, `MeetingAgendaItem` usam `BelongsToTenant` (global scope).
- **Anti-vazamento:** `board_id` validado no backend (mesmo tenant), `user_id` de participante também; RelationManagers delegam para Actions.
- **Backend-first:** regras críticas (cross-tenant, duplicidade, transições) ficam em Actions/Policies.

## Testes relacionados

- `tests/Feature/MeetingsTest.php` — isolamento multi-tenant, bloqueio cross-tenant (board/participante), transições válidas/invalidas, visibilidade de `board_member`/participante, auditoria (created/status_changed), sincronização de participantes na criação (activos/inactivos/conselho vazio).
- `tests/Unit/Support/Meetings/BoardMemberToMeetingParticipantRoleTest.php` — mapeamento de papéis conselho→reunião.

## Pendências futuras

- Botão «Importar membros do conselho» na edição da reunião ou ao mudar `board_id`.
- Convites/notificações (e-mail, calendário) — fora de escopo.
- Integração real de videoconferência — fora de escopo (aqui é apenas URL).
