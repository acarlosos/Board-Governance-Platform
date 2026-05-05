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
