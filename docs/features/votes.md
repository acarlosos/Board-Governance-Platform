# Votes (votações)

## Objetivo

Gerir **votações** vinculadas a **reuniões**, com opções e respostas, **voto único**, **voto aberto/secreto**, máquina de estados e auditoria, com isolamento multi-tenant.

## Tabelas envolvidas

- `votes`
- `vote_options`
- `vote_responses`

## Models envolvidos

- `App\Models\Vote`
- `App\Models\VoteOption`
- `App\Models\VoteResponse`

## Policies envolvidas

- `App\Policies\VotePolicy`
- `App\Policies\VoteOptionPolicy`
- `App\Policies\VoteResponsePolicy`

## Services / Actions envolvidos

- `App\Actions\Votes\PersistVoteAction`
- `App\Actions\Votes\PersistVoteOptionAction`
- `App\Actions\Votes\OpenVoteAction`
- `App\Actions\Votes\CloseVoteAction`
- `App\Actions\Votes\CancelVoteAction`
- `App\Actions\Votes\CastVoteAction`
- `App\Actions\Votes\NotifyVoteOpenedParticipantsAction` — notifica participantes activos ao abrir (chamada por `OpenVoteAction`)

## Notificações ao abrir

- Ao passar para `open`, `OpenVoteAction` invoca `NotifyVoteOpenedParticipantsAction`.
- Destinatários: participantes da reunião com status **convidado** ou **confirmado** (`MeetingParticipant::active()`), **excepto** quem abriu a votação.
- Canal `database`; template `vote_opened` (global, override por tenant); `related` = `Vote`.
- Locale do destinatário (`users.locale`, fallback `pt_BR`). Título fallback: `votes.notifications.opened_for_participant`.
- Falha ao notificar um destinatário não reverte a abertura da votação.

## Regras de negócio

- Votação pertence obrigatoriamente a uma `Meeting` (`meeting_id`).
- Apenas participantes da reunião podem votar.
- Um utilizador só pode votar uma vez por votação (`unique tenant_id + vote_id + user_id`).
- Máquina de estados (`VoteStatus`): `draft` → `open` → `closed`, e `draft|open` → `cancelled`.
- Abertura exige pelo menos 2 opções (geridas na ação **Opções de voto** na listagem, só em rascunho).
- Período opcional de votação: respeitar `starts_at` / `ends_at` quando preenchidos.

## Regras de segurança

- **Isolamento multi-tenant** via `BelongsToTenant` e `TenantScope`.
- **Anti cross-tenant**: `PersistVoteAction` valida que `meeting_id` pertence ao mesmo tenant.
- **Voto secreto**: identidade (`user_id`) não deve aparecer em listagens comuns; apenas `super_admin` pode ver identidade nas respostas.
- Regras críticas ficam em **Actions/Policies**, não no Filament.

## Testes relacionados

- `tests/Feature/VotesTest.php` — multi-tenancy, transições, abertura com 2 opções, voto único, período, opção pertencente à votação e auditoria (`vote_cast` sem comment).
- `tests/Feature/Votes/VoteOpenedNotificationsTest.php` — notificações ao abrir (destinatários, declined, isolamento tenant).

## Pendências futuras

- Cálculo/validação de quórum e resultados agregados por opção na UI.
