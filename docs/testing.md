# Ambiente de testes e isolamento da base de dados

## Comportamento

1. **`phpunit.xml`** define apenas `APP_ENV=testing`.
2. Com `APP_ENV=testing`, o Laravel carrega **`.env.testing`** (ver `LoadEnvironmentVariables` no framework), onde estão `DB_CONNECTION=sqlite`, `DB_DATABASE=database/testing.sqlite`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, etc.
3. **`tests/TestCase.php`** (testes que estendem `Tests\TestCase`):
   - usa **`RefreshDatabase`**: migrações aplicadas ao SQLite de teste e base reposta entre testes de forma controlada;
   - após o bootstrap, **falha de imediato** se a ligação por defeito não for `sqlite` ou se o ficheiro da base não for `testing.sqlite` — evita correr testes contra MySQL por engano.

Os testes em **`Tests\Unit`** que estendem `PHPUnit\Framework\TestCase` **não** passam por esta validação nem usam a app Laravel (não tocam na base).

## Ficheiros

| Ficheiro | Função |
|----------|--------|
| `.env.testing` | Variáveis só para testes (SQLite, drivers em memória). |
| `database/testing.sqlite` | Ficheiro da base SQLite (lista em `database/.gitignore` como `*.sqlite*`). |
| `phpunit.xml` | Força `APP_ENV=testing` para carregar `.env.testing`. |

## Como validar o isolamento

1. **Confirma a config em runtime (Feature / TestCase):**  
   Num teste temporário ou tinker com `--env=testing`:
   ```bash
   php artisan tinker --env=testing
   >>> config('database.default');
   >>> config('database.connections.sqlite.database');
   ```
   Esperado: `sqlite` e caminho que termina em `testing.sqlite`.

2. **Confirma que o MySQL não é o default nos testes:**  
   No `.env` de desenvolvimento podes ter `DB_CONNECTION=mysql`. Com `php artisan test`, o `TestCase` deve continuar a passar — se alguém alterar `.env.testing` para `mysql`, os testes que usam `Tests\TestCase` **falham** na asserção do `sqlite`.

3. **Marca um dado só na base de teste:**  
   Depois de `php artisan test`, inspeciona `database/testing.sqlite` (ex. `sqlite3 database/testing.sqlite '.tables'`) — vês tabelas criadas pelo `RefreshDatabase`; o teu MySQL de desenvolvimento **não** deve ganhar tabelas novas por causa dos testes.

4. **Prova negativa (opcional):**  
   Altera temporariamente `DB_CONNECTION` em `.env.testing` para `mysql` — `php artisan test` deve falhar na validação do `TestCase` (não chega a tocar no MySQL se a asserção falhar primeiro).

## Comandos úteis

```bash
php artisan test
php artisan test --filter=ExampleTest
```

Após alterar migrações, o `RefreshDatabase` volta a aplicá-las ao ficheiro SQLite de testes.
