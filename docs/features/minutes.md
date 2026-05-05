## Objetivo

Gerir **atas de reunião** com **versionamento**, **workflow de aprovação** e **isolamento multi-tenant**, servindo como base para **assinatura digital futura** (fora do escopo da Fase 8).

## Tabelas envolvidas

- `minutes`
- `minute_versions`
- `minute_approvals`

## Models envolvidos

- `App\Models\Minute`
- `App\Models\MinuteVersion`
- `App\Models\MinuteApproval`

## Policies envolvidas

- `App\Policies\MinutePolicy`
- `App\Policies\MinuteApprovalPolicy`

## Services / Actions envolvidos

- `App\Actions\Minutes\PersistMinuteAction`
- `App\Actions\Minutes\CreateMinuteVersionAction`
- `App\Actions\Minutes\SubmitMinuteForReviewAction`
- `App\Actions\Minutes\ApproveMinuteAction`
- `App\Actions\Minutes\RejectMinuteAction`
- `App\Actions\Minutes\ReopenRejectedMinuteAction`
- `App\Actions\Minutes\ArchiveMinuteAction`
- Auditoria: `App\Services\Audit\AuditLoggerService` (via observers)

## Regras de negócio

- Ata pertence obrigatoriamente a uma `Meeting` (`meeting_id`).
- **Workflow** (controlado apenas por Actions):
  - `draft` → `in_review` → `approved`/`rejected` → `archived`
  - `rejected` pode ser reaberta para `draft` (nova versão obrigatória para seguir adiante).
- Versionamento incremental por `minute_id` (`version_number` crescente).
- `current_version_id` aponta para a última versão criada.

## Regras de segurança

- **Isolamento multi-tenant** via `BelongsToTenant` e policies.
- **Anti cross-tenant**: `PersistMinuteAction` valida que `meeting_id` pertence ao mesmo tenant.
- **Acesso de leitura**: apenas `tenant_admin`/`manage_minutes` no tenant **ou** participantes da reunião.
- **Edição**: permitida apenas em `draft` (bloqueio em policy + Action).
- **Aprovações**: somente participantes elegíveis (vinculados à reunião no tenant) recebem `MinuteApproval`.
- **Auditoria sem conteúdo completo**: `MinuteObserver` não registra `content`; `MinuteVersionObserver` registra apenas metadados e `changes_summary`.

## Testes relacionados

- `tests/Feature/MinutesTest.php` — criação, isolamento tenant, visibilidade por participante, versionamento, workflow de revisão/aprovação/rejeição, bloqueio de edição e auditoria sem conteúdo.

## Pendências futuras

- Assinatura digital (Fase 12) e eventuais requisitos de imutabilidade legal.
- Integração opcional com `documents` (anexos formais à ata).

