# Ambiente de testes e isolamento da base de dados

## Comportamento

1. **`phpunit.xml`** define `APP_ENV=testing` (suite por defeito em CI/local).
2. Com `APP_ENV=testing`, o Laravel carrega **`.env.testing`** (`LoadEnvironmentVariables`), com `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` (ou ficheiro), `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, etc.
3. **`tests/TestCase.php`** (testes que estendem `Tests\TestCase`):
   - usa **`RefreshDatabase`**: migrações aplicadas à base de teste e reposta entre testes;
   - no bootstrap, **falha de imediato** se o perfil não for um dos dois permitidos:
     - **`testing` + `sqlite`**: ficheiro `testing.sqlite` ou `:memory:` (evita tocar em MySQL por engano);
     - **`testing.mysql` + `mysql`**: smoke opcional (17.7) com `.env.testing.mysql`, `TESTING_MYSQL_SMOKE=true` e `DB_DATABASE` com sufixo seguro (`*_test`, `*_testing`, `*_smoke_test`).

Os testes em **`Tests\Unit`** que estendem `PHPUnit\Framework\TestCase` **não** passam por esta validação nem usam a app Laravel (não tocam na base).

## Ficheiros

| Ficheiro | Função |
|----------|--------|
| `.env.testing` | Variáveis da suite por defeito (SQLite, drivers em memória). |
| `.env.testing.mysql.example` | Modelo para smoke MySQL; copiar para `.env.testing.mysql` (gitignored). |
| `.env.testing.mysql` | Credenciais reais do servidor/container MySQL 8 **só** para smoke local/DevOps. |
| `database/testing.sqlite` | SQLite em ficheiro (opcional se usares `:memory:`). |
| `phpunit.xml` | `APP_ENV=testing` → `.env.testing`. |
| `phpunit.mysql.xml` | `APP_ENV=testing.mysql` → `.env.testing.mysql`. |

## SQLite vs MySQL (smoke 17.7)

| Aspeto | SQLite (defeito) | MySQL (`phpunit.mysql.xml`) |
|--------|------------------|-----------------------------|
| Uso | CI e desenvolvimento diário. | Pré-GO produção, alinhar com staging/prod. |
| `RefreshDatabase` | `migrate:fresh` rápido em memória/ficheiro. | `migrate:fresh` sobre instância dedicada (dados apagados a cada corrida). |
| Diferenças de motor | Poucas: a suite foi escrita para ambos; não há testes que exijam APIs só SQLite. | Exige `pdo_mysql`, charset `utf8mb4`, permissões `CREATE`/`DROP` na base de smoke. |
| Segurança | `.env.testing` versionado com `:memory:`. | `.env.testing.mysql` **não** vai para o git; o `TestCase` recusa nomes de base que não terminem em sufixo dedicado. |

Até à data da última actualização deste documento, **não** há asserts condicionais por driver na pasta `tests/`; qualquer diferença futura (ex.: tipos JSON, `strict` SQL) deve ser registada aqui e, se necessário, corrigida no código ou na migração.

## Smoke MySQL — passos

1. Criar uma base **vazia** dedicada (ex.: `bgp_smoke_test`) num MySQL 8 local ou container.
2. `cp .env.testing.mysql.example .env.testing.mysql` e preencher `DB_*`, mantendo `APP_ENV=testing.mysql` e `TESTING_MYSQL_SMOKE=true`.
3. Correr a suite (completa ou subset com `--filter`):
   ```bash
   php artisan test --configuration=phpunit.mysql.xml
   php artisan test --configuration=phpunit.mysql.xml --filter=MultitenancyTest
   ```
4. Subset mínimo sugerido na spec 17.7: `Multi*`, `*Policy*`, `Auth*` — ajustar o `--filter` em regex conforme o runner local.

**Nota:** `php artisan test --env=testing.mysql` **não** substitui o `phpunit.xml` embutido; o perfil MySQL correcto é **`--configuration=phpunit.mysql.xml`** (ou `vendor/bin/phpunit -c phpunit.mysql.xml`).

## Como validar o isolamento

1. **Confirma a config em runtime (Feature / TestCase):**  
   ```bash
   php artisan tinker --env=testing
   >>> config('database.default');
   >>> config('database.connections.sqlite.database');
   ```  
   Esperado: `sqlite` e `:memory:` ou caminho que termina em `testing.sqlite`.

2. **Confirma que o MySQL não é o default nos testes:**  
   Com `php artisan test` (sem `phpunit.mysql.xml`), o `TestCase` exige `sqlite` em `.env.testing`.

3. **Marca um dado só na base de teste:**  
   Depois de `php artisan test`, o MySQL de desenvolvimento **não** deve ganhar tabelas novas; só o smoke MySQL toca na base configurada em `.env.testing.mysql`.

4. **Prova negativa (opcional):**  
   Alterar `.env.testing` para `DB_CONNECTION=mysql` sem passar pelo perfil `testing.mysql` — o `TestCase` falha (ambiente continua `testing`).

## Comandos úteis

```bash
php artisan test
php artisan test --filter=LocaleTest
php artisan test --configuration=phpunit.mysql.xml
```

Após alterar migrações, o `RefreshDatabase` volta a aplicá-las na base de teste activa (SQLite ou MySQL smoke).
