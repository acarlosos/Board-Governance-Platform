# `docs/execution/` — Especificações e resultados por fase

Cada fase tem **dois** ficheiros markdown:

| Ficheiro | Quem produz | Quando |
|---|---|---|
| `{fase}-{slug}.md` | **Arquitecto** — *spec* | após o GO do utilizador, antes de qualquer código |
| `{fase}-{slug}.result.md` | **Executor** — *result* | durante a execução; obrigatório antes de marcar a fase concluída |

> **Separação de papéis:** spec = Arquitecto; result = Executor; chat = apenas coordenação e excepções. O **`result.md` é o relatório técnico oficial da fase** — detalhe técnico vive lá, **não** no chat.

Convenção completa em [`.cursor/rules/documentation.mdc`](../../.cursor/rules/documentation.mdc) (secção "Especificações executáveis — `docs/execution/`").

## Templates

Toda fase nova **começa por copiar** os templates:

- [`_template.spec.md`](_template.spec.md) — 14 secções, prontas para o Arquitecto preencher.
- [`_template.result.md`](_template.result.md) — 11 secções, prontas para o Executor preencher no fecho.

## Spec — 14 secções

1. Contexto
2. Decisões finais confirmadas (com IDs `D1`, `D2`, …)
3. Escopo
4. Fora do escopo
5. Arquivos esperados
6. Implementação passo a passo
7. Regras de segurança / tenancy
8. Estratégia de chaves / IDs / nomes
9. Mapas obrigatórios (observers, eventos, etc.)
10. Testes obrigatórios
11. Validação final
12. Entregáveis esperados
13. Commit sugerido (1 por PR)
14. Instrução final para o Executor

## Result — 11 secções

1. Resumo da implementação (3–10 linhas)
2. Arquivos criados
3. Arquivos alterados
4. **Decisões relevantes tomadas durante execução** (micro-ajustes do Executor — novas `Dxx` ou desvios da spec, justificados)
5. Testes executados (filtros + nº de testes)
6. Resultado dos testes (JSON `phpunit`: `tests`, `passed`, `assertions`, `duration_ms`)
7. Riscos / pontos de atenção
8. Regressões verificadas
9. Pendências futuras
10. Commit sugerido (1 bloco por PR)
11. Confirmação explícita do que NÃO foi alterado (contraste item-a-item com "fora do escopo" da spec)

## Fluxo

```
   ┌─────────────────┐                                                            ┌────────────────────────────┐
   │   Arquitecto    │                                                            │         Validação          │
   │                 │                                                            │      (Arquitecto/Owner)    │
   │  GO do          │                                                            │                            │
   │  utilizador     │                                                            │                            │
   └────────┬────────┘                                                            └────────────▲───────────────┘
            │                                                                                  │
            ▼                                                                                  │
   ┌─────────────────┐                       ┌───────────────────────┐                         │
   │   spec.md       │  "Executor:          │      Executor          │                         │
   │ docs/execution/ ├─ executar  ─────────►│                        │                         │
   │  {fase}.md      │  docs/execution/      │   implementação        │                         │
   └─────────────────┘  {fase}.md"           │       │                │                         │
                                             │       ▼                │                         │
                                             │   result.md            │                         │
                                             │ docs/execution/        ├────────────────────────►│
                                             │  {fase}.result.md      │   atualiza:             │
                                             └───────────────────────┘   docs/roadmap.md,       │
                                                                         docs/features/{f}.md   │
```

**Regra fundamental:** se a spec existe, o Executor **NÃO depende** do histórico do chat. A spec é fonte única.

## Mensagem-padrão para o Executor

> Executor: executar `docs/execution/{ficheiro}.md`

Não há contexto adicional no chat. Se a spec estiver ambígua, o Executor pára, regista a dúvida no PR/issue e devolve ao Arquitecto.

## Resposta-padrão curta do Executor no chat

Após executar uma fase, o Executor responde **apenas** com:

- link da **spec**;
- link do **result**;
- **status** (verde / vermelho / bloqueado);
- **testes** (1 linha — total + duração);
- **bloqueios**, se houver.

Detalhe técnico (lista de ficheiros, output de comandos, sentinelas, tabelas de cobertura) vai **no `result.md`**, não no chat. O chat é coordenação operacional.

## Índice

| Fase | Spec | Result | Estado |
|---|---|---|---|
| 19B.1 | [`19B.1-cache-invalidation.md`](19B.1-cache-invalidation.md) | [`19B.1-cache-invalidation.result.md`](19B.1-cache-invalidation.result.md) | **concluída** (suite 317/317) |
| 19B.2 | [`19B.2-dashboard-observability.md`](19B.2-dashboard-observability.md) | [`19B.2-dashboard-observability.result.md`](19B.2-dashboard-observability.result.md) | **concluída** (suite 328/328) |
