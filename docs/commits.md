# Padrão de commits — Board Governance Platform

Convenção inspirada em **Conventional Commits**, com mensagens em **português** e **âmbito** alinhado aos módulos do produto.

## Formato da primeira linha (obrigatória)

```
<type>(<scope>): <descrição curta no imperativo>
```

- **type:** `feat` | `fix` | `docs` | `test` | `refactor` | `perf` | `chore` | `ci` | `build`
- **scope:** módulo ou área em **minúsculas** (ex.: `meetings`, `documents`, `votes`, `multitenancy`, `auth`, `filament`, `audit`, `api`).
- **descrição:** clara, presente do imperativo (“adiciona”, “corrige”, “remove”); **sem** ponto final no fim da linha; máx. ~72 caracteres na primeira linha.

### Exemplo

```
feat(meetings): adiciona criação de reuniões com isolamento por tenant
```

## Corpo sugerido (quando útil)

Após uma linha em branco, opcionalmente:

1. **Contexto / motivo** (porquê da mudança).
2. **Notas técnicas** breves (breaking changes, flags, migrations).

## Bloco “resumo para PR / agente”

Ao fechar uma tarefa ou sugerir commit, usar este bloco (copiável):

```text
Commit sugerido:
<type>(<scope>): <descrição>

Arquivos alterados:
- caminho/relativo/um.php
- caminho/relativo/dois.php

Motivo:
<Uma ou duas frases: o que mudou e porquê.>
```

### Exemplo completo

```text
Commit sugerido:
feat(meetings): adiciona criação de reuniões com isolamento por tenant

Arquivos alterados:
- app/Models/Meeting.php
- database/migrations/xxxx_create_meetings_table.php

Motivo:
Implementação inicial do módulo de reuniões com suporte multi-tenant.
```

## Boas práticas

- Um commit lógico por incremento; evitar misturar `feat` unrelated com `fix`.
- Se alterar só testes: `test(scope): …`; só docs: `docs(scope): …`.
- Referenciar issue/ticket na linha do corpo quando existir (`Refs #123`).
