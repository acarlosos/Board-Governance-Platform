# Multi-tenancy

## Objetivo

Garantir que **toda** a informação sensível de governança pertence a **um tenant** e que **não há vazamento** de dados entre organizações, incluindo painel Filament, API, filas e ficheiros.

## Tabelas envolvidas

_A preencher quando existirem migrations:_ `tenants`, tabelas de negócio com `tenant_id`, pivots que carreguem dados do tenant.

## Models envolvidos

_A preencher:_ model `Tenant`, models com trait `BelongsToTenant`, exceções pontuais para `super_admin`.

## Policies envolvidas

_A preencher:_ policies que verificam `tenant_id` e papel (`tenant_admin` vs `super_admin`).

## Services / Actions envolvidos

_A preencher:_ resolução de tenant (middleware, binding), provisioning, comandos que iteram tenants.

## Regras de negócio

- Utilizador **tenant_admin** e restantes roles **só** veem e alteram dados do tenant atual.
- **`super_admin`** acede globalmente **apenas** onde código e policy o permitirem explicitamente.
- Regras `unique` e `exists` em validações **sempre** filtradas por `tenant_id` quando aplicável.

## Regras de segurança

- Proibir `withoutGlobalScopes` aberto sem revisão e testes.
- Uploads com **prefixo ou disco lógico por tenant**; URLs não podem expor IDs de outros tenants.
- Jobs e notificações devem transportar contexto de tenant explícito.

## Testes relacionados

_A preencher:_ feature tests que provam isolamento (tenant A ≠ tenant B), policies e middleware.

## Pendências futuras

- Definir mecanismo exacto de resolução de tenant (subdomínio, domínio customizado, seleção pós-login).
- Documentar estratégia de backup/restore por tenant se aplicável.
