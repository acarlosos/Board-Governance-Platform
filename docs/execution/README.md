# `docs/execution/` — Especificações e resultados por fase

Cada fase tem **dois** ficheiros markdown:

| Ficheiro | Quem produz | Quando |
|---|---|---|
| `{fase}-{slug}.md` | **Arquitecto** — *spec* | após o GO do utilizador, antes de qualquer código |
| `{fase}-{slug}.result.md` | **Executor** — *result* | durante a execução; obrigatório antes de marcar a fase concluída |

> **Separação de papéis:** spec = Arquitecto; result = Executor; chat = apenas coordenação e excepções.

Convenção completa em [`.cursor/rules/documentation.mdc`](../../.cursor/rules/documentation.mdc) (secção "Especificações executáveis — `docs/execution/`").

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
4. Decisões tomadas durante implementação (novas `Dxx` ou desvios da spec, justificados)
5. Testes executados (filtros + nº de testes)
6. Resultado dos testes (JSON `phpunit`: `tests`, `passed`, `assertions`, `duration_ms`)
7. Riscos / pontos de atenção
8. Regressões verificadas
9. Pendências futuras
10. Commit sugerido (1 bloco por PR)
11. Confirmação explícita do que NÃO foi alterado (contraste item-a-item com "fora do escopo" da spec)

## Ciclo

```
GO do utilizador
  → Arquitecto cria  docs/execution/{fase}-{slug}.md           (spec)
  → utilizador envia "Executor: executar docs/execution/{fase}-{slug}.md"
  → Executor implementa e cria  docs/execution/{fase}-{slug}.result.md
  → Executor reporta no chat apenas: lista de ficheiros + suite verde + link ao result
  → utilizador valida e marca fase como concluída em docs/roadmap.md
```

## Mensagem-padrão para o Executor

> Executor: executar `docs/execution/{ficheiro}.md`

Não há contexto adicional no chat. Se a spec estiver ambígua, o Executor pára, regista a dúvida no PR/issue e devolve ao Arquitecto.

## Índice

| Fase | Spec | Result | Estado |
|---|---|---|---|
| 19B.1 | [`19B.1-cache-invalidation.md`](19B.1-cache-invalidation.md) | [`19B.1-cache-invalidation.result.md`](19B.1-cache-invalidation.result.md) | **concluída** (suite 317/317) |
| 19B.2 | [`19B.2-dashboard-observability.md`](19B.2-dashboard-observability.md) | _(a criar pelo Executor)_ | **pronta para executar** |
