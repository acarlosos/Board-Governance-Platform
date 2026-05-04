# Arquitetura — Board Governance Platform

## Base técnica do repositório

O código da aplicação assenta em **Laravel** (skeleton oficial na raiz do repositório: `artisan`, `app/`, `routes/`, `config/`, etc.). A versão do framework está fixada em `composer.json` / `composer.lock` (actualmente **Laravel 13** com PHP **^8.3**). O painel **Filament**, multi-tenancy explícito e restantes módulos de negócio serão adicionados em fases posteriores sobre esta base.

## Produto

SaaS **multi-tenant** de **governança corporativa**: organizações (tenants), conselhos, reuniões, atas, documentos e versionamento, votações, assinaturas digitais, pendências/workflows, notificações, relatórios e integrações futuras (Office 365, OneDrive, DocuSign, e-mail, BI, videoconferência).

## Stack

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel |
| Painel administrativo | Filament v5 (Livewire v4) — pacote instalado; painel a configurar |
| UI reativa (onde aplicável) | Livewire |
| Base de dados | MySQL |
| API (quando existir) | Laravel + Sanctum (tokens com abilities) |

## Testes e base de dados

Os testes automatizados usam **SQLite** (`database/testing.sqlite`) via **`.env.testing`**, nunca o MySQL principal por defeito. O `phpunit.xml` fixa `APP_ENV=testing` para o Laravel carregar esse ficheiro; o `Tests\TestCase` valida a ligação e usa **`RefreshDatabase`**. Ver [testing.md](testing.md).

## Idiomas

Idiomas suportados (**pt_BR**, **en**, **es**), middleware `SetLocale`, ficheiros em `lang/` e campo `users.locale` — ver [features/localization.md](features/localization.md).

## Multi-tenancy

- Modelo alvo: **uma base MySQL**, dados de negócio com **`tenant_id`**, isolamento por **global scopes** / trait **`BelongsToTenant`** e resolução de tenant no pedido (subdomínio, path ou fluxo pós-login — **a fixar na implementação** e documentar em [features/multitenancy.md](features/multitenancy.md)).
- Perfis: **`super_admin`** (acesso global explícito), **`tenant_admin`** e utilizadores do tenant apenas no seu contexto.
- **Nenhuma** consulta ou policy pode assumir dados de outro tenant.

## Camadas de aplicação

- **HTTP / Filament:** finos; validação via Form Requests; autorização via Policies/Gates.
- **Negócio:** Actions, Services; lógica complexa fora de Models quando deixar de caber com clareza.
- **Modelos:** relacionamentos Eloquent, casts, scopes simples; observers quando fizer sentido.
- **Auditoria:** eventos críticos registados (ver [features/audit-logs.md](features/audit-logs.md)).

## Segurança (resumo)

- Validação sempre no servidor; não confiar no Filament/Livewire sozinhos.
- Senhas com hash nativo Laravel; **2FA** como evolução planeada.
- Logs sem segredos nem PII desnecessária; exclusão física de dados sensíveis só com justificativa documentada.

## API REST

Estrutura preparada para **`/api/v1`** (versionamento), mesmo tenant e policies que o painel. Detalhes por recurso nas respetivas fichas em `docs/features/`.

## Decisões técnicas pendentes de fixação

Registar aqui ou na feature correspondente quando forem tomadas (ex.: pacote de activity log vs. tabela `audit_logs` própria, estratégia exata de storage por tenant).
