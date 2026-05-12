# Fase 17 — Revisão pré-launch (subset MVP)

> Spec **anti-regressão**. Subset minimalista da Fase 17 do roadmap: **só 17.4 (policies) + 17.5 (logs) + 17.7 (testes finais)**. Os subitens 17.1–17.3 (performance) são adiados para pós-launch porque exigem dados reais; 17.6 (docs) é coberto por 19A.9.

## 1. Contexto

Antes de fazer deploy produção (Fase 18) é obrigatório passar um pente fino em **policies** (anti-vazamento cross-tenant), **logs** (sem PII/segredos) e **testes finais** (cobertura nas áreas obrigatórias de `.cursor/rules/tests.mdc`). Não é fase de optimização — é fase de **garantia** de que nada óbvio escapa para produção.

Subitens 17.1 (performance), 17.2 (índices MySQL/PostgreSQL) e 17.3 (queries) **não** entram nesta fase. Razão: sem tráfego real é overengineering. Entram numa fase pós-launch após semanas de dados reais (perfilamento Telescope + slow query log).

Subitem 17.6 (docs) entra na 19A.9 (sync documental) — sem duplicação aqui.

## 2. Decisões finais confirmadas

| ID | Decisão |
|---|---|
| **D34** | Subset MVP da Fase 17 = 17.4 + 17.5 + 17.7. 17.1/17.2/17.3 = pós-launch com dados reais. 17.6 = coberta por 19A.9. |
| **D35** | Revisão é **anti-regressão**. Sem refactor estrutural. Correcções permitidas só se forem pontuais (≤ 5 linhas por ponto) ou para fechar gap de cobertura obrigatória. Refactor maior abre nova fase. |
| **D36** | Cobertura obrigatória segue `.cursor/rules/tests.mdc` literalmente: multi-tenancy, policies (allow + deny), criação de reuniões, upload de documentos, votações. Lacunas detectadas viram tests novos nesta fase. |
| **D37** | Sem alterar comportamento. Se um teste detectar bug, abrir issue separada; correcção em PR à parte (não confundir scope de revisão com scope de fix). |

## 3. Escopo

- **17.4 — Revisão de policies:**
  - Varrer **todas** as Policies em `app/Policies/*Policy.php`.
  - Para cada model com `tenant_id`, confirmar que `view`/`update`/`delete`/`viewAny` validam tenant ownership.
  - Confirmar que `super_admin` é único bypass explícito (sem `Gate::before` global).
  - Validar que Filament Resources, Actions e API Controllers chamam `authorize()` ou equivalente antes de `find()`/`firstOrFail()`.
  - Validar que nenhum `withoutGlobalScopes()` em código produtivo está sem `// reason:` comment justificando.
- **17.5 — Revisão de logs:**
  - Varrer `app/` por `Log::info`/`Log::warning`/`Log::error`.
  - Confirmar **zero** `tenant_id` cru fora de canais `audit`.
  - Confirmar **zero** PII (email, password, token, secret) em logs.
  - Confirmar `audit_logs` cobre acções críticas (login, logout, create/update/delete em tenant/user/board/meeting/minute/vote/signature_request/document).
  - Confirmar `IntegrationLog` sanitiza `config` antes de gravar (já feito; revalidar).
- **17.7 — Testes finais:**
  - `php artisan test` 100 % verde em SQLite.
  - Smoke matrix em **MySQL** real: copiar `.env.testing.mysql.example` → `.env.testing.mysql`, correr `php artisan test --configuration=phpunit.mysql.xml` (subset opcional: `--filter=…`; ex.: `Multi*`, policies, auth — ver `docs/testing.md`).
  - Confirmar áreas obrigatórias de `tests.mdc` têm cobertura (matriz de auditoria no result).

## 4. Fora do escopo

- **17.1 Performance / 17.2 Índices DB extra / 17.3 Queries** → adiados para pós-launch.
- **17.6 Docs** → coberta por 19A.9.
- Refactor de Services/Actions.
- Mudança de drivers (queue, session, cache, mail).
- Qualquer item de 19B.
- Mudanças em Gate `view_executive_dashboard` ou outros gates já fechados.
- Mudanças em estrutura de `audit_logs`.

## 5. Arquivos esperados

**Possíveis alterações (só onde a revisão detectar gap):**
- `app/Policies/*Policy.php` — patch de `≤ 5 linhas/policy` quando necessário.
- `app/Filament/Resources/*Resource.php` — confirmar `getEloquentQuery()` não usa `withoutGlobalScopes` sem comentário.
- `app/Http/Controllers/Api/V1/*Controller.php` — confirmar `$this->authorize()` antes de qualquer `find()`.
- `tests/Feature/Policies/*Test.php` — possíveis tests novos onde houver lacuna obrigatória.
- `.env.testing.mysql` (opcional, ignorado em git).

**Sempre criar:**
- `docs/execution/17-pre-launch-review.result.md`.

## 6. Implementação passo a passo

1. **Auditoria de policies** — listar `ls app/Policies/`. Para cada Policy, preencher tabela `model × tenant_id × scope verificado × super_admin tratado × cross-tenant teste existe`. Patch onde faltar.
2. **Auditoria de logs e auditoria** — `rg 'Log::|logger\(' app/ --type=php` e listar **todas** as ocorrências. Para cada uma, classificar: ok / sanitizar / mover para `audit`. Patch quando necessário (≤ 5 linhas/ponto).
3. **Smoke MySQL** — `cp .env.testing.mysql.example .env.testing.mysql` (MySQL 8 dedicado). Correr `php artisan test --configuration=phpunit.mysql.xml` com subset crítico se desejado (`--filter=…`). Reportar timing e diferenças vs SQLite em `docs/testing.md` se surgirem.
4. **Cobrir gaps obrigatórios `tests.mdc`** — onde a auditoria revelar falta, escrever testes mínimos (allow + deny por policy; smoke create-meeting; smoke upload-document; smoke vote-submit). Não inflar com casos cosméticos.
5. **Result + decisão Pre-Launch GO** — preencher matriz de auditoria, listar patches aplicados, listar testes novos, dar decisão Pre-Launch GO/NO-GO.

## 7. Regras de segurança / tenancy

- **`.cursor/rules/tenant-leakage-critical.mdc`** é a régua-mestre desta fase. Qualquer query/policy/validação que falhar o checklist é bug crítico.
- Nenhuma alteração de comportamento. Se a auditoria detectar bug, abrir issue + abrir PR separado fora desta fase (D37).
- Logs continuam sem PII. Audit logs continuam a sanitizar `before`/`after` por allowlist.

## 8. Estratégia de chaves / IDs / nomes

Sem chaves de cache novas. Sem migrations. Sem renomeações.

## 9. Mapas obrigatórios

### Matriz de auditoria — Policies

Preencher no result, uma linha por Policy existente:

| Model | Tem `tenant_id`? | Policy aplica scope? | `super_admin` explícito? | Teste allow? | Teste deny? | Teste cross-tenant? | Acção |
|---|---|---|---|---|---|---|---|
| Tenant | n/a | … | … | … | … | … | … |
| User | sim | … | … | … | … | … | … |
| Board | sim | … | … | … | … | … | … |
| Meeting | sim | … | … | … | … | … | … |
| Document | sim | … | … | … | … | … | … |
| Minute | sim | … | … | … | … | … | … |
| Vote | sim | … | … | … | … | … | … |
| Task | sim | … | … | … | … | … | … |
| SignatureRequest | sim | … | … | … | … | … | … |
| NotificationCenter | sim | … | … | … | … | … | … |
| AuditLog | sim | … | … | … | … | … | … |

### Matriz de auditoria — Logs

Uma linha por ocorrência `Log::*` em código produtivo:

| Ficheiro | Linha | Nível | Mensagem (resumo) | Vaza PII? | Vaza `tenant_id` cru? | Acção |
|---|---|---|---|---|---|---|

### Áreas obrigatórias de `tests.mdc`

| Área | Cobertura confirmada? | Test class |
|---|---|---|
| Multi-tenancy — isolamento | ✓/✗ | … |
| Policies — allow | ✓/✗ | … |
| Policies — deny (cross-tenant) | ✓/✗ | … |
| Criação de reuniões | ✓/✗ | … |
| Upload de documentos | ✓/✗ | … |
| Votações | ✓/✗ | … |

## 10. Testes obrigatórios

- `php artisan test` (suite completa SQLite) — verde.
- `php artisan test --configuration=phpunit.mysql.xml` (smoke MySQL) — verde no subset crítico ou suite completa.
- Para cada gap detectado: escrever test mínimo (allow + deny ou cross-tenant) usando Pest se já é o stack do projecto, senão PHPUnit.

## 11. Validação final

- Matriz Policies: 100 % das linhas com ✓ em "scope aplica" + "cross-tenant teste".
- Matriz Logs: 0 ocorrências com "Vaza PII?" = sim ou "Vaza `tenant_id` cru?" = sim.
- Matriz `tests.mdc`: 6/6 áreas obrigatórias com ✓.
- Suite SQLite verde.
- Smoke MySQL verde.

**Falta qualquer ✓ → NO-GO Pre-Launch. Não avançar para 18 (deploy).**

## 12. Entregáveis esperados

1. Patches em `app/Policies/*.php` / `app/Filament/Resources/*.php` / `app/Http/Controllers/Api/V1/*.php` quando aplicável.
2. Tests novos em `tests/Feature/Policies/*` ou `tests/Feature/*` para gaps obrigatórios.
3. `docs/execution/17-pre-launch-review.result.md` com as 3 matrizes preenchidas + decisão Pre-Launch GO.
4. Lista de issues abertas para bugs detectados (fora do scope desta fase).

## 13. Commit sugerido

Pode ser um único PR com várias commits lógicos. Sugestão:

```
chore(security): revisão final pré-launch — policies/logs/testes (17.4/5/7)
```

Se houver patches que toquem segurança crítica, dividir num segundo commit:

```
fix(security): {policy/log/audit}: corrigir gap detectado pela revisão 17
```

## 14. Instrução final para o Executor

> Esta fase **bloqueia 18 (deploy)**. Sem `Pre-Launch GO` no result, deploy não pode acontecer. Se detectar bug crítico que não cabe em ≤ 5 linhas, **PARAR**, abrir issue, devolver ao Arquitecto. Não tentar resolver bugs grandes dentro do scope da revisão.
