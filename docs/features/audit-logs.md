# Auditoria (audit logs)

## Objetivo

Manter **trilho imutável** (ou append-only) das ações críticas de governança: quem fez o quê, quando, em que tenant e sobre que recurso, para compliance e investigação.

## Tabelas envolvidas

_A preencher:_ `audit_logs` ou tabelas geridas por pacote (ex. activity log), campos de `morph`, JSON de alterações, IP/user-agent se permitido pela política de privacidade.

## Models envolvidos

_A preencher:_ model de auditoria, observers ou listeners que registam eventos.

## Policies envolvidas

_A preencher:_ quem pode **ler** ou **exportar** auditoria (normalmente `tenant_admin` / compliance / `super_admin`).

## Services / Actions envolvidos

_A preencher:_ serviço central `AuditLogger` ou equivalente; eventos de domínio (voto, assinatura, publicação de ata).

## Regras de negócio

- Ações críticas (ex.: fecho de votação, assinatura, eliminação lógica sensível) **devem** gerar entrada de auditoria.
- Conteúdo do log: metadados suficientes sem gravar dados pessoais ou segredos desnecessários.

## Regras de segurança

- Logs **sem** passwords, tokens ou conteúdo integral de documentos classificados, salvo requisito legal documentado.
- Acesso à listagem de auditoria sujeito a policy e tenant.

## Testes relacionados

_A preencher:_ asserts de que a ação X cria linha de audit com `tenant_id` correcto.

## Pendências futuras

- Escolha final: pacote Spatie Activitylog vs. tabela dedicada vs. híbrido — registar decisão em [../architecture.md](../architecture.md) quando existir.
