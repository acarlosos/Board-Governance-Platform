# Localização (i18n)

## Objetivo

Suportar **pt_BR** (padrão), **en** e **es** na Board, com locale por utilizador, fallback de traduções e base pronta para **Filament** (labels traduzíveis no futuro).

## Tabelas envolvidas

- `users.locale` — `string(10)`, default `pt_BR` (migração `*_add_locale_to_users_table`).

## Models envolvidos

- `User` — atributo `locale` em `$fillable`.

## Policies envolvidas

_Nenhuma específica nesta fase._

## Services / Actions envolvidos

- `App\Http\Middleware\SetLocale` — define `App::setLocale()` a partir do utilizador autenticado (se válido) ou `config('app.locale')`.

## Como funciona

1. **`config/app.php`:** `locale` default `pt_BR`, `fallback_locale` `en` (sobreponível via `.env`).
2. **`config/localization.php`:** lista `supported` (`pt_BR`, `en`, `es`) e `labels` para UI futura (selector de idioma).
3. **Ficheiros:** `lang/{pt_BR,en,es}/messages.php` (textos da app) e `lang/en/validation.php` (completo, publicado pelo framework) + `lang/pt_BR/validation.php` e `lang/es/validation.php` (regras frequentes; chaves em falta usam o **fallback** em inglês).
4. **Middleware `SetLocale`:** registado no grupo **`web`** (após sessão); convidados usam só o default da config; utilizadores com `locale` inválido mantêm o default da config.
5. **Views:** preferir `__('chave')` ou `@lang` em vez de texto fixo (exemplo na `welcome`: título e `<h1>`).

## Como adicionar novas traduções

1. Escolher ficheiro: `messages.php` para UI geral; `validation.php` para mensagens de validação; ou ficheiros por domínio (ex. `lang/pt_BR/boards.php`) quando o volume crescer.
2. Adicionar a mesma chave em **pt_BR**, **en** e **es** (ou só em pt_BR + en e deixar es herdar do fallback até existir tradução).
3. Na view ou PHP: `__('messages.chave.subchave')` ou `trans('boards.title')`.
4. Para atributos de validação legíveis: preencher `'attributes' => ['email' => 'e-mail']` em `validation.php` por idioma.

## Boas práticas

- Chaves estáveis e hierárquicas (`messages.welcome.heading`), sem concatenar frases com variáveis soltas.
- Não duplicar ficheiros enormes entre idiomas: manter **en** completo para `validation` onde fizer sentido; outros idiomas com subconjunto + fallback.
- Ao adicionar idioma novo: incluir em `config/localization.php` → `supported` e `labels`, criar pasta `lang/{locale}/`, atualizar validação de perfil quando existir formulário de `locale`.
- **Filament (futuro):** usar `->label(__('...'))` em recursos; considerar `lang/vendor/filament` após instalar o pacote; o locale da app (`App::getLocale()`) é o que o Filament segue para formatação e traduções de pacote.

## Regras de negócio

- Utilizador autenticado com `locale` em `supported` vê a UI nesse idioma.
- `locale` desconhecido ou vazio: comportamento igual ao visitante (default da config).

## Regras de segurança

- `locale` deve ser validado em Form Requests (whitelist contra `supported`) quando editável pelo utilizador.

## Testes relacionados

- `tests/Feature/LocaleTest.php` — convidado (pt_BR), utilizador `es`, `locale` inválido.

## Pendências futuras

- Selector de idioma na UI e persistência ao guardar perfil.
- Pacote de traduções Filament e `php artisan filament:install` com locale.
- Sincronizar `Carbon` / `Date` com locale se necessário para formatos regionais.
