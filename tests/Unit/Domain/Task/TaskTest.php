<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task;

use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Exceptions\InvalidTaskStatusTransition;
use App\Domain\Task\Exceptions\InvalidTaskTitle;
use App\Domain\Task\Task;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function test_task_changes_own_data(): void
    {
        $task = Task::create(
            userId: 1,
            title: 'Initial title',
            createdAt: new DateTimeImmutable('2026-05-22 10:00:00'),
        );

        $task->rename('Updated title');
        $task->changeDescription('Updated description');

        $this->assertSame('Updated title', $task->title);
        $this->assertSame('Updated description', $task->description);
    }

    public function test_task_moves_forward_through_lifecycle(): void
    {
        $task = Task::create(
            userId: 1,
            title: 'Implement lifecycle',
            createdAt: new DateTimeImmutable,
        );

        $task->startProgress();

        $this->assertSame(TaskStatus::InProgress, $task->status);

        $task->complete();

        $this->assertSame(TaskStatus::Done, $task->status);
    }

    public function test_task_rejects_blank_title(): void
    {
        $this->expectException(InvalidTaskTitle::class);

        Task::create(
            userId: 1,
            title: '   ',
            createdAt: new DateTimeImmutable,
        );
    }

    public function test_task_rejects_skipping_statuses(): void
    {
        $task = Task::create(
            userId: 1,
            title: 'Implement lifecycle',
            createdAt: new DateTimeImmutable,
        );

        $this->expectException(InvalidTaskStatusTransition::class);

        $task->complete();
    }

    public function test_task_rejects_status_rollback(): void
    {
        $task = Task::create(
            userId: 1,
            title: 'Implement lifecycle',
            createdAt: new DateTimeImmutable,
        );

        $task->startProgress();
        $task->complete();

        $this->expectException(InvalidTaskStatusTransition::class);

        $task->changeStatus(TaskStatus::InProgress);
    }
}
