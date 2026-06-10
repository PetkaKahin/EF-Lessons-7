<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Jobs\SendTaskCompletedNotification;

class QueueTaskCompletedNotification
{
    public function handle(TaskCompleted $event): void
    {
        SendTaskCompletedNotification::dispatch(
            $event->task,
            $event->completedByUserId,
            $event->previousStatus,
        );
    }
}
