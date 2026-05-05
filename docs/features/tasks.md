# Tasks (Workflows / Pendências)

## Objetivo

Gerir **pendências** (tarefas) com atribuição a utilizadores, prazos e histórico de mudanças, com isolamento **100% multi-tenant**.

## Tabelas envolvidas

- `tasks`
- `task_comments`
- `task_history`

## Models envolvidos

- `App\Models\Task`
- `App\Models\TaskComment`
- `App\Models\TaskHistory`

## Policies envolvidas

- `App\Policies\TaskPolicy`
- `App\Policies\TaskCommentPolicy`

## Services / Actions envolvidos

- `App\Actions\Tasks\PersistTaskAction`
- `App\Actions\Tasks\StartTaskAction`
- `App\Actions\Tasks\CompleteTaskAction`
- `App\Actions\Tasks\CancelTaskAction`
- `App\Actions\Tasks\AddTaskCommentAction`
- `App\Actions\Tasks\RecordTaskHistoryAction`

## Regras de negócio

- **Multi-tenancy:** `tenant_id` sempre aplicado e validado no backend.
- **assigned_to:** quando definido, **deve** apontar para um `users.id` do **mesmo tenant**.
- **related_type/related_id:** quando definido, deve apontar para entidade do **mesmo tenant** (whitelist: `Meeting`, `Document`, `Minute`, `Vote`).
- **Status controlado por Actions** via state machine (`TaskStatus`):
  - `pending` → `in_progress` → `completed`
  - `pending`/`in_progress` → `cancelled`
- **completed_at:** só existe quando `status = completed`.
- **Histórico:** alterações relevantes geram registros em `task_history` (`created`, `updated`, `status_changed`, `comment_added`).
- **Comentários:** só podem ser adicionados se o utilizador **puder ver** a task.

## Regras de segurança

- **Isolamento por tenant** em queries e validações (incl. `withoutGlobalScopes()` apenas para checagens internas, sempre validando `tenant_id`).
- Policies garantem:
  - `super_admin` vê tudo.
  - `tenant_admin` e utilizadores com `manage_tasks` gerem tasks do próprio tenant.
  - utilizador atribuído pode ver a task; update permitido apenas via Actions.

## Filament (Admin)

- Resource: `App\Filament\Admin\Resources\Tasks\TaskResource`
- Relações:
  - comentários (criação via Action)
  - histórico (somente leitura)

## Testes relacionados

- `tests/Feature/TasksTest.php`

## Pendências futuras

- Permissões mais granulares (ex.: `view_tasks`, `comment_tasks`) se necessário.
- UI de seleção do relacionado (`related_id`) por lookup seguro em vez de campo numérico.

