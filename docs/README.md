# Documentação interna — Board Governance Platform

Base de conhecimento do projeto: **arquitetura**, **decisões técnicas** e **uma ficha por funcionalidade**.

## Onde começar

| Documento | Conteúdo |
|-----------|----------|
| [architecture.md](architecture.md) | Visão geral do sistema, stack, multi-tenancy, segurança e auditoria. |
| [features/README.md](features/README.md) | Índice das funcionalidades documentadas e convenção de nomes. |
| [commits.md](commits.md) | Padrão de mensagens de commit e bloco “Commit sugerido”. |
| [testing.md](testing.md) | Ambiente de testes, `.env.testing`, SQLite e isolamento da base. |

## Convenção

- Cada funcionalidade relevante vive em `docs/features/{nome-da-feature}.md`.
- O nome do ficheiro usa **kebab-case** (ex.: `meeting-minutes.md`, `digital-signatures.md`).
- Evitar duplicar parágrafos entre `architecture.md` e as fichas de feature: aqui **índice e ligações**; nas features **detalhe do que está implementado**.

## Relação com o código

A documentação descreve o **estado atual** (ou planeado de forma explícita na secção *Pendências*). Decisões importantes ficam em `architecture.md` ou na ficha da feature, com referência a classes/migrations quando existirem.
