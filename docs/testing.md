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

Não há asserts condicionais por driver em `tests/`. No subset crítico 17.7 (2026-05-22), **241/241** testes passaram em ambos os motores; o número de **assertions** no PHPUnit pode divergir (ex.: **1842** SQLite vs **2324** MySQL no mesmo subset) por `RefreshDatabase`/`migrate:fresh` real e auditoria persistida — **não** indica falha funcional se todos os testes estiverem verdes.

| Métrica (subset 17.7) | SQLite | MySQL 8 (`bgp_smoke_test`) |
|------------------------|--------|----------------------------|
| Testes | 241 | 241 |
| Duração (indicativa) | ~12 s | ~27 s |
| Falhas observadas | 0 | 0 |

## Smoke MySQL — passos

1. Criar base dedicada: `CREATE DATABASE bgp_smoke_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. `cp .env.testing.mysql.example .env.testing.mysql` — preencher `DB_*` e `DB_PASSWORD` se aplicável.
3. Script reproduzível (recomendado):
   ```bash
   bash scripts/smoke-mysql-17.7.sh
   ```
4. Ou manualmente:
   ```bash
   php artisan migrate:fresh --force --env=testing.mysql
   vendor/bin/phpunit -c phpunit.mysql.xml tests/Feature/MultitenancyTest.php
   ```

**Comando preferido:** `vendor/bin/phpunit -c phpunit.mysql.xml` (exit code 0 quando verde). `php artisan test --configuration=phpunit.mysql.xml` pode reportar exit **1** apesar de testes verdes (Laravel só define `runningUnitTests()` para `APP_ENV=testing`; o `TestCase` aceita também `testing.mysql`).

Subset 17.7 (pastas): Auth (`AuthPermissionsTest`, `Api/V1/AuthApiTest`, `Unit/Auth/`), Policies (`SecurityHardeningTest`, `BoardsTest`, `MeetingsTest`, `AuditLogsTest`), MultiTenant (`MultitenancyTest`), Dashboard (`Feature|Unit|Observers/Dashboard`, `Filament/Dashboard`), Api (`tests/Feature/Api`).

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
