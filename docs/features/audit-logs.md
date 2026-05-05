# Auditoria (audit logs)

## Objetivo

Manter **trilho imutável** (ou append-only) das ações críticas de governança: quem fez o quê, quando, em que tenant e sobre que recurso, para compliance e investigação.

## Tabelas envolvidas

- `audit_logs`
  - `tenant_id` (nullable) — tenant associado ao evento quando aplicável.
  - `user_id` (nullable) — actor autenticado quando existir.
  - `action` — string (`created`, `updated`, etc; ver `AuditAction`).
  - `auditable_type` / `auditable_id` — alvo auditado (morph).
  - `old_values` / `new_values` — JSON com alterações **filtradas por allowlist** e **sem** campos sensíveis.
  - `ip_address` / `user_agent` — metadados de request quando disponíveis.
  - `created_at` — momento do evento (não há `updated_at`).

## Models envolvidos

- `App\Models\AuditLog` — sem `updated_at`, sem soft delete; modelo de leitura.
- `App\Models\Tenant`, `App\Models\User` — auditados via observers.

## Policies envolvidas

- `App\Policies\AuditLogPolicy`
  - `super_admin` pode ver tudo.
  - `tenant_admin` pode ver apenas logs com `tenant_id` igual ao seu.
  - Nenhum utilizador pode criar/editar/apagar via painel (deny).

## Services / Actions envolvidos

- `App\Services\Audit\AuditLoggerService` — ponto único para persistência de logs com sanitização (remoção de chaves sensíveis) e captura de actor/tenant/IP/UA.
- Observers:
  - `App\Observers\TenantObserver`
  - `App\Observers\UserObserver`

## Regras de negócio

- Ações críticas (ex.: fecho de votação, assinatura, eliminação lógica sensível) **devem** gerar entrada de auditoria.
- Conteúdo do log: metadados suficientes sem gravar dados pessoais ou segredos desnecessários.
- **Tenant:** auditar alterações de `name`, `slug`, `document`, `status`.
- **User:** auditar alterações de `name`, `email`, `tenant_id`, `locale`, `status`, `is_super_admin` (nunca `password`).
- **Board:** auditar alterações de `name`, `description`, `status`, `tenant_id`, `created_by`.
- **BoardMember:** auditar alterações de `board_id`, `user_id`, `role`, `status`, `joined_at`, `left_at` (e `tenant_id`).
- **Meeting:** auditar alterações de `board_id`, `title`, `description`, `scheduled_at`, `starts_at`, `ends_at`, `video_conference_url`, `status`, `tenant_id`, `created_by`.
- **MeetingParticipant:** auditar alterações de `meeting_id`, `user_id`, `role`, `status`, `responded_at` (e `tenant_id`).
- **MeetingAgendaItem:** auditar alterações de `meeting_id`, `title`, `description`, `order_column`, `status` (e `tenant_id`).
- **Minute:** auditar alterações de `meeting_id`, `title`, `status`, `current_version_id`, `created_by` (nunca `content`).
- **MinuteVersion:** registrar evento `version_created` com `version_number` e `changes_summary` (nunca `content`).
- **MinuteApproval:** registrar evento `approval_created` / `approval_updated` com `user_id`, `status`, `approved_at`/`rejected_at`.
- **Vote:** auditar alterações de `meeting_id`, `title`, `description`, `type`, `status`, `quorum_required`, `starts_at`, `ends_at`, `created_by`.
- **VoteOption:** registrar eventos `option_created` / `option_updated` (metadados).
- **VoteResponse:** registrar evento `vote_cast` com `vote_option_id` e `voted_at` (nunca `comment`).
- **Integration:** auditar `type`, `provider`, `name`, `status`, `last_test_*` (nunca `config`). Se config mudar, registrar apenas `config_changed` e `config_changed_keys` (sem secrets).
- **SignatureRequest:** auditar `signable_type`, `signable_id`, `provider`, `integration_id`, `title`, `status`, `requested_*`, `external_id` (nunca `message` nem `metadata` completos).
- **SignatureRequestSigner:** auditar `signature_request_id`, `user_id`, `name`, `email`, `status`, `signing_order`, `signed_at`, `rejected_at`, `external_id` (nunca `metadata` completo).
- **NotificationTemplate:** auditar `key`, `locale`, `channel`, `status` e flags de alteração (nunca `body` completo; usar `content_changed`).
- **NotificationCenter:** auditar `user_id`, `channel`, `status`, `read_at`, `sent_at`, `related_*` (nunca `title/body/metadata` completos).

## Regras de segurança

- Logs **sem** passwords, tokens ou conteúdo integral de documentos classificados, salvo requisito legal documentado.
- Acesso à listagem de auditoria sujeito a policy e tenant.
- **Anti-loop:** `AuditLog` não é auditado.

## Testes relacionados

- `tests/Feature/AuditLogsTest.php`
  - criação/edição de `Tenant` e `User` gera log
  - não regista `password`
  - isolamento de leitura por tenant (resource/policy)
  - política bloqueia mutações
  - `AuditLog` não gera loop

## Pendências futuras

- Log de `login` / `logout`: preparado no enum (`AuditAction`) mas só activar quando o acoplamento estiver definido (listeners de auth, Filament login, etc.).
- Exportação/retention conforme requisitos legais (GDPR/LGPD, etc.) — documentar quando houver ticket.
