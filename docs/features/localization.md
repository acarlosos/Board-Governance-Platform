# Localização (i18n)

## Objetivo

Suportar **pt_BR** (idioma padrão da aplicação), **en** (fallback) e **es** (idioma suportado), com locale por utilizador e strings da app versionadas de forma enxuta. **Não** versionar árvores completas de tradução de pacotes (Filament, etc.) sem necessidade real.

## Estratégia de idiomas

| Papel | Locale | Notas |
|--------|--------|--------|
| **Padrão** | `pt_BR` | `config('app.locale')`; UI e cópias por defeito. |
| **Fallback** | `en` | `config('app.fallback_locale')`; chaves em falta noutros idiomas resolvem-se em inglês. |
| **Suportado** | `es` | Mesmo tratamento que `pt_BR` nas listas `supported` e validação de perfil. |

Traduções **próprias do projeto** ficam em **`lang/{locale}/`** (`pt_BR`, `en`, `es`), em ficheiros por domínio: `messages.php`, `validation.php`, `fields.php`, `navigation.php`, `boards.php`, `auth.php` (quando aplicável ao domínio da app), etc.

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
2. **`config/localization.php`:** lista `supported` (`pt_BR`, `en`, `es`) e `labels` para UI (selector de idioma).
3. **Ficheiros em `lang/{locale}/`:** mensagens da app, validação, campos, navegação, etc. O **inglês** pode incluir ficheiros publicados pelo framework (`auth.php`, `pagination.php`, `passwords.php`) quando fizer sentido manter alinhados ao Laravel.
4. **Middleware `SetLocale`:** registado no grupo **`web`** (após sessão); convidados usam só o default da config; utilizadores com `locale` inválido mantêm o default da config.
5. **Views / Filament:** preferir `__('chave')` com prefixo de ficheiro (`messages.*`, `fields.*`, …). Labels de recursos Filament podem usar `__('fields.*')` ou chaves estáveis em `lang/{locale}/`.

## Traduções de vendor (Filament e outros)

- O Filament e os restantes pacotes trazem traduções **dentro de `vendor/`** via Composer; **não** é obrigatório copiar tudo para o repositório.
- **`lang/vendor/`** está em **`.gitignore`**: evita voltar a versionar milhares de ficheiros por engano.
- **Só publicar e versionar** traduções de pacote quando for **estritamente necessário** sobrescrever uma ou mais chaves concretas (copywriting, correcção, locale em falta no pacote).
- Nesse caso: usar `php artisan vendor:publish` **apenas com a tag** do pacote necessário (ex. um único conjunto `*-translations`), editar **só** os ficheiros/locales alterados e **não** adicionar árvores completas de dezenas de idiomas sem critério.

## Como adicionar novas traduções (app)

1. Escolher ficheiro em `lang/{locale}/`: `messages.php`, `validation.php`, `fields.php`, `navigation.php`, ou ficheiro por domínio (ex. `boards.php`).
2. Manter a mesma chave em **pt_BR**, **en** e **es** quando possível (ou só em `pt_BR` + `en` e deixar `es` herdar do fallback até existir tradução).
3. No código: `__('messages.chave')`, `trans('boards.title')`, etc.
4. Para atributos de validação legíveis: `'attributes' => ['email' => '…']` em `validation.php` por idioma.

## Boas práticas

- Chaves estáveis e hierárquicas (`messages.welcome.heading`), sem concatenar frases com variáveis soltas.
- Não duplicar ficheiros enormes entre idiomas: `validation` pode estar mais completo em `en` com fallback para os restantes.
- Ao adicionar idioma novo: `config/localization.php` → `supported` e `labels`, pasta `lang/{locale}/`, validação de `locale` em Form Requests (whitelist).

## Regras de negócio

- Utilizador autenticado com `locale` em `supported` vê a UI nesse idioma.
- `locale` desconhecido ou vazio: comportamento igual ao visitante (default da config).

## Regras de segurança

- `locale` deve ser validado em Form Requests (whitelist contra `supported`) quando editável pelo utilizador.

## Testes relacionados

- `tests/Feature/LocaleTest.php` — convidado (pt_BR), utilizador `es`, `locale` inválido.

## Pendências futuras

- Selector de idioma na UI e persistência ao guardar perfil.
- Sincronizar `Carbon` / `Date` com locale se necessário para formatos regionais.
