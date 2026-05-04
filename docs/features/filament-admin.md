# Painel administrativo (Filament)

## Objetivo

Fornecer a **UI administrativa** da Board (gestão por tenant) com formulários e tabelas consistentes, **autorização** via Policies e **consultas sempre filtradas** pelo tenant actual.

## Painel `admin`

- `App\Providers\Filament\AdminPanelProvider` regista o painel com `->id('admin')`, `->path('admin')` e **`->default()`**. Sem painel por defeito, comandos como `php artisan make:filament-user` falham com `NoDefaultPanelSetException`.

## Tabelas envolvidas

_A preencher por recurso Filament:_ mesmas tabelas de domínio expostas nos Resources (ver fichas de feature de negócio correspondentes).

## Models envolvidos

_A preencher:_ models ligados a cada `Resource` / `Page` / `Widget`.

## Policies envolvidas

_A preencher:_ `policy` registada por model; métodos `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` conforme necessário.

## Services / Actions envolvidos

_A preencher:_ chamadas desde `CreateAction` / `EditAction` / custom actions para Services/Actions pesados.

## Regras de negócio

- Resources **sem** regra de negócio crítica isolada em closures grandes: delegar a camada de serviço.
- Forms com **Sections**; tables com filtros e actions nomeados de forma clara.

## Regras de segurança

- Todas as queries do painel respeitam **TenantScope** / `BelongsToTenant`.
- Não confiar em `hidden()`/`disabled()` no form como única protecção; validar no request e na policy.

## Testes relacionados

_A preencher:_ feature tests Filament (Livewire) ou HTTP contra rotas do painel, conforme stack de testes do projeto.

## Traduções

Preferir labels explícitos com chaves da app em **`lang/{pt_BR,en,es}/`** (ex. `fields.*`). Sobrescritas pontuais de pacotes Filament só quando necessário — ver estratégia e limites em [localization.md](localization.md) (`lang/vendor/` ignorado no Git por defeito).

## Pendências futuras

- `php artisan filament:install --panels` e primeiro painel, se ainda não existirem.
- Temas / branding por tenant se for requisito.
- Plugins (2FA, media library) documentados aqui quando instalados.
