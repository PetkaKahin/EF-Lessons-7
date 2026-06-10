<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\TaskAuditEvent;
use App\Events\TaskCompleted;
use Illuminate\Support\Facades\DB;

class WriteTaskAuditLog
{
    public function handle(TaskCompleted $event): void
    {
        DB::table('task_audits')->insert([
            'task_id' => $event->task->id,
            'event' => TaskAuditEvent::Completed->value,
            'occurred_at' => now(),
            'meta' => json_encode([
                'user_id' => $event->completedByUserId,
                'previous_status' => $event->previousStatus->value,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
