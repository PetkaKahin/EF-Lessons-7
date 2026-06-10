<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Task\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTaskCompletedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public ?int $completedByUserId,
        public TaskStatus $previousStatus,
    ) {}

    public function handle(): void
    {
        Log::info('Task completed notification sent.', [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'completed_by_user_id' => $this->completedByUserId,
            'previous_status' => $this->previousStatus->value,
            'current_status' => $this->task->status->value,
        ]);
    }
}
