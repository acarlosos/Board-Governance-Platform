# API REST (v1) + Sanctum

## Objetivo

Criar a **fundação** da API REST pública/privada da Board Governance Platform para:

- autenticação segura para apps móveis e integrações externas
- tokens por dispositivo
- isolamento multi-tenant **absoluto**
- versionamento (`/api/v1/*`)
- respostas (resources/envelope) consistentes
- rate limiting
- auditoria de autenticação e tokens **sem expor segredos**

**Fora de escopo (Fase 16):** GraphQL, websockets, OAuth externo (OIDC/SAML), SDK mobile.

## Decisões (normativas)

### Tenancy (v1)

- O tenant **sempre** é derivado do utilizador autenticado:
  - `auth()->user()->tenant_id`
- **Não** usar `tenant_id` em header.
- **Não** usar `tenant_id` no path.
- **Não** implementar switch tenant nesta fase.
- `super_admin` é o **único bypass global** explícito e deve ser aplicado apenas onde policy permitir.

### Autenticação / Tokens

- Usar **Laravel Sanctum** com **Personal Access Tokens** (PAT).
- 1 token = 1 **dispositivo/app** (identificado por `device_name` / `name` do token).
- Tokens têm:
  - `abilities` (scopes) para limitar acesso
  - `expires_at` configurável (política definida no config)
- **Abilities limitam** acesso, mas **nunca ampliam** permissões do utilizador.
  - Regra: **permitido = Policy/Spatie do user AND ability do token**

### Regra anti-vazamento (obrigatória)

- É **proibido** fazer `find(id)` (ou equivalente) **antes** de:
  - aplicar escopo tenant/ownership, e
  - autorizar via Policy/Gate

Na prática: Actions devem **resolver** o recurso via query já escopada (tenant/owner) e só depois autorizar/operar.

### Controllers finos

- Controllers de API **não** contêm regra crítica.
- Controllers fazem apenas:
  - validar request (Form Request)
  - chamar Action/Service
  - devolver Resource/envelope padronizado

### Respostas padronizadas (envelope)

Toda resposta JSON da API v1 segue um envelope consistente com:

- `success: true|false`
- `data` em caso de sucesso
- `error` em caso de falha (inclui `code`, `message`, e opcionalmente `fields` em 422)
- `meta` com `request_id` e `api_version`; paginação quando aplicável

## Endpoints (v1)

Base path: `/api/v1`

### Dashboard

#### GET `/dashboard/snapshot`

Devolve o snapshot completo do dashboard executivo (`ExecutiveDashboardSnapshot`), serializado em **snake_case** (DTO 19A.3).

**Auth / abilities (D27, D28)**

- Middleware: `abilities:reports:read` (reuso da ability de `view_reports` — **não** existe `dashboard:read` separada).
- Controller: `Gate::authorize('view_executive_dashboard')` (19A.6). **Permitido** só se ability **e** gate passam.

**Query**

- `period` opcional: `this_month` | `last_30_days` | `all_time` (default `this_month`).

**Rate limit (D31)**

- 60 pedidos/min por utilizador (chave `api:v1:dashboard:snapshot:user:{id}`).

**Códigos de erro (API)**

- `401` / `unauthenticated` — sem bearer válido.
- `403` / `forbidden_ability` — token sem `reports:read`.
- `403` / `forbidden_policy` — gate `view_executive_dashboard` negado (ex.: sem `view_reports`, `tenant_id` nulo sem super_admin).
- `422` / `validation_failed` — `period` inválido.
- `429` / `rate_limited` — limite excedido.

**Fora de escopo (D32–D33)**

- Sem write em `/dashboard/*`; sem `v2` nesta fase; tenancy apenas via utilizador autenticado (sem `?tenant_id=`).

### Auth

#### POST `/auth/login`

Cria um token para o dispositivo/app, com abilities opcionais (sempre **limitadas**).

**Request**

```json
{
  "email": "user@tenant.com",
  "password": "********",
  "device_name": "iPhone 15 Pro (Board App)",
  "abilities": ["auth:read", "tokens:read:self"]
}
```

**Regras**

- `abilities` é opcional.
- Se `abilities` **não** forem enviadas, o token recebe o escopo mínimo:
  - `auth:read`
  - `tokens:read:self`
- Abilities solicitadas são **intersectadas** com o máximo permitido para aquele utilizador.

**Response 200**

```json
{
  "success": true,
  "data": {
    "token": "plain_text_token_apenas_uma_vez",
    "token_type": "Bearer",
    "expires_at": "2026-06-06T12:00:00Z",
    "user": {
      "id": 1,
      "name": "…",
      "email": "…",
      "tenant_id": 10,
      "is_super_admin": false
    },
    "tenant": {
      "id": 10,
      "name": "…",
      "slug": "…"
    },
    "abilities": ["auth:read", "tokens:read:self"]
  },
  "meta": { "request_id": "uuid", "api_version": "v1" }
}
```

**Auditoria**

- `api_login` (sem token) com: `user_id`, `tenant_id`, `token_id`, `device_name`, IP/UA.

#### POST `/auth/logout`

Revoga o **token atual** (o mesmo apresentado no `Authorization: Bearer ...`).

**Response 200**

```json
{
  "success": true,
  "data": { "revoked": true },
  "meta": { "request_id": "uuid", "api_version": "v1" }
}
```

**Auditoria**

- `api_logout` (sem token) com: `user_id`, `tenant_id`, `token_id`, IP/UA.
- `token_revoked` (sem token) com: `token_id` e contexto.

#### GET `/auth/me`

Devolve contexto mínimo do utilizador e do token atual.

**Decisão de minimização de dados**

- Não expor a lista completa de permissões internas por padrão.
- Retornar apenas:
  - dados básicos do utilizador
  - tenant básico
  - roles (se necessário para UX)
  - abilities do token atual
  - `capabilities` mínimas (calculadas) — opcional

**Response 200**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "…",
      "email": "…",
      "tenant_id": 10,
      "is_super_admin": false,
      "roles": ["tenant_admin"]
    },
    "tenant": {
      "id": 10,
      "name": "…",
      "slug": "…"
    },
    "token": {
      "id": 123,
      "name": "iPhone 15 Pro (Board App)",
      "abilities": ["auth:read", "tokens:read:self"],
      "expires_at": "2026-06-06T12:00:00Z",
      "last_used_at": "2026-05-06T12:00:00Z"
    },
    "capabilities": {
      "can_manage_security": false,
      "can_manage_documents": true,
      "can_view_reports": true
    }
  },
  "meta": { "request_id": "uuid", "api_version": "v1" }
}
```

Notas:

- `capabilities` deve ser um conjunto **pequeno** e estável (flags de UX), calculado via Policies/Spatie.
- Se `roles` não forem necessários, podem ser removidos e substituídos por capabilities.

#### GET `/auth/tokens`

Lista tokens do próprio utilizador.

**Requisitos**

- exige ability: `tokens:read:self`

**Response 200 (paginado)**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "iPhone…",
      "abilities": ["auth:read", "tokens:read:self"],
      "created_at": "…",
      "last_used_at": "…",
      "expires_at": "…"
    }
  ],
  "meta": {
    "request_id": "uuid",
    "api_version": "v1",
    "pagination": { "page": 1, "per_page": 15, "total": 3 }
  }
}
```

#### POST `/auth/tokens`

Cria token adicional (integrações/devices).

**Requisitos**

- exige ability: `tokens:manage:self`

**Request**

```json
{
  "device_name": "Zapier (Board)",
  "abilities": ["documents:read", "meetings:read"]
}
```

**Response 201**

```json
{
  "success": true,
  "data": {
    "token": "plain_text_token_apenas_uma_vez",
    "token_type": "Bearer",
    "expires_at": "2026-06-06T12:00:00Z",
    "token_record": {
      "id": 456,
      "name": "Zapier (Board)",
      "abilities": ["documents:read", "meetings:read"],
      "created_at": "…"
    }
  },
  "meta": { "request_id": "uuid", "api_version": "v1" }
}
```

**Auditoria**

- `token_created` com: `user_id`, `tenant_id`, `token_id`, `device_name`, `abilities`, IP/UA.
- Nunca gravar o token.

#### DELETE `/auth/tokens/{token_id}`

Revoga token específico do próprio utilizador.

**Requisitos**

- exige ability: `tokens:manage:self`
- por defeito: apenas tokens do próprio `user_id`
- exceção: `super_admin` pode revogar globalmente (policy explícita)

**Response 200**

```json
{
  "success": true,
  "data": { "revoked": true },
  "meta": { "request_id": "uuid", "api_version": "v1" }
}
```

**Auditoria**

- `token_revoked` com: `actor_user_id`, `tenant_id`, `token_id`, e alvo (sem token).

## Abilities (v1)

### Conjunto base

- `auth:read`
- `tokens:read:self`
- `tokens:manage:self` (inclui `tokens:read:self`)

### Abilities por domínio (preparação)

Estas abilities serão usadas quando endpoints de domínio forem expostos na API (fases seguintes), sempre em interseção com policies/permissões:

- `documents:read`, `documents:write`
- `meetings:read`, `meetings:write`
- `minutes:read`, `minutes:write`
- `votes:read`, `votes:write`
- `tasks:read`, `tasks:write`
- `reports:read`
- `integrations:read`, `integrations:write`
- `signatures:read`, `signatures:write`
- `notifications:read`

## Matriz abilities × permissões (regra final)

### Regra universal

Para qualquer operação de API:

1. **Policy/Spatie do user** deve permitir a operação.
2. O **token** deve ter a **ability** correspondente.

**Nunca** usar ability como bypass de policy, e **nunca** usar policy como bypass de ability.

### Mapeamento sugerido (permissions → abilities máximas emitíveis)

Quando um utilizador pede abilities no `login` ou ao criar token adicional, o sistema deve limitar ao máximo permitido pelo próprio utilizador:

- `manage_documents` → `{documents:read, documents:write}`
- `manage_meetings` → `{meetings:read, meetings:write}`
- `manage_minutes` → `{minutes:read, minutes:write}`
- `manage_votes` → `{votes:read, votes:write}`
- `manage_tasks` → `{tasks:read, tasks:write}`
- `manage_integrations` → `{integrations:read, integrations:write}`
- `manage_signatures` → `{signatures:read, signatures:write}`
- `manage_notifications` → `{notifications:read}`
- `view_reports` → `{reports:read}`

O endpoint **`GET /dashboard/snapshot`** (19B.4) reutiliza **`reports:read`** no token e ainda exige o gate **`view_executive_dashboard`** no controller (intersecção obrigatória).

Permissões administrativas de plataforma/conta (ex.: `manage_tenants`, `manage_users`, `manage_settings`, `manage_security`) não são expostas como abilities na API v1, salvo decisão explícita posterior.

## Rate limiting (sugestão inicial)

Valores sujeitos a ajuste após testes de carga/segurança:

- `POST /auth/login`: 5/min por IP + 5/min por email
- `POST /auth/tokens`: 10/min por user
- `DELETE /auth/tokens/{id}`: 30/min por user
- `GET /auth/me` e `GET /auth/tokens`: 60/min por user
- `GET /dashboard/snapshot`: 60/min por user (19B.4)

## Auditoria (eventos)

Eventos mínimos a auditar na Fase 16:

- `api_login`
- `api_logout`
- `token_created`
- `token_revoked`

Regras:

- **Nunca** registrar o token em texto, `Authorization` header ou payload sensível.
- Usar `AuditLoggerService` (sanitização já remove `token`, `authorization`, `access_token`, etc.).

## Regras de segurança (checklist)

- Tenant sempre derivado do user autenticado (sem input de tenant).
- Actions/Policies validam `tenant_id`/ownership explicitamente.
- Proibido `find(id)` antes de escopo/autorização.
- Rate limiting em endpoints sensíveis.
- Respostas de erro sem detalhes internos; sem stack traces.
- Auditoria sanitizada (sem segredos).

## Testes relacionados (Fase 16)

Cobertura mínima esperada:

- autenticação API (login/logout/me)
- criação/listagem/revogação de tokens (owner-only)
- abilities: allow/deny
- interseção user-permissions × abilities
- isolamento tenant (token de A não acessa dados de B)
- rate limiting (login/tokens)
- auditoria de eventos sem token/Authorization

## Pendências futuras

- Endpoints de domínio (documents/meetings/votes/etc.) seguindo o mesmo padrão.
- SSO (SAML/OIDC) por tenant (fora do escopo).
- Switch tenant (só com desenho dedicado e regras fortes).
