# Board Governance Platform

SaaS **multi-tenant** de **governança corporativa** para conselhos administrativos (reuniões, documentos, votações, auditoria, etc.).

## Stack prevista

| Camada | Tecnologia |
|--------|--------------|
| Backend | **Laravel** (base instalada; ver `composer.json` para versão do framework) |
| Painel administrativo | Filament + Livewire *(ainda não instalado)* |
| Base de dados | **MySQL** |
| API | Laravel Sanctum *(a configurar mais tarde)* |

Documentação interna: pasta [`docs/`](docs/) (arquitetura, features, commits).

## Requisitos

- PHP 8.3+ e [Composer](https://getcomposer.org/)
- Node.js e npm (para assets / Vite)
- MySQL (base já existente no teu ambiente; **configuração via `.env`**)

## Instalação básica

1. Clonar o repositório e entrar na pasta do projecto.

2. Instalar dependências PHP e gerar `APP_KEY` se necessário:
   ```bash
   composer install
   cp .env.example .env   # se ainda não existir .env
   php artisan key:generate
   ```

3. **Configurar o `.env` manualmente** (obrigatório antes de migrar):
   - `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`
   - **`DB_*`** apontando para o teu MySQL (`DB_CONNECTION=mysql`, host, porta, base, utilizador, password)

   > **Aviso:** o ficheiro `.env` não é versionado. Ajusta as credenciais da base no teu ambiente; sem MySQL correcto no `.env`, migrações e a aplicação não funcionam.

4. (Opcional) Dependências front-end e build:
   ```bash
   npm install
   npm run build
   ```

5. **Migrações** (quando o `.env` estiver correcto):
   ```bash
   php artisan migrate
   ```
   Nesta fase inicial do repositório, as migrações padrão do Laravel (`users`, `cache`, `jobs`) ainda não foram aplicadas à tua base até correres o comando acima.

## Desenvolvimento

```bash
php artisan serve
```

Com Vite em paralelo: `npm run dev` (ver `composer.json` script `dev` para fluxo completo).

## Regras do projecto

Convenções para agentes e equipa: `.cursor/rules/`.
