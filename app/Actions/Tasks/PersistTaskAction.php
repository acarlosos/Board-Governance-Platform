<?php

namespace App\Actions\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PersistTaskAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Task
    {
        $data = $this->applyTenantGuard($actor, $data, null);

        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_map(fn (TaskStatus $s) => $s->value, TaskStatus::cases()))],
            'priority' => ['required', 'string', Rule::in(array_map(fn (TaskPriority $p) => $p->value, TaskPriority::cases()))],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'related_type' => ['nullable', 'string'],
            'related_id' => ['nullable', 'integer'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            if (! $tenantId) {
                return;
            }

            $assignedTo = $safe->assigned_to ?? null;
            if ($assignedTo) {
                $user = User::query()->withoutGlobalScopes()->find($assignedTo);
                if (! $user || (int) $user->tenant_id !== (int) $tenantId) {
                    $v->errors()->add('assigned_to', __('tasks.validation.assigned_must_belong_to_tenant'));
                }
            }

            $relatedType = $safe->related_type ?? null;
            $relatedId = $safe->related_id ?? null;
            if ($relatedType || $relatedId) {
                $related = $this->resolveRelated($relatedType, $relatedId);
                if (! $related || (int) $related->getAttribute('tenant_id') !== (int) $tenantId) {
                    $v->errors()->add('related_id', __('tasks.validation.related_must_belong_to_tenant'));
                }
            }
        });

        $validated = $validator->validate();

        $task = new Task;
        $task->fill(Arr::only($validated, [
            'tenant_id',
            'title',
            'description',
            'status',
            'priority',
            'due_date',
            'assigned_to',
            'related_type',
            'related_id',
        ]));
        $task->created_by = $actor->getKey();

        // completed_at coerente
        $task->completed_at = ($task->status === TaskStatus::Completed) ? now() : null;

        $task->save();

        app(RecordTaskHistoryAction::class)->record($actor, $task, 'created', [], $task->only([
            'title',
            'status',
            'priority',
            'due_date',
            'assigned_to',
            'related_type',
            'related_id',
        ]));

        return $task->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Task $task, array $data): Task
    {
        $data = $this->applyTenantGuard($actor, $data, $task);

        $validator = Validator::make($data, [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', Rule::in(array_map(fn (TaskPriority $p) => $p->value, TaskPriority::cases()))],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'related_type' => ['nullable', 'string'],
            'related_id' => ['nullable', 'integer'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($task): void {
            $safe = $v->safe();
            $tenantId = $safe->tenant_id ?? null;
            if (! $tenantId) {
                return;
            }

            if (! auth()->user()?->isSuperAdmin() && (int) $task->tenant_id !== (int) $tenantId) {
                $v->errors()->add('tenant_id', __('tasks.validation.tenant_mismatch'));
            }

            $assignedTo = $safe->assigned_to ?? null;
            if ($assignedTo) {
                $user = User::query()->withoutGlobalScopes()->find($assignedTo);
                if (! $user || (int) $user->tenant_id !== (int) $tenantId) {
                    $v->errors()->add('assigned_to', __('tasks.validation.assigned_must_belong_to_tenant'));
                }
            }

            $relatedType = $safe->related_type ?? null;
            $relatedId = $safe->related_id ?? null;
            if ($relatedType || $relatedId) {
                $related = $this->resolveRelated($relatedType, $relatedId);
                if (! $related || (int) $related->getAttribute('tenant_id') !== (int) $tenantId) {
                    $v->errors()->add('related_id', __('tasks.validation.related_must_belong_to_tenant'));
                }
            }
        });

        $validated = $validator->validate();

        $old = $this->normalizeHistoryValues(
            $task->only(['title', 'description', 'priority', 'due_date', 'assigned_to', 'related_type', 'related_id'])
        );

        $task->fill(Arr::only($validated, ['tenant_id', 'title', 'description', 'priority', 'due_date', 'assigned_to', 'related_type', 'related_id']));
        $task->save();

        $new = $this->normalizeHistoryValues(
            $task->only(['title', 'description', 'priority', 'due_date', 'assigned_to', 'related_type', 'related_id'])
        );
        $changes = array_diff_assoc($new, $old);
        if ($changes !== []) {
            app(RecordTaskHistoryAction::class)->record($actor, $task, 'updated', $old, $new);
        }

        return $task->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantGuard(User $actor, array $data, ?Task $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (! $actor->isSuperAdmin() && $actor->tenant_id === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('tasks.validation.tenant_required'),
            ]);
        }

        if ($existing && ! $actor->isSuperAdmin()) {
            if ((int) $existing->tenant_id !== (int) $actor->tenant_id) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('tasks.validation.tenant_mismatch'),
                ]);
            }
        }

        return $data;
    }

    private function resolveRelated(?string $relatedType, mixed $relatedId): ?Model
    {
        if (! $relatedType || ! $relatedId) {
            return null;
        }

        $map = [
            Minute::class,
            Vote::class,
            Document::class,
            Meeting::class,
        ];

        if (! in_array($relatedType, $map, true)) {
            return null;
        }

        /** @var class-string<Model> $relatedType */
        return $relatedType::query()->withoutGlobalScopes()->find((int) $relatedId);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeHistoryValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            }
        }

        return $values;
    }
}

