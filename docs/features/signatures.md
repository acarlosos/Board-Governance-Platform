# Assinatura digital (base interna)

## Objetivo

Criar o **fluxo interno** de solicitações de assinatura para **Documentos** e **Atas**, com estado, signatários e eventos, mantendo:

- **100% multi-tenant**
- regras críticas em **Actions/Policies**
- **drivers fake** (sem chamadas externas nesta fase)
- **sem vazamento** de metadata/segredos em logs, auditoria ou exceptions

## Tabelas envolvidas

- `signature_requests`
- `signature_request_signers`
- `signature_events`

## Models envolvidos

- `App\Models\SignatureRequest`
- `App\Models\SignatureRequestSigner`
- `App\Models\SignatureEvent`

## Enums

- `App\Enums\SignatureProvider`
- `App\Enums\SignatureRequestStatus` (state machine)
- `App\Enums\SignatureSignerStatus` (state machine)
- `App\Enums\SignatureEventAction`

## Policies envolvidas

- `App\Policies\SignatureRequestPolicy`
- `App\Policies\SignatureRequestSignerPolicy`
- `App\Policies\SignatureEventPolicy`

## Drivers / Factory

- `App\Signatures\Drivers\SignatureProviderDriverInterface`
- `App\Signatures\SignatureProviderDriverFactory`
- `App\Signatures\Drivers\InternalSignatureDriver` (fake)
- `App\Signatures\Drivers\FakeDocuSignSignatureDriver` (fake)
- `App\Signatures\DTO\SignatureProviderResult`

## Actions envolvidos

- `App\Actions\Signatures\PersistSignatureRequestAction`
- `App\Actions\Signatures\PersistSignatureSignerAction`
- `App\Actions\Signatures\SendSignatureRequestAction`
- `App\Actions\Signatures\SignSignatureRequestAction`
- `App\Actions\Signatures\RejectSignatureRequestAction`
- `App\Actions\Signatures\CancelSignatureRequestAction`
- `App\Actions\Signatures\RecordSignatureEventAction`

## Regras de negócio

- **Signable permitido nesta fase**: apenas `Document` e `Minute`.
- **Anti cross-tenant**:
  - `signable_type/signable_id` deve pertencer ao mesmo `tenant_id`.
  - `integration_id` (quando usado) deve ser do mesmo tenant.
- **DocuSign (fake)**:
  - `provider=docusign` exige `integration_id` **ativo** e compatível: `Integration.type=signature` + `Integration.provider=docusign`.
  - driver retorna `external_id` fake; sem webhook/email/API externa.
- **Signers**:
  - se `user_id` informado, utilizador deve ser do **mesmo tenant**
  - signatário externo (sem `user_id`) é permitido com `name` + `email` válidos

## State machine

- `SignatureRequestStatus`:
  - `draft → sent`
  - `sent → completed | cancelled | failed`
- `SignatureSignerStatus`:
  - `pending → sent`
  - `sent → signed | rejected`

## Regras de segurança

- **Não expor** em `signature_events` e `audit_logs`:
  - `metadata` completo
  - mensagens sensíveis
  - segredos/payloads
- `RecordSignatureEventAction` sanitiza `context` e limita `message`.
- Observers auditam apenas metadados seguros (sem `message`/`metadata` completos).

## Filament (Admin)

- Resource: `App\Filament\Admin\Resources\Signatures\SignatureRequestResource`
- RelationManagers:
  - `SignatureSignersRelationManager` (editar/adicionar só em `draft`)
  - `SignatureEventsRelationManager` (somente leitura)

## Testes relacionados

- `tests/Feature/SignaturesTest.php`

## Pendências futuras

- Webhooks e sincronização real com DocuSign (fase futura).
- Notificações/e-mail para signatários (Fase 13).
- Assinatura/imutabilidade legal e trilha de evidência conforme requisitos.

