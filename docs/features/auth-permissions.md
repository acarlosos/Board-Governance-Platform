# Autenticação e permissões

## Objetivo

Controlar **quem** acede à plataforma e **o que** pode fazer, com separação clara entre utilizadores do tenant, administradores do tenant e administradores globais, alinhado com Filament e API.

## Tabelas envolvidas

_A preencher:_ `users`, `tenant_user` ou equivalente, tabelas Spatie (`roles`, `permissions`, pivots) se adotados, convites, password resets.

## Models envolvidos

_A preencher:_ `User`, relação com `Tenant`, packages de permissões.

## Policies envolvidas

_A preencher:_ policies por recurso (boards, meetings, documents, etc.) e gates globais se existirem.

## Services / Actions envolvidos

_A preencher:_ convites, sincronização de perfis com roles, ações de impersonação (se existir).

## Regras de negócio

- Autenticação Laravel/Filament padrão; API via Sanctum quando existir.
- Mapeamento **perfil de produto** → conjunto de permissões/roles documentado aqui quando fechado.

## Regras de segurança

- Autorização **sempre** no servidor (Policies/Gates); UI esconde mas não substitui checagens.
- Não expor listagens que permitam enumerar utilizadores de outros tenants sem policy explícita.

## Testes relacionados

_A preencher:_ testes de login, autorização negada, troca de tenant, API com token e abilities.

## Pendências futuras

- **2FA** (Filament/plugin ou fluxo custom).
- SSO empresarial se for requisito.
