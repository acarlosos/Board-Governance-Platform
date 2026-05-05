# Notificações e templates

## Objetivo

Criar uma base central de **notificações internas** (canal `database`) e **templates multi-idioma** com override por tenant, preparando o envio futuro por e-mail/SMTP sem enviar e-mail real nesta fase.

## Tabelas envolvidas

- `notification_templates`
- `notifications_center`
- `notification_logs`

## Models envolvidos

- `App\Models\NotificationTemplate`
- `App\Models\NotificationCenter`
- `App\Models\NotificationLog`

## Enums

- `App\Enums\NotificationChannel` (`database`, `email`)
- `App\Enums\NotificationTemplateStatus` (`active`, `inactive`)
- `App\Enums\NotificationStatus` (`unread`, `read`, `sent`, `failed`)
- `App\Enums\NotificationLogStatus` (`success`, `failed`)

## Policies envolvidas

- `App\Policies\NotificationTemplatePolicy`
- `App\Policies\NotificationCenterPolicy`
- `App\Policies\NotificationLogPolicy`

## Services / Drivers / DTO

- `App\Services\Notifications\NotificationTemplateRenderer` — substituição simples `{{ var }}`
- `App\Services\Notifications\NotificationTemplateResolver` — override por tenant e fallback global
- Drivers (fake):
  - `DatabaseNotificationDriver`
  - `FakeEmailNotificationDriver`
- Factory: `NotificationChannelDriverFactory`
- DTO: `NotificationSendResult`

## Actions

- `PersistNotificationTemplateAction` — cria/edita template (tenant_admin não cria/edita global)
- `CreateNotificationAction` — cria notificação para utilizador do tenant; valida `related` contra whitelist (`Task`, `SignatureRequest`, `Minute`, `Vote`, `Document`, `Meeting`)
- `SendNotificationAction` — envia (fake) e grava log sanitizado
- `MarkNotificationAsReadAction` — utilizador marca a própria como lida
- `RecordNotificationLogAction` — log append-only com sanitização

## Regras críticas

- Notificação (`notifications_center`) **sempre** pertence a um tenant.
- `user_id` deve ser do **mesmo tenant**.
- `related_type/related_id` (se informado) deve ser do **mesmo tenant** e `related_type` só aceita classes na whitelist: `Task`, `SignatureRequest`, `Minute`, `Vote`, `Document`, `Meeting` (caso contrário `ValidationException`).
- Templates globais (`tenant_id = null`) funcionam como **fallback**, mas `tenant_admin` **não edita** templates globais.
- Override: template do tenant sobrescreve global por `key + locale + channel`.
- Nesta fase **não envia e-mail real** e **não dispara automaticamente** eventos a partir de Tasks/Signatures/Minutes/Votes.

## Segurança

- `metadata/context/logs` são **sanitizados** (sem tokens, secrets, payloads, body/subject completos).
- Auditoria não registra body completo nem metadata sensível.

## Filament (Admin)

- `NotificationTemplateResource` — CRUD com fallback/global visível e bloqueio de edição no backend.
- `NotificationCenterResource` — leitura + ações: marcar como lida e reenviar (fake) para canal email.

## Seeders

- `Database\Seeders\NotificationTemplatesSeeder` — templates globais base (`pt_BR/en/es`):
  - `task_assigned`
  - `signature_requested`
  - `minute_review_requested`
  - `vote_opened`

## Testes relacionados

- `tests/Feature/NotificationsTest.php`

## Pendências futuras

- Disparos automáticos (Fase 13/14 conforme roadmap) com regras de negócio claras.
- Integração real SMTP por tenant via módulo Integrations.

