<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Task\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Seed the tasks table with predictable demo data.
     */
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Task Owner',
                'password' => 'password',
            ]
        );

        $teammate = User::query()->firstOrCreate(
            ['email' => 'teammate@example.com'],
            [
                'name' => 'Task Teammate',
                'password' => 'password',
            ]
        );

        $tasks = [
            [
                'user_id' => $owner->id,
                'title' => 'Read Laravel lifecycle docs',
                'description' => 'Understand how request, routing, controller, and response layers work together.',
                'status' => TaskStatus::New->value,
            ],
            [
                'user_id' => $owner->id,
                'title' => 'Create Task migration',
                'description' => 'Add columns for title, description, status, and timestamps.',
                'status' => TaskStatus::Done->value,
            ],
            [
                'user_id' => $owner->id,
                'title' => 'Implement FormRequest validation',
                'description' => 'Move request validation rules out of the controller.',
                'status' => TaskStatus::Done->value,
            ],
            [
                'user_id' => $owner->id,
                'title' => 'Add Task API resource',
                'description' => 'Keep response structure consistent for single records and lists.',
                'status' => TaskStatus::InProgress->value,
            ],
            [
                'user_id' => $owner->id,
                'title' => 'Check status filter',
                'description' => 'Call GET /api/tasks?status=done and verify only completed tasks are returned.',
                'status' => TaskStatus::New->value,
            ],
            [
                'user_id' => $teammate->id,
                'title' => 'Check pagination links',
                'description' => 'Call GET /api/tasks?per_page=2 and inspect links and meta blocks.',
                'status' => TaskStatus::InProgress->value,
            ],
            [
                'user_id' => $teammate->id,
                'title' => 'Write Pest tests',
                'description' => 'Cover create, list, show, update, delete, 404, and validation errors.',
                'status' => TaskStatus::Done->value,
            ],
            [
                'user_id' => $teammate->id,
                'title' => 'Review API errors',
                'description' => 'Make sure validation errors use the standard Laravel 422 response.',
                'status' => TaskStatus::New->value,
            ],
            [
                'user_id' => $teammate->id,
                'title' => 'Try DELETE endpoint',
                'description' => null,
                'status' => TaskStatus::InProgress->value,
            ],
            [
                'user_id' => $teammate->id,
                'title' => 'Document curl examples',
                'description' => null,
                'status' => TaskStatus::New->value,
            ],
        ];

        foreach ($tasks as $task) {
            Task::query()->updateOrCreate(
                ['title' => $task['title']],
                $task
            );
        }
    }
}
