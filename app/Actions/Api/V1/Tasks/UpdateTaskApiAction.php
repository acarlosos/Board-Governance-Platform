<?php

namespace App\Actions\Api\V1\Tasks;

use App\Actions\Tasks\PersistTaskAction;
use App\Models\Task;
use App\Models\User;

final class UpdateTaskApiAction
{
    /**
     * API v1 PATCH wrapper: garante update parcial sem “reset” de campos não enviados.
     *
     * @param  array<string, mixed>  $payload
     */
    public function execute(User $actor, Task $task, array $payload): Task
    {
        $data = [
            // O core Action (`PersistTaskAction`) aplica tenant guard; aqui só evitamos loss de dados.
            'tenant_id' => $task->tenant_id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority?->value,
            'due_date' => $task->due_date,
            'assigned_to' => $task->assigned_to,
            'related_type' => $task->related_type,
            'related_id' => $task->related_id,
        ];

        foreach (['title', 'description', 'priority', 'due_date', 'assigned_to', 'related_type', 'related_id'] as $key) {
            if (array_key_exists($key, $payload)) {
                $data[$key] = $payload[$key];
            }
        }

        // Se related_type for explicitamente null, limpar related_id para evitar referência “órfã”.
        if (array_key_exists('related_type', $payload) && $payload['related_type'] === null) {
            $data['related_id'] = null;
        }

        return app(PersistTaskAction::class)->update($actor, $task, $data);
    }
}

