# Fase {N} — {Título curto da fase}

> Especificação executável. O Executor deve seguir este documento sem precisar do chat.
> Origem arquitectural: [`docs/features/{feature}.md`](../features/{feature}.md). Convenção `docs/execution/` em [`.cursor/rules/documentation.mdc`](../../.cursor/rules/documentation.mdc).

<!--
INSTRUÇÕES — REMOVER ANTES DE COMMIT.

Como usar:
1. Copiar este ficheiro para `{fase}-{slug-kebab-case}.md` (ex.: `19B.3-projection-table.md`).
2. Preencher cada secção; manter os 14 cabeçalhos na ordem.
3. Cabeçalhos com placeholder `{...}` são obrigatórios; tabelas vazias devem ser preenchidas ou removidas com justificativa "Sem itens nesta fase."
4. Antes de publicar: remover este comentário HTML e quaisquer `TODO`.
5. O Executor cria depois o pareado `{fase}-{slug}.result.md` (template em `_template.result.md`).
-->

## 1. Contexto

{2–6 linhas: estado actual relevante, problema concreto a resolver, ligação com fases anteriores. Sem rationale de marketing nem texto genérico de framework.}

## 2. Decisões finais confirmadas

> Numeração `Dxx` é **contínua dentro da feature** (ex.: dashboard já tem D1–D12; a próxima decisão é D13). Confirmar com `Grep` antes de atribuir IDs.

| Decisão | Conteúdo |
|---|---|
| **D{n}** — {nome curto} | {1–2 linhas — o quê + razão / trade-off principal} |

## 3. Escopo

1. {Acção 1 — verbo + objecto concreto.}
2. {Acção 2.}
3. {…}

## 4. Fora do escopo

- ❌ {Item — adiar para fase `{N+x}` ou descartar com razão curta.}
- ❌ {…}

## 5. Arquivos esperados

### Novos

| Caminho | Tipo | Propósito |
|---|---|---|
| `app/…` | `final class` | {1 linha} |
| `tests/…` | unit / feature | {1 linha} |

### Modificados

| Caminho | Mudança |
|---|---|
| `app/…` | {1 linha} |
| `.cursor/rules/{rule}.mdc` | {1 linha} |
| `docs/features/{feature}.md` | {1 linha} |

## 6. Implementação passo a passo

> Padrão: {1 PR único / N PRs pequenos} — `php artisan test` verde antes de avançar para o passo seguinte.

### Passo 1 — {Título curto}

1. {Acção concreta.}
2. {…}

### Passo 2 — {Título curto}

1. {…}

### Passo 3 — Documentação

- `.cursor/rules/{rule}.mdc` — {bloco a adicionar}.
- `docs/features/{feature}.md` — {entrada da fase}.
- `docs/roadmap.md` — marcar fase concluída quando suite passar.

## 7. Regras de segurança / tenancy

- {Multi-tenancy — como o escopo respeita isolamento por `tenant_id` / scope / policy.}
- {Authz — gate, policy ou permission relevante; comportamento esperado para `super_admin`, `tenant_admin`, role X, etc.}
- {Anti-leak — chaves, logs, eventos, jobs: nunca expor dados de outro tenant.}
- {Sem `auth()` / `request()` / `session()` em serviços/Actions; receber `User` explícito.}
- {Testes obrigatórios `tests.mdc`: cobrir cross-tenant + perfis afectados + edge case `tenant_id === null`.}

## 8. Estratégia de chaves / IDs / nomes

{Quando relevante: chaves de cache, IDs externos, slugs, route names, permissions, locks, queue names. Caso contrário, escrever: "Sem identificadores novos nesta fase."}

## 9. Mapas obrigatórios

{Tabelas que o Executor consulta directo do documento: observers→campos, eventos→handlers, rotas→controllers, abilities→endpoints, etc. Caso contrário: "Sem mapas adicionais."}

## 10. Testes obrigatórios

> Runner: PHPUnit. Driver de cache: `array` em testes (default `phpunit.xml`). `Cache::flush()` / `RefreshDatabase` conforme necessário.

### Unit ({N} testes)

1. {Caso de teste — 1 linha, verbo no presente.}
2. {…}

### Feature ({N} testes)

1. {…}

### Critério de saída

- Total adicionado: ≈ {N} testes, ≈ {M} assertions.
- Suite total: ≥ {actual_count + N}/{actual_count + N} verde.
- Zero falhas em `php artisan test --filter='{regex_relevante}'`.

## 11. Validação final

Sequência **obrigatória** antes de pedir review do PR:

```
1) php artisan test --filter='{Filtro1}'
2) php artisan test --filter='{Filtro2}'
3) php artisan test                 # suite completa
4) php artisan filament:assets      # smoke: deploy continua a funcionar
```

Se qualquer passo falhar, **parar** e diagnosticar.

Validação **staging** (opcional, não bloqueia merge): {1–3 verificações manuais ou de smoke.}

## 12. Entregáveis esperados

1. Lista de ficheiros criados/alterados (top-level).
2. Resultado da suite completa (JSON `phpunit`: `tests`, `passed`, `assertions`, `duration_ms`).
3. {Sentinelas / outputs de comando / diffs específicos da fase.}
4. {Confirmação `Dxx` em diff das rules.}
5. **`docs/execution/{fase}-{slug}.result.md`** preenchido conforme as 11 secções obrigatórias em [`.cursor/rules/documentation.mdc`](../../.cursor/rules/documentation.mdc) (subsecção "Especificações executáveis — `docs/execution/`"). **Obrigatório**.

## 13. Commit sugerido

> Convenção [`docs/commits.md`](../commits.md) — `type(scope): descrição em português`.

```
{type}({scope}): {descrição curta} ({fase})
```

Motivo: {1–2 linhas — o quê + porquê.}

Ficheiros: {contagem ou lista curta}.

## 14. Instrução final para o Executor

Executar este documento integralmente, na ordem dos passos descritos em §6.

**Não criar**:

- {Item fora do âmbito que tipicamente o Executor pode achar útil mas não deve fazer.}
- {…}

Se durante a execução algum critério de §11 não passar, **parar** e retornar com diagnóstico — não tentar contornar.

Se {alguma decisão da spec se mostrar bloqueante na prática}, **parar e abrir issue arquitectural** — não improvisar.

Antes de marcar a fase concluída, **criar e preencher** `docs/execution/{fase}-{slug}.result.md` (template em [`_template.result.md`](_template.result.md)) conforme as 11 secções obrigatórias. Sem o result, a fase **não** é considerada concluída.

Reportar no chat **apenas** (resposta-padrão curta — `.cursor/rules/documentation.mdc`):

- link da **spec**;
- link do **result**;
- **status** (verde / vermelho / bloqueado);
- **testes** (1 linha — total + duração);
- **bloqueios**, se houver.

Detalhe técnico (lista de ficheiros, output de comandos, sentinelas, tabelas) vai no `result.md`, não no chat.
