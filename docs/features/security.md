# Segurança Avançada

## Objetivo

Camada corporativa de segurança da Board Governance Platform: 2FA TOTP, sessões auditáveis com revogação remota, auditoria de autenticação, política de senha e rate limiting de operações sensíveis. Toda a regra crítica vive em Actions/Policies; a UI Filament apenas delega.

## Decisões

- **2FA**: provider nativo do Filament `Filament\Auth\MultiFactor\App\AppAuthentication` (Google2FA + QRCode), com `recoverable()` ligado. Não reimplementamos TOTP.
- **Sessões**: `SESSION_DRIVER=database` (tabela `sessions` padrão do Laravel). Permite **invalidação remota** real ao apagar a row da `sessions`. A tabela `auth_sessions` é o histórico auditável associado por `session_id`.
- **Auditoria**: estendemos `AuditAction` com `failed_login`, `two_factor_enabled`, `two_factor_disabled`, `session_revoked`, `session_expired`, `password_changed`. Listeners auto-descobertos pelo Laravel 11+ (sem `Event::listen` manual para evitar registos duplicados).
- **Política de senha**: mínimo 8 caracteres, com letras maiúsculas e minúsculas, números e símbolos.
- **API (Fase 16)**: a API v1 usa Sanctum (PAT) e audita `api_login`, `api_logout`, `token_created`, `token_revoked` sem expor token/`Authorization`. Especificação: `docs/features/api.md`.

## Tabelas envolvidas

- `users` — colunas `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` (todas anuláveis e **encriptadas** via cast no model).
- `auth_sessions` — `tenant_id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `last_activity_at`, `status` (`active|closed|expired`), `created_at`. Índices em `(tenant_id, status)`, `(user_id, status)`, `session_id` e índices de atividade `(tenant_id, status, last_activity_at)` / `(user_id, status, last_activity_at)`.
- `sessions` (padrão Laravel) — usada quando `SESSION_DRIVER=database`. A revogação remota apaga a row correspondente.
- `audit_logs` — recebe os novos `AuditAction` com **chaves sensíveis sanitizadas** pelo `AuditLoggerService`.

## Models envolvidos

- `App\Models\User` — implementa `Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication` e `HasAppAuthenticationRecovery`. Casts `encrypted` / `encrypted:array` em secret e recovery codes; `two_factor_secret` e `two_factor_recovery_codes` ocultos via `#[Hidden]`.
- `App\Models\AuthSession` — registo auditável **tenant-scoped** via `BelongsToTenant` + `TenantScope`. Casts `status => AuthSessionStatus`, datas `datetime`.

## Enums

- `App\Enums\AuthSessionStatus`: `active`, `closed`, `expired`.
- `App\Enums\AuditAction`: estendido com `FailedLogin`, `TwoFactorEnabled`, `TwoFactorDisabled`, `SessionRevoked`, `SessionExpired`, `PasswordChanged`.

## Policies

- `App\Policies\AuthSessionPolicy`:
 - `view` / `revoke`:
 - `super_admin` → qualquer sessão;
 - `tenant_admin` ou utilizador com `manage_security` → sessões do mesmo `tenant_id`;
 - utilizador comum → apenas as próprias sessões.

## Permissões

- Nova permissão Spatie `manage_security`, atribuída ao `super_admin` e ao `tenant_admin` por `RolesAndPermissionsSeeder`.

## Services / Actions

- `App\Services\Security\AuthSessionService`:
 - `recordLogin($user)` — cria `auth_sessions` activa e log `login`.
 - `recordLogout($user)` — fecha a sessão activa correspondente e log `logout`.
 - `recordFailedLogin($email)` — log `failed_login` (sem persistir credenciais).
 - `touchActivity($sessionId)` — atualiza `last_activity_at` (chamado pelo middleware no máximo 1×/min).
 - `resolveVisibleSessionForActor($actor, $sessionId)` — **resolve sessão por escopo antes de autorizar** (anti-enumeração e anti cross-tenant).
 - `revoke(AuthSession, User)` — **idempotente**: se activa, fecha `auth_sessions`; sempre tenta remover a row em `sessions` quando `SESSION_DRIVER=database`.
 - `expire(AuthSession)` — marca como `expired` + log.
- `App\Services\Security\PasswordPolicyService` — fonte única das regras de senha (`rules()` para Validator, `validate(string)` boolean).
- `App\Actions\Security\RevokeAuthSessionAction` — exige permissão (Policy) e aplica rate limit (30 por minuto por actor).
- `App\Actions\Security\UpdateOwnPasswordAction` — valida senha actual, aplica `PasswordPolicyService`, exige confirmação, persiste o novo hash e aplica rate limit (5 por minuto por user).
- `App\Actions\Filament\PersistPanelUserAction` — passou a usar `PasswordPolicyService` no create/update.

## Listeners (auto-discovery Laravel 11+)

- `App\Listeners\Security\LogSuccessfulLogin` (`Illuminate\Auth\Events\Login`).
- `App\Listeners\Security\LogSuccessfulLogout` (`Illuminate\Auth\Events\Logout`).
- `App\Listeners\Security\LogFailedLogin` (`Illuminate\Auth\Events\Failed`).

`UserObserver` detecta transições de `two_factor_secret` e mudanças de `password` para registar `two_factor_enabled/disabled` e `password_changed`.

## Painel Filament

- `App\Providers\Filament\AdminPanelProvider`:
 - `->multiFactorAuthentication([AppAuthentication::make()->recoverable()])` activa 2FA TOTP nativo.
 - Middleware `App\Http\Middleware\TouchAuthSessionActivity` adicionado ao painel para actualizar `last_activity_at`.
- `App\Filament\Admin\Pages\SecuritySettings`:
 - Secção 2FA usa `Filament::getMultiFactorAuthenticationProviders()->getManagementSchemaComponents()` (sem código duplicado).
 - Secção “Trocar senha” delega para `UpdateOwnPasswordAction`.
 - Secção “Sessões ativas” lista por permissão (próprias / tenant / global) e revoga via `RevokeAuthSessionAction` **sem carregar por ID fora de escopo**.

## Regras de negócio

- `tenant_admin` **nunca** vê/revoga sessões fora do próprio tenant (Policy + filtro de query).
- `super_admin` pode operar globalmente.
- Utilizadores comuns só vêem/encerram as próprias sessões.
- A revogação remota só elimina a `sessions` row quando o driver é `database`; caso contrário marca apenas `auth_sessions` como `closed`.

### Resolver sessão por escopo antes de autorizar (Fase 15.6)

- Nunca usar `AuthSession::find($id)` em fluxos de UI/domínio.
- Usar `AuthSessionService::resolveVisibleSessionForActor()` para:
  - `super_admin`: pode resolver qualquer sessão.
  - `tenant_admin`/`manage_security`: apenas `tenant_id` do actor.
  - utilizador comum: apenas `user_id` do actor.
- Quando não encontrar, retornar erro **genérico** (sem vazar existência).

### Auditoria: actor vs target (Fase 15.6)

- O **target** é o `User` modificado (auditable).
- O **actor** vem de `auth()->user()` quando existir (ex.: admin alterando outro user, ou o próprio user em self-service).
- Nunca persistir password/OTP/secrets/recovery codes (sanitização central em `AuditLoggerService`).

## Regras de segurança (anti-vazamento)

- `two_factor_secret` e `two_factor_recovery_codes` são **encriptados em repouso** (cast `encrypted`/`encrypted:array`) e **ocultos** na serialização (`#[Hidden]`).
- `AuditLoggerService::SENSITIVE_KEYS` já sanitizava `password`, `two_factor_secret`, `two_factor_recovery_codes`, `token`, `secret`, etc. — mantido.
- Fase 15.6 ampliou a lista de sanitização (ex.: `otp`, `recovery_code(s)`, `authorization`, `client_secret`, `private_key`).
- Os listeners de Login/Logout/Failed nunca persistem credenciais; `Failed` recebe apenas o `email` informado.
- Migrations apenas adicionam colunas opcionais; não há rollback destrutivo.

## Headers de segurança (Fase 15.6)

Middleware `App\Http\Middleware\SecurityHeadersMiddleware` (aplicado ao grupo `web`, incluindo `/admin`) adiciona:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: política básica e conservadora
- `Content-Security-Policy-Report-Only`: CSP inicial em **report-only** para evitar quebra do Filament/Vite (pendente endurecer para enforce após observar reports)

## Dependency scan (Fase 15.6)

- `composer security:audit` → `composer audit`
- `npm run security:audit` → `npm audit`

## Rate limiting

- `UpdateOwnPasswordAction`: 5 tentativas/minuto por utilizador (`RateLimiter::hit/clear`).
- `RevokeAuthSessionAction`: 30 tentativas/minuto por actor.
- O throttling do login do Filament continua activo (já vinha do `Filament\Auth\Pages\Login`).

### Consolidação (Fase 15.6)

- **Login + desafio 2FA (Filament)**: o Filament aplica throttling interno nas páginas de auth e no challenge MFA. Limites exactos dependem da versão do Filament; manter como **primeira barreira** e complementar com as Actions do domínio quando aplicável.
- **Troca de senha (domínio)**: `UpdateOwnPasswordAction` limita a 5 tentativas / 60s por utilizador (`password-update:{userId}`).
- **Revogação de sessão (domínio)**: `RevokeAuthSessionAction` limita a 30 tentativas / 60s por actor (`session-revoke:{actorId}`).
- **Endpoints sensíveis futuros (API)**: padronizar `RateLimiter` por actor/tenant e documentar por endpoint antes de expor rotas públicas.

## Operação / `.env`

- `SESSION_DRIVER=database` é necessário em produção para revogação remota real (já presente em `.env.example`). A tabela `sessions` é criada pela migration default `0001_01_01_000000_create_users_table.php`.

## Testes relacionados

`tests/Feature/SecurityTest.php` cobre:

- login/logout disparam `auth_sessions` + audit logs.
- `failed_login` é auditado sem credenciais.
- `two_factor_enabled/disabled` audita sem expor secret.
- `two_factor_secret` e `recovery_codes` ficam encriptados no DB.
- revogação de sessão por tenant_admin (mesmo tenant), bloqueio cross-tenant, revogação por super_admin global, utilizador comum só revoga as próprias.
- `PersistPanelUserAction` rejeita senha fraca e aceita senha forte.
- `UpdateOwnPasswordAction` rejeita senha fraca, recusa senha actual incorrecta, aceita combinação válida e gera audit `password_changed`.
- `AuthSessionService::expire` marca como `expired` e audita.
- resolução de sessão por escopo antes da autorização (super_admin vs tenant_admin vs utilizador comum).
- revogação idempotente (sessão já fechada não quebra e remove row em `sessions` quando aplicável).
- `touchActivity` nunca “reabre” sessão encerrada/expirada.

`tests/Feature/AuthPermissionsTest.php` foi actualizado para o novo total de permissões (14).

## Pendências futuras

- WebAuthn / passkeys (fora do escopo desta fase).
- SSO (SAML/OIDC) por tenant.
- Notificação ao utilizador quando uma sessão é revogada por outro actor.
- Endpoint API protegido por Sanctum/JWT (precisará de Auth events específicos).
