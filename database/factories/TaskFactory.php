<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Normal,
            'due_date' => null,
            'assigned_to' => null,
            'created_by' => null,
            'related_type' => null,
            'related_id' => null,
            'completed_at' => null,
        ];
    }
}

