# Fase 18 — Deploy produção (18.1–18.7)

> Spec **operacional**. Foundation mínima para "produção estável": deploy automatizado, storage, queue, scheduler, backup, monitorização e checklist de segurança. Sem infra-as-code, sem CDN, sem Redis (ficam para evolução).

## 1. Contexto

Após `Pre-Launch GO` em 17, o produto é tecnicamente apto a ir para produção. Falta a foundation operacional: ambiente, deploy script, storage, queue, scheduler, backup, monitor, checklist segurança.

Decisões de stack assumem **Laravel Forge** (ou equivalente git-pull-com-zero-downtime) como plataforma de deploy. Servidor pré-provisionado pelo cliente (não infra-as-code nesta fase).

## 2. Decisões finais confirmadas

| ID | Decisão |
|---|---|
| **D38** | Infra-as-code (Terraform/Pulumi) **fora** desta fase. Servidor pré-provisionado pelo cliente / DevOps. |
| **D39** | Storage = `local` (`storage/app/private/tenants/{tenant_id}/...`). S3 fica para evolução futura quando volume justificar. |
| **D40** | Queue driver = `database`. Redis fica para fase pós-launch. Worker corre via Supervisor (1–2 processos). |
| **D41** | Scheduler corre via cron de 1 minuto (`* * * * * php artisan schedule:run`). Já existe schedule em `bootstrap/app.php` (dashboard projections cada 5 min, audit retention, etc.). |
| **D42** | Backup via `spatie/laravel-backup` (DB + `storage/app/private`) nocturno, retenção 14 dias, alerta por email. |
| **D43** | Monitorização básica: `/health` endpoint público (200 OK quando DB+cache ok); uptime externo via UptimeRobot (free tier) ou Forge monitoring. APM (NewRelic/Datadog) é pós-launch. |
| **D44** | Checklist segurança = 10 itens verificáveis (env vars, HTTPS, cookies, headers, debug off, etc.). Sem auditoria externa nesta fase. |

## 3. Escopo

- **18.1 Ambiente produção:** `.env.production.example`, `deploy.sh` (Forge), instruções de provisioning mínimo.
- **18.2 Storage:** confirmar `config/filesystems.php` aponta `private` para `storage/app/private`; documentar política de permissions; smoke test upload por tenant.
- **18.3 Queue:** driver `database`, `php artisan queue:table` (se ainda não corrido), Supervisor config doc + restart script.
- **18.4 Scheduler:** cron Forge `* * * * * php artisan schedule:run`; smoke `php artisan schedule:list` no result.
- **18.5 Backup:** instalar `spatie/laravel-backup`, configurar `config/backup.php`, schedule diário + alert email.
- **18.6 Monitorização:** rota `/health` (não auth) com check DB + cache; doc UptimeRobot setup.
- **18.7 Checklist segurança:** 10 itens validados antes do GO produção final.

## 4. Fora do escopo

- Infra-as-code / Terraform / Ansible.
- CDN, edge cache, Cloudflare.
- Redis (queue/cache/session).
- S3 / object storage.
- APM (NewRelic, Datadog, Sentry).
- Auditoria externa de segurança / pentest.
- Multi-region deploy.
- Containerização (Docker/K8s).
- Migração de banco em produção com downtime > 1 min (caso houvesse — não há).

## 5. Arquivos esperados

**Criar:**
- `.env.production.example` (template sem segredos reais).
- `deploy.sh` na raiz ou em `scripts/deploy.sh` (Forge hook).
- `app/Http/Controllers/Health/HealthCheckController.php`.
- `tests/Feature/Health/HealthCheckTest.php`.
- `docs/operations/deploy.md` (guia operacional novo).
- `docs/operations/runbook.md` (incidentes comuns).
- `docs/operations/security-checklist.md` (10 itens).
- `docs/execution/18-production-deploy.result.md`.

**Alterar:**
- `routes/web.php` (rota `/health`).
- `composer.json` (dependência `spatie/laravel-backup` se ainda não estiver).
- `config/backup.php` (publicado pelo package; ajustar destinos/retenção).
- `bootstrap/app.php` (schedule `backup:run` diário + `backup:clean` semanal).
- `.env.example` (variáveis de backup, sem valores).

## 6. Implementação passo a passo

1. **Deploy script + env template** — criar `.env.production.example` listando todas variáveis necessárias (DB, cache, queue, session, mail, app, board.dashboard.*, backup, etc.) com comentários mas **sem valores reais**. Criar `scripts/deploy.sh` com sequência: `composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan filament:upgrade`.
2. **Storage + queue + scheduler** — confirmar `config/filesystems.php` tem disco `private` apontando `storage/app/private`. Validar `php artisan queue:table` (se ainda não existir) e migration aplicada. Documentar Supervisor config em `docs/operations/deploy.md`. Confirmar cron de scheduler.
3. **Backup** — `composer require spatie/laravel-backup`. `php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"`. Configurar `config/backup.php`: destination `local` (path `storage/app/backups`), notificação email, retenção 14 dias. Adicionar a `bootstrap/app.php` schedule: `backup:run` diário 03:00 + `backup:clean` semanal.
4. **Healthcheck + monitoring** — controller `HealthCheckController::__invoke()` que tenta `DB::connection()->getPdo()` + `Cache::set('hc', 1, 5); Cache::get('hc')`. Devolve 200 com `{status: ok, db: ok, cache: ok, version: …}` ou 503 com detalhe. Teste `HealthCheckTest` cobre ok + db_down (mock). Rota `Route::get('/health', HealthCheckController::class)->withoutMiddleware([...])`. Documentar UptimeRobot setup em `docs/operations/deploy.md`.
5. **Checklist segurança + runbook** — `docs/operations/security-checklist.md` lista os 10 itens (ver §9). Validar manualmente em staging antes do GO produção. `docs/operations/runbook.md` cobre 5 cenários: deploy falha, queue worker morto, backup falha, healthcheck vermelho, rollback de release. Result com matriz de checklist preenchida.

## 7. Regras de segurança / tenancy

- **Storage**: cada tenant escreve em `storage/app/private/tenants/{tenant_id}/...`. Permissions: `chmod 750 storage/app/private`. Nginx **não** serve `storage/app/private` directamente (já é a config Laravel padrão; confirmar).
- **Env**: secrets via Forge env editor (encriptados em rest). `APP_KEY` gerada **uma vez** em produção; nunca recommit.
- **Sessions**: `SESSION_SECURE_COOKIE=true` em produção; `SESSION_DOMAIN` configurado.
- **HTTPS**: redirect 301 HTTP→HTTPS via Nginx; HSTS preload-ready.
- **Backup**: ficheiros de backup contêm DB inteira → tratar como secret-tier. Notificação por email mas **conteúdo** nunca vai por email.

## 8. Estratégia de chaves / IDs / nomes

- Routes: `/health` (sem `/api/v1` prefix, sem auth, sem rate limit estrito; Nginx pode aplicar limit de 60 req/min).
- Backup name: `bgp-{env}-{Y-m-d_H-i-s}.zip`.
- Cron names: padrão `schedule:run`; backup `php artisan backup:run` via schedule.
- Supervisor program name: `bgp-queue-worker`.

## 9. Mapas obrigatórios

### Checklist segurança — 10 itens (validar antes do GO)

| # | Item | Como validar | Estado |
|---|---|---|---|
| S1 | `APP_DEBUG=false` em produção | `php artisan tinker --execute='echo config("app.debug")'` | □ |
| S2 | `APP_ENV=production` | mesmo + verificar `/health` response | □ |
| S3 | HTTPS forçado (redirect 301) | `curl -I http://prod.url` → 301 | □ |
| S4 | `SESSION_SECURE_COOKIE=true` | grep `.env.production` | □ |
| S5 | Headers segurança (HSTS, X-Frame-Options, CSP) | `curl -I https://prod.url` | □ |
| S6 | `APP_KEY` definida e diferente de dev/staging | `php artisan tinker` | □ |
| S7 | DB user com permissions mínimas (CRUD em DB própria; sem `SUPER`) | DB admin | □ |
| S8 | Storage `private` não servido por Nginx | `curl https://prod.url/storage/app/private/...` → 404 | □ |
| S9 | Healthcheck público responde 200 | `curl https://prod.url/health` | □ |
| S10 | Backup smoke (correr 1x manual) | `php artisan backup:run` + abrir zip | □ |

### Cron actual + esperado

| Cron | Comando | Frequência |
|---|---|---|
| Scheduler base | `php artisan schedule:run` | a cada 1 min |
| Dashboard projections | `php artisan dashboard:refresh-projections` (via schedule) | cada 5 min |
| Backup DB+files | `php artisan backup:run` (via schedule) | diário 03:00 |
| Backup cleanup | `php artisan backup:clean` (via schedule) | semanal |
| Audit retention | (se já existir) `php artisan audit:purge` | conforme schedule actual |

### Runbook — 5 cenários mínimos

| Cenário | Sintoma | Acção |
|---|---|---|
| Deploy falha | Forge script exit ≠ 0 | rollback release; ler `storage/logs/laravel.log`; abrir issue |
| Queue worker morto | `dashboard:refresh-projections` não corre, jobs acumulam | `sudo supervisorctl restart bgp-queue-worker:*` |
| Backup falha | email alert Spatie | ler log; correr manual; verificar disco; recolocar schedule |
| Healthcheck 503 | UptimeRobot alerta | abrir `/health` direto; investigar DB ou cache; reiniciar service específico |
| Rollback de release | bug descoberto pós-deploy | Forge "Activate previous release"; `php artisan migrate:rollback` só se migration nova causou bug |

## 10. Testes obrigatórios

- `tests/Feature/Health/HealthCheckTest::test_health_endpoint_responds_ok_when_db_and_cache_ok` (HTTP 200, JSON shape estável).
- `tests/Feature/Health/HealthCheckTest::test_health_endpoint_responds_503_when_db_down` (mock `DB::connection` para throw).
- Suite global verde: `php artisan test`.
- Smoke do backup em staging: `php artisan backup:run --only-db` → 0 erros, zip criado.
- Smoke do schedule: `php artisan schedule:list` mostra entries.

## 11. Validação final

- 10/10 itens do checklist segurança ✓.
- `/health` responde 200 em staging e produção.
- Backup run manual em staging cria zip válido.
- Suite verde.
- `php artisan schedule:list` mostra projections, backup, backup:clean.
- Queue worker corre via Supervisor (verificar em staging).
- Deploy automatizado: 1 `git push` → release nova activa, sem manual intervention.

**Falta qualquer item → NO-GO produção.**

## 12. Entregáveis esperados

1. Todos os ficheiros listados em §5.
2. `docs/execution/18-production-deploy.result.md` com:
   - matriz S1–S10 preenchida com evidência;
   - output `php artisan schedule:list`;
   - output `php artisan backup:run --only-db` em staging;
   - output `curl /health` (staging + produção, se já tiver acesso);
   - timestamp do GO produção;
   - lista de **issues pós-launch abertas** para débito conhecido (S3/Redis/APM/etc.).
3. Tag git `v1.0.0` no commit do GO produção.

## 13. Commit sugerido

Pode ser dividido em commits lógicos:

```
feat(deploy): healthcheck endpoint + tests (18.6)
feat(deploy): backup nocturno via spatie/laravel-backup (18.5)
chore(deploy): production env template + deploy script (18.1)
docs(deploy): operations guide + runbook + security checklist (18.1-18.7)
```

Ou um único PR fechando tudo:

```
feat(deploy): production deploy foundation (18.1-18.7)
```

## 14. Instrução final para o Executor

> Esta fase **bloqueia o lançamento**. GO produção exige 10/10 ✓ em §9 + healthcheck verde + backup smoke + suite verde. Se faltar qualquer item, **PARAR**, registar no result, devolver ao Arquitecto. Não fazer GO parcial. Após GO, criar tag `v1.0.0` e marcar fase 18 + roadmap 18.1–18.7 = ✅.
