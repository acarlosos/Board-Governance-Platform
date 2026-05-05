# Integrações

## Objetivo

Permitir que cada **tenant** configure integrações externas (por provedor) no painel Admin, com **config criptografado**, **drivers fake** nesta fase (sem chamadas externas) e **logs/auditoria** sem vazamento de segredos.

## Tabelas envolvidas

- `integrations`
- `integration_logs`

## Models envolvidos

- `App\Models\Integration`
- `App\Models\IntegrationLog`

## Enums

- `App\Enums\IntegrationType`
- `App\Enums\IntegrationProvider`
- `App\Enums\IntegrationStatus`
- `App\Enums\IntegrationTestStatus`
- `App\Enums\IntegrationLogAction`

## Policies envolvidas

- `App\Policies\IntegrationPolicy`
- `App\Policies\IntegrationLogPolicy`

## Services / Drivers envolvidos

- `App\Integrations\IntegrationConfigSchemaRegistry` — schema por provider (required/secret)
- `App\Integrations\IntegrationDriverFactory`
- `App\Integrations\Drivers\*IntegrationDriver` — drivers **fake**
- `App\Integrations\DTO\IntegrationTestResult`

## Actions envolvidos

- `App\Actions\Integrations\PersistIntegrationAction`
- `App\Actions\Integrations\TestIntegrationAction`
- `App\Actions\Integrations\EnableIntegrationAction`
- `App\Actions\Integrations\DisableIntegrationAction`
- `App\Actions\Integrations\RecordIntegrationLogAction`

## Regras de negócio

- **Multi-tenancy:** `Integration`/`IntegrationLog` sempre pertencem a um `tenant_id` e listagens são filtradas por tenant (exceto `super_admin`).
- **Config criptografado:** `Integration::config` usa cast `encrypted:array`.
- **Teste (fase 11):** drivers são **fake**, validam apenas campos obrigatórios por provider e retornam `IntegrationTestResult`.
- **Ativação:** só pode ativar (`active`) após `last_test_status = success`.
- **Edição de secrets:** campo secret vazio na edição **mantém** o valor existente.

## Regras de segurança

- **Segredos nunca aparecem** em:
  - `integration_logs.context/message`
  - `audit_logs`
  - tabelas do Filament
  - exceções ou respostas
- `integration_logs` sanitiza chaves sensíveis (`password`, `client_secret`, `private_key`, `token`, etc).
- Auditoria (`IntegrationObserver`) **não** registra `config`; no máximo marca `config_changed` e `config_changed_keys` sem incluir secrets.

## Filament (Admin)

- Resource: `App\Filament\Admin\Resources\Integrations\IntegrationResource`
- RelationManager: `IntegrationLogsRelationManager` (somente leitura)
- Campos de secret no formulário usam `password` + helper “deixe em branco para manter”.

## Testes relacionados

- `tests/Feature/IntegrationsTest.php`

## Pendências futuras

- Separar `manage_integrations` por tipo (email/storage/signature/etc) se necessário.
- Implementar chamadas reais por provider (somente quando a fase abrir).
- Endpoint/API pública para integração (quando Fase 16 abrir).
- Assinatura digital: `provider=docusign` pode ser usado por `signature_requests` (Fase 12).

