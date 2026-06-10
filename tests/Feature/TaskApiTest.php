<?php

declare(strict_types=1);

use App\Domain\Task\Enums\TaskStatus;
use App\Enums\TaskAuditEvent;
use App\Events\TaskCompleted;
use App\Jobs\SendTaskCompletedNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// чтобы тесты не падали
define('LARAVEL_START', microtime(true));

function apiTokenFor(User $user): string
{
    return $user->createToken('test-token')->plainTextToken;
}

it('can issue a sanctum token and use it for task requests', function (): void {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'secret-password',
    ]);

    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'secret-password',
        'token_name' => 'lesson-token',
    ]);

    $loginResponse
        ->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', 'owner@example.com')
        ->assertJsonStructure([
            'data' => [
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);

    $this->withToken($loginResponse->json('data.token'))
        ->getJson('/api/tasks')
        ->assertOk();
});

it('rejects invalid login credentials', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'secret-password',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires authentication for task endpoints', function (): void {
    $this->getJson('/api/tasks')
        ->assertUnauthorized();
});

it('can revoke the current sanctum token', function (): void {
    $user = User::factory()->create();
    $token = apiTokenFor($user);

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/tasks')
        ->assertUnauthorized();
});

it('can create a task with default status for the authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->withToken(apiTokenFor($user))
        ->postJson('/api/tasks', [
            'title' => 'Prepare Laravel API lesson',
            'description' => null,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.title', 'Prepare Laravel API lesson')
        ->assertJsonPath('data.description', null)
        ->assertJsonPath('data.status', 'new')
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'title',
                'description',
                'status',
                'created_at',
                'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Prepare Laravel API lesson',
        'status' => 'new',
    ]);
});

it('can create a task as in progress through the task lifecycle', function (): void {
    $user = User::factory()->create();

    $response = $this->withToken(apiTokenFor($user))
        ->postJson('/api/tasks', [
            'title' => 'Start implementation',
            'status' => 'in_progress',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.title', 'Start implementation')
        ->assertJsonPath('data.status', 'in_progress');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Start implementation',
        'status' => 'in_progress',
    ]);
});

it('rejects creating a task directly as done', function (): void {
    $user = User::factory()->create();

    $this->withToken(apiTokenFor($user))
        ->postJson('/api/tasks', [
            'title' => 'Already finished',
            'status' => 'done',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseMissing('tasks', [
        'user_id' => $user->id,
        'title' => 'Already finished',
    ]);
});

it('reuses incoming request id header', function (): void {
    Log::spy();

    $user = User::factory()->create();

    $response = $this
        ->withToken(apiTokenFor($user))
        ->withHeader('X-Request-Id', 'lesson-request-id')
        ->getJson('/api/tasks');

    $response
        ->assertOk()
        ->assertHeader('X-Request-Id', 'lesson-request-id');

    Log::shouldHaveReceived('shareContext')
        ->once()
        ->with([
            'requestId' => 'lesson-request-id',
        ]);
});

it('generates request id header when missing', function (): void {
    $user = User::factory()->create();

    $response = $this->withToken(apiTokenFor($user))->getJson('/api/tasks');
    $requestId = $response->headers->get('X-Request-Id');

    $response->assertOk();

    expect($requestId)->not->toBeNull()
        ->and(Str::isUuid($requestId))->toBeTrue();
});

it('adds app time header in milliseconds', function (): void {
    $user = User::factory()->create();

    $response = $this->withToken(apiTokenFor($user))->getJson('/api/tasks');
    $appTime = $response->headers->get('X-App-Time');

    $response->assertOk();

    expect($appTime)->not->toBeNull()
        ->and($appTime)->toMatch('/^\d+\.\d ms$/');
});

it('can list only owned tasks with status filter and pagination', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    Task::factory()->for($owner)->create([
        'title' => 'New task',
        'status' => 'new',
    ]);

    Task::factory()->for($owner)->create([
        'title' => 'Done task',
        'status' => 'done',
    ]);

    Task::factory()->for($otherUser)->create([
        'title' => 'Other user done task',
        'status' => 'done',
    ]);

    $response = $this->withToken(apiTokenFor($owner))
        ->getJson('/api/tasks?status=done&per_page=10');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $owner->id)
        ->assertJsonPath('data.0.title', 'Done task')
        ->assertJsonPath('data.0.status', 'done')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'user_id',
                    'title',
                    'description',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
});

it('validates task list filters and pagination parameters', function (): void {
    $user = User::factory()->create();
    $token = apiTokenFor($user);

    $this->withToken($token)
        ->getJson('/api/tasks?status=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->withToken($token)
        ->getJson('/api/tasks?page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);

    $this->withToken($token)
        ->getJson('/api/tasks?per_page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);

    $this->withToken($token)
        ->getJson('/api/tasks?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

it('can show, update, and delete an owned task', function (): void {
    $owner = User::factory()->create();

    $task = Task::factory()->for($owner)->create([
        'title' => 'Original title',
        'status' => 'new',
    ]);

    $token = apiTokenFor($owner);

    $this->withToken($token)
        ->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.user_id', $owner->id)
        ->assertJsonPath('data.title', 'Original title');

    $this->withToken($token)
        ->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Updated title',
            'status' => 'in_progress',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated title')
        ->assertJsonPath('data.status', 'in_progress');

    $this->withToken($token)
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

it('dispatches task completed event when task status becomes done', function (): void {
    Event::fake([
        TaskCompleted::class,
    ]);

    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'status' => 'in_progress',
    ]);

    $this->withToken(apiTokenFor($owner))
        ->patchJson("/api/tasks/{$task->id}", [
            'status' => 'done',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');

    Event::assertDispatched(
        TaskCompleted::class,
        fn (TaskCompleted $event): bool => $event->task->is($task)
            && $event->completedByUserId === $owner->id
            && $event->previousStatus === TaskStatus::InProgress
    );
});

it('rejects skipping and rolling back task status transitions', function (): void {
    $owner = User::factory()->create();
    $token = apiTokenFor($owner);

    $newTask = Task::factory()->for($owner)->create([
        'status' => 'new',
    ]);

    $this->withToken($token)
        ->patchJson("/api/tasks/{$newTask->id}", [
            'status' => 'done',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('tasks', [
        'id' => $newTask->id,
        'status' => 'new',
    ]);

    $inProgressTask = Task::factory()->for($owner)->create([
        'status' => 'in_progress',
    ]);

    $this->withToken($token)
        ->patchJson("/api/tasks/{$inProgressTask->id}", [
            'status' => 'new',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('tasks', [
        'id' => $inProgressTask->id,
        'status' => 'in_progress',
    ]);

    $doneTask = Task::factory()->for($owner)->create([
        'status' => 'done',
    ]);

    $this->withToken($token)
        ->patchJson("/api/tasks/{$doneTask->id}", [
            'status' => 'in_progress',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('tasks', [
        'id' => $doneTask->id,
        'status' => 'done',
    ]);
});

it('writes task audit log when task is completed', function (): void {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'status' => 'in_progress',
    ]);

    $this->withToken(apiTokenFor($owner))
        ->patchJson("/api/tasks/{$task->id}", [
            'status' => 'done',
        ])
        ->assertOk();

    $audit = DB::table('task_audits')
        ->where('task_id', $task->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->event)->toBe(TaskAuditEvent::Completed->value)
        ->and($audit->occurred_at)->not->toBeNull();

    $meta = json_decode($audit->meta, true, 512, JSON_THROW_ON_ERROR);

    expect($meta)->toMatchArray([
        'user_id' => $owner->id,
        'previous_status' => 'in_progress',
    ]);
});

it('queues task completed notification job when task is completed', function (): void {
    Queue::fake();

    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'status' => 'in_progress',
    ]);

    $this->withToken(apiTokenFor($owner))
        ->patchJson("/api/tasks/{$task->id}", [
            'status' => 'done',
        ])
        ->assertOk();

    Queue::assertPushed(
        SendTaskCompletedNotification::class,
        fn (SendTaskCompletedNotification $job): bool => $job->task->is($task)
            && $job->completedByUserId === $owner->id
            && $job->previousStatus === TaskStatus::InProgress
    );
});

it('delivers task completed notification to the log', function (): void {
    Log::spy();

    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'title' => 'Publish lesson homework',
        'status' => 'done',
    ]);

    (new SendTaskCompletedNotification(
        $task,
        $owner->id,
        TaskStatus::InProgress,
    ))->handle();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Task completed notification sent.', [
            'task_id' => $task->id,
            'task_title' => 'Publish lesson homework',
            'completed_by_user_id' => $owner->id,
            'previous_status' => 'in_progress',
            'current_status' => 'done',
        ]);
});

it('does not write task audit log when status does not transition to done', function (): void {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'status' => 'done',
    ]);

    $this->withToken(apiTokenFor($owner))
        ->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Still done',
        ])
        ->assertOk();

    $this->withToken(apiTokenFor($owner))
        ->patchJson("/api/tasks/{$task->id}", [
            'status' => 'done',
        ])
        ->assertOk();

    $this->assertDatabaseCount('task_audits', 0);
});

it('denies viewing, updating, and deleting another users task', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $task = Task::factory()->for($owner)->create([
        'title' => 'Private task',
    ]);

    $otherToken = apiTokenFor($otherUser);

    $this->withToken($otherToken)
        ->getJson("/api/tasks/{$task->id}")
        ->assertForbidden();

    $this->withToken($otherToken)
        ->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Stolen title',
        ])
        ->assertForbidden();

    $this->withToken($otherToken)
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Private task',
    ]);
});

it('returns 404 for missing task', function (): void {
    $user = User::factory()->create();

    $this->withToken(apiTokenFor($user))
        ->getJson('/api/tasks/999')
        ->assertNotFound();
});

it('uses standard 422 validation response', function (): void {
    $user = User::factory()->create();
    $token = apiTokenFor($user);

    $this->withToken($token)
        ->postJson('/api/tasks', [
            'status' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'status']);

    $task = Task::factory()->for($user)->create();

    $this->withToken($token)
        ->patchJson("/api/tasks/{$task->id}", [
            'title' => '',
            'status' => 'invalid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'status']);
});
