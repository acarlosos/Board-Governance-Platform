# Resultado — Fase 17 (Revisão pré-launch — subset MVP)

Relatório de [`17-pre-launch-review.md`](./17-pre-launch-review.md). Última actualização smoke MySQL: **2026-05-22**.

## 1. Decisão

**Pre-Launch GO (condicional):** auditoria estática **sem patches de código** na revisão inicial; **D37** — bugs latentes → issue + PR separado. **17.7 smoke MySQL:** subset crítico **verde** (ver §4).

## 2. Policies (17.4)

| Área | Resultado |
|------|-----------|
| Ficheiros `app/Policies/*Policy.php` | **27** ficheiros listados |
| Revisão linha-a-linha | Amostragem: policies de domínio seguem padrão tenant + abilities em Filament/API |
| `withoutGlobalScopes()` | PO6 concluído noutro PR (`// reason:` em `app/`) |

## 3. Logs (17.5)

| Verificação | Resultado |
|---------------|-----------|
| `Log::` / `logger(` em `app/` | **0** ocorrências com facade `Log` |

## 4. Testes (17.7)

| Bloco | Ficheiros (smoke) | MySQL 8 | SQLite (mesmo subset) |
|-------|-------------------|---------|------------------------|
| **Auth** | `AuthPermissionsTest`, `Api/V1/AuthApiTest`, `Unit/Auth/` | ✅ | ✅ |
| **Policies** | `SecurityHardeningTest`, `BoardsTest`, `MeetingsTest`, `AuditLogsTest` | ✅ | ✅ |
| **MultiTenant** | `MultitenancyTest` | ✅ | ✅ |
| **Dashboard** | `Feature|Unit|Observers/Dashboard`, `Filament/Dashboard` | ✅ | ✅ |
| **Api** | `tests/Feature/Api` | ✅ | ✅ |
| **Total subset** | 241 testes | **241/241**, ~27 s | **241/241**, ~12 s |

**Infra smoke:** `.env.testing.mysql` (local, gitignored), base `bgp_smoke_test`, `php artisan migrate:fresh --env=testing.mysql` OK. Comando canónico: `bash scripts/smoke-mysql-17.7.sh` ou `vendor/bin/phpunit -c phpunit.mysql.xml` (paths em `docs/testing.md`).

**Correcção mínima:** `tests/TestCase.php` — `runningUnitTests()` do Laravel só é true para `APP_ENV=testing`; smoke usa `testing.mysql` → guarda alargada.

**Diferenças SQLite vs MySQL:** sem falhas funcionais no subset; assertions PHPUnit mais altas em MySQL (2324 vs 1842) e tempo ~2× — esperado com `migrate:fresh` por teste em servidor real. Detalhe em `docs/testing.md`.

## 5. Suite completa (SQLite CI)

- `php artisan test` — **359/359**, **2531** assertions (2026-05-22, pós-fix `TestCase`).

## 6. Bloqueios

- **Smoke MySQL subset 17.7:** ✅ executado localmente — **não bloqueia** GO por motor de testes.
- **GO produção** continua condicionado a **19A.8** (QA staging), soak e restantes checklists operacionais.
