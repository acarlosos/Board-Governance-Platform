# Checklist segurança pré-GO produção (10 itens)

| # | Item | Como validar |
|---|------|----------------|
| S1 | `APP_DEBUG=false` | `php artisan tinker --execute="var_export(config('app.debug'));"` |
| S2 | `APP_ENV=production` | idem + resposta `/health` |
| S3 | HTTPS 301 | `curl -I http://…` → 301 para https |
| S4 | `SESSION_SECURE_COOKIE=true` | env produção |
| S5 | Headers (HSTS, X-Frame-Options, etc.) | `curl -I https://…` + `SecurityHeadersMiddleware` |
| S6 | `APP_KEY` única | não reutilizar chave de dev |
| S7 | DB user least privilege | sem SUPER global |
| S8 | `storage/app/private` não servido directamente | URL directa → 404 |
| S9 | `/health` 200 | `curl -sf https://…/health` |
| S10 | Backup smoke | após instalar Spatie: `php artisan backup:run` manual |

Marcar cada linha na checklist do PR de GO com data e responsável.
